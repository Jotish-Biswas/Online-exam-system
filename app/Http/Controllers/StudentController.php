<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function login()
    {
        return view('portal');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|string',
            'student_id' => 'required|string',
            'index_no' => 'required|string',
        ]);

        $exam = Exam::where('exam_id', $request->exam_id)->first();

        if (!$exam) {
            return back()->withErrors(['exam_id' => 'Invalid Exam ID']);
        }

        if (!$exam->is_active) {
            return back()->withErrors(['exam_id' => 'This exam is currently not active']);
        }

        // Check scheduled window (start_time & end_time)
        if ($exam->start_time && now()->lt($exam->start_time)) {
            return back()->withErrors(['exam_id' => 'This exam is scheduled to open on ' . $exam->start_time->format('M d, Y h:i A') . '. Please check back then.']);
        }
        if ($exam->end_time && now()->gt($exam->end_time)) {
            return back()->withErrors(['exam_id' => 'This exam closed on ' . $exam->end_time->format('M d, Y h:i A') . '. Submissions are no longer accepted.']);
        }

        // Check if the student has already taken this exam (only by student_id)
        $existingResult = ExamResult::where('exam_id', $exam->id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($existingResult) {
            return back()->with('error', 'You have already completed this exam.');
        }

        // Prevent concurrent logins for the same student on the same exam using atomic cache add
        $cacheKey = 'student-login-' . $exam->id . '-' . $request->student_id;
        // Cache::add returns false if the key already exists, true if added
        $locked = Cache::add($cacheKey, true, now()->addMinutes(120)); // Lock for 2 hours
        if (!$locked) {
            return back()->with('error', 'This student ID is already logged in elsewhere for this exam.');
        }

        // Store student info and exam start time in session
        session([
            'student_exam_id' => $exam->id,
            'student_id' => $request->student_id,
            'index_no' => $request->index_no,
            'exam_started_at' => now()->timestamp,
        ]);

        return redirect()->route('student.exam-preview', $exam->id);
    }

    public function examPreview($examId)
    {
        if (!session('student_exam_id') || session('student_exam_id') != $examId) {
            return redirect()->route('student.login');
        }

        $exam = Exam::with('questions')->findOrFail($examId);
        $totalMarks      = $exam->questions->sum('marks') ?: $exam->questions->count();
        $totalQuestions  = $exam->questions->count();
        $hasNegative     = ($exam->negative_marking ?? 0) > 0;
        $hasMCQ          = $exam->questions->whereIn('question_type', ['single', 'multiple'])->count() > 0;
        $hasFileUpload   = $exam->questions->where('question_type', 'file_upload')->count() > 0;

        return view('student.exam-preview', compact(
            'exam', 'totalMarks', 'totalQuestions', 'hasNegative', 'hasMCQ', 'hasFileUpload'
        ));
    }

    public function exam($examId)
    {
        if (!session('student_exam_id') || session('student_exam_id') != $examId) {
            return redirect()->route('student.login');
        }

        $exam = Exam::with(['questions.answers'])->findOrFail($examId);

        // Reset timer on first actual exam start (after preview)
        if (!session('exam_timer_started_' . $examId)) {
            session([
                'exam_started_at'                    => now()->timestamp,
                'exam_timer_started_' . $examId      => true,
            ]);
        }

        // Handle question ordering (stable per student session)
        if ($exam->shuffle_questions) {
            $sessionKey = 'exam_question_order_' . $exam->id;
            if (!session()->has($sessionKey)) {
                $qIds = $exam->questions->pluck('id')->all();
                shuffle($qIds);
                session([$sessionKey => $qIds]);
            }
            $orderedIds = session($sessionKey, []);
            $exam->setRelation('questions', $exam->questions->sortBy(function($q) use ($orderedIds) {
                $idx = array_search($q->id, $orderedIds);
                return $idx === false ? 9999 : $idx;
            })->values());
        }

        // Handle option shuffling per question (stable per student session)
        if ($exam->shuffle_options) {
            foreach ($exam->questions as $question) {
                if ($question->isMCQ() && $question->answers->count() > 1) {
                    $ansKey = 'exam_answer_order_' . $question->id;
                    if (!session()->has($ansKey)) {
                        $aIds = $question->answers->pluck('id')->all();
                        shuffle($aIds);
                        session([$ansKey => $aIds]);
                    }
                    $orderedAnsIds = session($ansKey, []);
                    $question->setRelation('answers', $question->answers->sortBy(function($a) use ($orderedAnsIds) {
                        $idx = array_search($a->id, $orderedAnsIds);
                        return $idx === false ? 9999 : $idx;
                    })->values());
                }
            }
        }

        // Calculate timer remaining seconds
        $durationMinutes = $exam->duration_minutes ?? 30;
        $totalDurationSeconds = $durationMinutes * 60;
        $startedAt = session('exam_started_at', now()->timestamp);
        $elapsedSeconds = max(0, now()->timestamp - $startedAt);
        $remainingSeconds = max(0, $totalDurationSeconds - $elapsedSeconds);

        return view('student.exam', compact('exam', 'remainingSeconds', 'totalDurationSeconds'));
    }

    public function submitExam(Request $request, $examId)
    {
        if (!session('student_exam_id') || session('student_exam_id') != $examId) {
            return redirect()->route('student.login');
        }

        $exam = Exam::with(['questions.answers'])->findOrFail($examId);
        
        // Build flexible validation rules (allow skipping questions)
        $rules = [
            'tab_switch_count' => 'nullable|integer|min:0',
        ];
        $mcqQuestions = 0;
        $fileUploadQuestions = 0;
        
        foreach ($exam->questions as $question) {
            if ($question->question_type === 'file_upload') {
                $fileUploadQuestions++;
                $allowedExtensions = implode(',', $question->getAllowedExtensions());
                $maxSizeMb = $question->getMaxFileSize();
                
                $rules["file_uploads.{$question->id}"] = "nullable|file|mimes:{$allowedExtensions}|max:" . ($maxSizeMb * 1024);
            } elseif ($question->question_type === 'single') {
                $mcqQuestions++;
                $rules["answers.{$question->id}"] = 'nullable|integer|exists:answers,id';
            } else { // multiple choice
                $mcqQuestions++;
                $rules["answers.{$question->id}"] = 'nullable|array';
                $rules["answers.{$question->id}.*"] = 'integer|exists:answers,id';
            }
        }
        
        $request->validate($rules);

        // Calculate score for MCQ questions
        $correctAnswers = 0;
        $obtainedScore = 0.00;
        $totalMarks = $exam->questions->sum('marks') ?: (float) $exam->questions->count();
        $negativeMark = (float) ($exam->negative_marking ?? 0.00);

        // Process MCQ answers
        if ($request->has('answers')) {
            foreach ($request->answers as $questionId => $answerData) {
                if (empty($answerData)) {
                    continue;
                }
                $question = $exam->questions->find($questionId);
                if (!$question) {
                    continue;
                }
                $qMarks = (float) ($question->marks ?? 1.00);
                
                if ($question->question_type === 'single') {
                    $answer = \App\Models\Answer::find($answerData);
                    if ($answer && $answer->is_correct) {
                        $correctAnswers++;
                        $obtainedScore += $qMarks;
                    } else {
                        if ($negativeMark > 0) {
                            $obtainedScore -= $negativeMark;
                        }
                    }
                } else {
                    $selectedAnswerIds = is_array($answerData) ? $answerData : [$answerData];
                    $correctAnswerIds = $question->answers()->where('is_correct', true)->pluck('id')->toArray();
                    
                    sort($selectedAnswerIds);
                    sort($correctAnswerIds);
                    
                    if (!empty($selectedAnswerIds) && $selectedAnswerIds === $correctAnswerIds) {
                        $correctAnswers++;
                        $obtainedScore += $qMarks;
                    } else {
                        if ($negativeMark > 0) {
                            $obtainedScore -= $negativeMark;
                        }
                    }
                }
            }
        }

        // Score cannot be less than 0
        $score = max(0, round($obtainedScore, 2));

        // Calculate session duration and tab switch count
        $startedAt = session('exam_started_at', now()->timestamp);
        $timeTakenSeconds = max(0, now()->timestamp - $startedAt);
        $tabSwitchCount = (int) $request->input('tab_switch_count', 0);

        // Create exam result
        $examResult = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => session('student_id'),
            'index_no' => session('index_no'),
            'total_questions' => $exam->questions->count(),
            'total_marks' => $totalMarks,
            'correct_answers' => $correctAnswers,
            'score' => $score,
            'time_taken_seconds' => $timeTakenSeconds,
            'tab_switch_count' => $tabSwitchCount,
            'submitted_at' => now()
        ]);

        // Store MCQ answers
        if ($request->has('answers')) {
            foreach ($request->answers as $questionId => $answerData) {
                if (empty($answerData)) {
                    continue;
                }
                $question = $exam->questions->find($questionId);
                if (!$question) {
                    continue;
                }
                
                if ($question->question_type === 'single') {
                    StudentAnswer::create([
                        'exam_result_id' => $examResult->id,
                        'question_id' => $questionId,
                        'answer_id' => $answerData
                    ]);
                } else {
                    $selectedAnswerIds = is_array($answerData) ? $answerData : [$answerData];
                    foreach ($selectedAnswerIds as $answerId) {
                        StudentAnswer::create([
                            'exam_result_id' => $examResult->id,
                            'question_id' => $questionId,
                            'answer_id' => $answerId
                        ]);
                    }
                }
            }
        }

        // Store file uploads
        if ($request->has('file_uploads')) {
            foreach ($request->file('file_uploads') as $questionId => $uploadedFile) {
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $extension = $uploadedFile->getClientOriginalExtension();
                    $filename = 'exam_' . $exam->id . '_student_' . session('student_id') . '_question_' . $questionId . '_' . time() . '.' . $extension;
                    $filePath = $uploadedFile->storeAs('exam_submissions', $filename, 'public');
                    
                    StudentAnswer::create([
                        'exam_result_id' => $examResult->id,
                        'question_id' => $questionId,
                        'answer_id' => null,
                        'file_path' => $filePath,
                        'original_filename' => $uploadedFile->getClientOriginalName(),
                        'file_size' => $uploadedFile->getSize(),
                        'file_mime_type' => $uploadedFile->getMimeType(),
                        'is_graded' => false
                    ]);
                }
            }
        }

        // Clear session and login lock for this exam+student
        $cacheKey = 'student-login-' . $exam->id . '-' . session('student_id');
        Cache::forget($cacheKey);

        $keysToForget = ['student_exam_id', 'student_id', 'index_no', 'exam_started_at',
                         'exam_question_order_' . $exam->id, 'exam_timer_started_' . $exam->id];
        foreach ($exam->questions as $q) {
            $keysToForget[] = 'exam_answer_order_' . $q->id;
        }
        session()->forget($keysToForget);

        return view('student.result', compact('examResult', 'exam'));
    }

    public function logout(Request $request)
    {
        $studentId = session('student_id');

        if ($studentId) {
            $examId = session('student_exam_id');
            if ($examId) {
                $cacheKey = 'student-login-' . $examId . '-' . $studentId;
                Cache::forget($cacheKey);
            }
        }

        session()->forget(['student_exam_id', 'student_id', 'index_no', 'exam_started_at']);

        return redirect()->route('student.login')->with('success', 'You have been successfully logged out.');
    }

    public function checkResultsForm()
    {
        return view('student.check-results');
    }

    public function checkResults(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|string',
            'student_id' => 'required|string',
            'index_no' => 'required|string',
        ]);

        $exam = Exam::where('exam_id', $request->exam_id)->first();

        if (!$exam) {
            return back()->withErrors(['exam_id' => 'Invalid Exam ID']);
        }

        $examResult = ExamResult::where('exam_id', $exam->id)
            ->where('student_id', $request->student_id)
            ->where('index_no', $request->index_no)
            ->with(['studentAnswers.question', 'studentAnswers.answer'])
            ->first();

        if (!$examResult) {
            return back()->withErrors(['exam_id' => 'No results found for these credentials.']);
        }

        // Check if there are any file upload questions that are not graded yet
        $fileUploadAnswers = $examResult->studentAnswers->filter(function($answer) {
            return $answer->question->isFileUpload();
        });

        $ungradedFiles = $fileUploadAnswers->filter(function($answer) {
            return !$answer->is_graded;
        });

        $hasUngradedFiles = $ungradedFiles->isNotEmpty();

        return view('student.check-results-display', compact('examResult', 'exam', 'hasUngradedFiles', 'fileUploadAnswers'));
    }
}
