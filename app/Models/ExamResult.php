<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'index_no',
        'total_questions',
        'total_marks',
        'correct_answers',
        'score',
        'time_taken_seconds',
        'tab_switch_count',
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'time_taken_seconds' => 'integer',
        'tab_switch_count' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function isRankEligible(): bool
    {
        $exam = $this->exam;

        if (!$exam || !$exam->start_time || !$exam->end_time || !$this->submitted_at) {
            return false;
        }

        return $this->submitted_at->gte($exam->start_time)
            && $this->submitted_at->lte($exam->end_time);
    }

    /**
     * Split MCQ vs writing marks. Overall pass requires both sections
     * (when present) and writing must be fully graded first.
     */
    public function evaluateSections(): array
    {
        $this->loadMissing([
            'exam.questions.answers',
            'studentAnswers.question',
            'studentAnswers.answer',
        ]);

        $exam = $this->exam;
        $negativeMark = (float) ($exam->negative_marking ?? 0);
        $answersByQuestion = $this->studentAnswers->groupBy('question_id');

        $mcqObtained = 0.0;
        $mcqTotal = 0.0;
        $mcqCorrect = 0;
        $mcqCount = 0;

        $writingObtained = 0.0;
        $writingTotal = 0.0;
        $writingGraded = 0;
        $writingCount = 0;
        $writingCorrect = 0;

        $correctCount = 0;
        $obtained = 0.0;

        foreach ($exam->questions as $question) {
            $qMarks = (float) ($question->marks ?? 1);
            $qAnswers = $answersByQuestion->get($question->id, collect());

            if ($question->isFileUpload()) {
                $writingCount++;
                $writingTotal += $qMarks;
                $sa = $qAnswers->first();
                if ($sa && $sa->is_graded) {
                    $writingGraded++;
                    $awarded = min($qMarks, max(0, (float) $sa->manual_score));
                    $writingObtained += $awarded;
                    $obtained += $awarded;
                    if ($awarded >= $qMarks && $qMarks > 0) {
                        $writingCorrect++;
                        $correctCount++;
                    }
                }
                continue;
            }

            $mcqCount++;
            $mcqTotal += $qMarks;

            if ($qAnswers->isEmpty()) {
                continue;
            }

            $isCorrect = false;
            if ($question->question_type === 'multiple') {
                $selected = $qAnswers->pluck('answer_id')->filter()->sort()->values()->all();
                $correct = $question->answers->where('is_correct', true)->pluck('id')->sort()->values()->all();
                $isCorrect = !empty($selected) && $selected === $correct;
            } else {
                $sa = $qAnswers->first();
                $isCorrect = (bool) ($sa->answer && $sa->answer->is_correct);
            }

            if ($isCorrect) {
                $mcqCorrect++;
                $correctCount++;
                $mcqObtained += $qMarks;
                $obtained += $qMarks;
            } elseif ($negativeMark > 0) {
                $mcqObtained -= $negativeMark;
                $obtained -= $negativeMark;
            }
        }

        $mcqObtained = max(0, $mcqObtained);
        $obtained = max(0, $obtained);

        $mcqPct = $mcqTotal > 0 ? ($mcqObtained / $mcqTotal) * 100 : null;
        $writingFullyGraded = $writingCount === 0 || $writingGraded === $writingCount;
        $writingPct = ($writingTotal > 0 && $writingFullyGraded)
            ? ($writingObtained / $writingTotal) * 100
            : null;

        $mcqPassMark = $exam->mcqPassPercentage();
        $writingPassMark = $exam->writingPassPercentage();

        $mcqPassed = $mcqCount === 0 ? true : ($mcqPct !== null && $mcqPct >= $mcqPassMark);
        $writingPassed = $writingCount === 0
            ? true
            : ($writingFullyGraded && $writingPct !== null && $writingPct >= $writingPassMark);

        $overallReady = $writingFullyGraded;
        $overallPassed = $overallReady && $mcqPassed && $writingPassed;

        $totalMarks = $mcqTotal + $writingTotal;
        $overallPct = $totalMarks > 0 && $overallReady ? ($obtained / $totalMarks) * 100 : null;

        return [
            'mcq_count' => $mcqCount,
            'mcq_obtained' => round($mcqObtained, 2),
            'mcq_total' => round($mcqTotal, 2),
            'mcq_correct' => $mcqCorrect,
            'mcq_percentage' => $mcqPct !== null ? round($mcqPct, 1) : null,
            'mcq_pass_mark' => $mcqPassMark,
            'mcq_passed' => $mcqPassed,
            'writing_count' => $writingCount,
            'writing_obtained' => round($writingObtained, 2),
            'writing_total' => round($writingTotal, 2),
            'writing_graded' => $writingGraded,
            'writing_correct' => $writingCorrect,
            'writing_percentage' => $writingPct !== null ? round($writingPct, 1) : null,
            'writing_pass_mark' => $writingPassMark,
            'writing_passed' => $writingPassed,
            'writing_fully_graded' => $writingFullyGraded,
            'has_mcq' => $mcqCount > 0,
            'has_writing' => $writingCount > 0,
            'obtained' => round($obtained, 2),
            'total' => round($totalMarks, 2),
            'correct_count' => $correctCount,
            'overall_percentage' => $overallPct !== null ? round($overallPct, 1) : null,
            'overall_ready' => $overallReady,
            'overall_passed' => $overallPassed,
        ];
    }

    public function isPassed(): bool
    {
        return $this->evaluateSections()['overall_passed'];
    }

    public function isOverallReady(): bool
    {
        return $this->evaluateSections()['overall_ready'];
    }

    /**
     * Rebuild obtained marks from MCQ answers plus graded writing/file scores.
     */
    public function recalculateScore(): void
    {
        $sections = $this->evaluateSections();

        $this->update([
            'score' => $sections['obtained'],
            'correct_answers' => $sections['correct_count'],
        ]);
    }
}
