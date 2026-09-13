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

    public function isPassed(): bool
    {
        $total = $this->total_marks ?? $this->total_questions;
        if ($total <= 0) return true;
        $percentage = ($this->score / $total) * 100;
        $passPct = $this->exam ? ($this->exam->pass_percentage ?? 40) : 40;
        return $percentage >= $passPct;
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    /**
     * Rebuild obtained marks from MCQ answers plus graded writing/file scores.
     */
    public function recalculateScore(): void
    {
        $this->loadMissing([
            'exam.questions.answers',
            'studentAnswers.question',
            'studentAnswers.answer',
        ]);

        $exam = $this->exam;
        $negativeMark = (float) ($exam->negative_marking ?? 0);
        $answersByQuestion = $this->studentAnswers->groupBy('question_id');

        $obtained = 0.0;
        $correctCount = 0;

        foreach ($exam->questions as $question) {
            $qMarks = (float) ($question->marks ?? 1);
            $qAnswers = $answersByQuestion->get($question->id, collect());

            if ($question->isFileUpload()) {
                $sa = $qAnswers->first();
                if ($sa && $sa->is_graded) {
                    $awarded = min($qMarks, max(0, (float) $sa->manual_score));
                    $obtained += $awarded;
                    if ($awarded >= $qMarks && $qMarks > 0) {
                        $correctCount++;
                    }
                }
                continue;
            }

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
                $correctCount++;
                $obtained += $qMarks;
            } elseif ($negativeMark > 0) {
                $obtained -= $negativeMark;
            }
        }

        $this->update([
            'score' => max(0, round($obtained, 2)),
            'correct_answers' => $correctCount,
        ]);
    }
}
