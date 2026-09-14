<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\AiExplanation;
use App\Models\Question;
use App\Services\AiExplanationService;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StudentController extends Controller
{
    public function login()
    {
        return view('portal');
    }

    public function authenticate(Request $request)
    {
        if ($request->filled('exam_id')) {
            return $this->authenticateLegacyExam($request);
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
            'role' => 'student',
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'Invalid student ID or password.'])->withInput($request->only('username'));
        }

        $request->session()->regenerate();
        $student = Auth::user();
        session([
            'student_id' => $student->username,
            'index_no' => $student->index_no ?: $student->username,
        ]);

        return redirect()->route('student.dashboard');
    }

    private function authenticateLegacyExam(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|string',
            'student_id' => 'required|string',
            'index_no' => 'required|string',
        ]);

        $exam = Exam::where('exam_id', $request->exam_id)->first();
        if (!$exam) return back()->withErrors(['exam_id' => 'Invalid Exam ID']);
        if (!$exam->is_active) return back()->withErrors(['exam_id' => 'This exam is currently not active']);
        if ($exam->start_time && now()->lt($exam->start_time)) return back()->withErrors(['exam_id' => 'This exam is scheduled to open on ' . $exam->start_time->format('M d, Y h:i A') . '. Please check back then.']);

        $cacheKey = 'student-login-' . $exam->id . '-' . $request->student_id;
        if (!Cache::add($cacheKey, true, now()->addMinutes(120))) {
            return back()->with('error', 'This student ID is already logged in elsewhere for this exam.');
        }

        session([
            'student_exam_id' => $exam->id,
            'student_id' => $request->student_id,
            'index_no' => $request->index_no,
            'exam_started_at' => now()->timestamp,
        ]);

        return redirect()->route('student.exam-preview', $exam->id);
    }

    public function dashboard()
    {
        $student = Auth::user();
        $completedExamIds = ExamResult::where('student_id', $student->username)->pluck('exam_id');
        $studentGroup = $student->student_group;
        $studentAcademicGroup = $studentGroup
            ? \App\Models\AcademicGroup::where(function ($query) use ($studentGroup) {
                $query->where('slug', \Illuminate\Support\Str::slug($studentGroup))
                    ->orWhere('name', $studentGroup);
            })->first()
            : null;
        $available = Exam::with(['academicGroup', 'academicSubject', 'chapters'])
            ->withCount('questions')
            ->has('questions')
            ->where(function ($query) {
                $query->whereNull('start_time')->orWhere('start_time', '<=', now());
            })
            ->where('is_active', true)
            ->where(function ($query) use ($studentAcademicGroup) {
                $query->where('creation_mode', 'manual');
                if ($studentAcademicGroup) {
                    $query->orWhere('academic_group_id', $studentAcademicGroup->id);
                } else {
                    $query->orWhere('creation_mode', 'structured');
                }
            })
            ->get();
        $runningExams = $available->filter(fn ($exam) => $exam->isOpenNow());
        $structuredGroups = $studentGroup
            ? \App\Models\AcademicGroup::with([
                'subjects.chapters.exams' => fn ($query) => $query
                    ->withCount('questions')
                    ->has('questions')
                    ->where('is_active', true),
            ])->where(function ($query) use ($studentGroup) {
                $query->where('slug', \Illuminate\Support\Str::slug($studentGroup))
                    ->orWhere('name', $studentGroup);
            })->get()
            : \App\Models\AcademicGroup::with([
                'subjects.chapters.exams' => fn ($query) => $query
                    ->withCount('questions')
                    ->has('questions')
                    ->where('is_active', true),
            ])->orderBy('name')->get();
        $manualExams = $available->where('creation_mode', 'manual')->values();
        $pastExams = Exam::withCount('questions')
            ->whereIn('id', $completedExamIds)
            ->latest('updated_at')->get();
        $results = ExamResult::with('exam')
            ->where('student_id', $student->username)
            ->latest('submitted_at')
            ->get()
            ->unique('exam_id')
            ->values();

        return view('student.dashboard', compact('student', 'runningExams', 'structuredGroups', 'manualExams', 'pastExams', 'results'));
    }

    public function profile()
    {
        return view('student.profile', ['student' => Auth::user()]);
    }

    public function beginExam($examId)
    {
        $exam = Exam::find($examId);
        if (!$exam) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This exam is no longer available because it was deleted by the teacher.');
        }
        $student = Auth::user();

        if (!$exam->canAttemptNow()) {
            return back()->with('error', 'This exam is not currently available.');
        }

        $cacheKey = 'student-session-v2-' . $exam->id . '-' . $student->username;
        $sessionId = session()->getId();
        $existingSessionId = Cache::get($cacheKey);
        if ($existingSessionId && $existingSessionId !== $sessionId) {
            return back()->with('error', 'This exam is already open in another session.');
        }
        Cache::put($cacheKey, $sessionId, now()->addMinutes(120));

        session([
            'student_exam_id' => $exam->id,
            'student_id' => $student->username,
            'index_no' => $student->index_no ?: $student->username,
            'exam_started_at' => now()->timestamp,
        ]);

        return redirect()->route('student.exam-preview', $exam->id);
    }

    public function historyResult($examResultId)
    {
        $examResult = ExamResult::with('exam')->find($examResultId);
        if (!$examResult || !$examResult->exam) {
            return redirect()->route('student.dashboard')
                ->with('error', 'This exam and its result are no longer available because the teacher deleted the exam.');
        }

        abort_unless($examResult->student_id === Auth::user()->username, 403);

        $latestResult = ExamResult::where('exam_id', $examResult->exam_id)
            ->where('student_id', $examResult->student_id)
            ->latest('submitted_at')
            ->first();

        if ($latestResult && $latestResult->id !== $examResult->id) {
            return redirect()->route('student.history-result', $latestResult->id);
        }

        $exam = $examResult->exam()->with(['questions.answers'])->firstOrFail();
        $examResult->load(['studentAnswers.question', 'studentAnswers.answer']);
        $sections = $examResult->evaluateSections();
        $hasUngradedFiles = $sections['has_writing'] && !$sections['writing_fully_graded'];
        $fileUploadAnswers = $examResult->studentAnswers->filter(fn ($answer) => $answer->question && $answer->question->isFileUpload());

        return view('student.check-results-display', compact('examResult', 'exam', 'hasUngradedFiles', 'fileUploadAnswers', 'sections'));
    }

    public function examPreview($examId)
    {
        if (Auth::check() && Auth::user()->isStudent() && !session('student_exam_id')) {
            return redirect()->route('student.begin-exam', $examId);
        }

        if (!session('student_exam_id') || session('student_exam_id') != $examId) {
            return redirect()->route('student.login');
        }

        $exam = Exam::with('questions')->find($examId);
        if (!$exam) {
            session()->forget(['student_exam_id', 'exam_started_at']);
            return redirect()->route('student.dashboard')
                ->with('error', 'This exam is no longer available because it was deleted by the teacher.');
        }
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

        $exam = Exam::with(['questions.answers'])->find($examId);
        if (!$exam) {
            session()->forget(['student_exam_id', 'exam_started_at']);
            return redirect()->route('student.dashboard')
                ->with('error', 'This exam is no longer available because it was deleted by the teacher.');
        }

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

        $exam = Exam::with(['questions.answers'])->find($examId);
        if (!$exam) {
            session()->forget(['student_exam_id', 'exam_started_at']);
            return redirect()->route('student.dashboard')
                ->with('error', 'This exam is no longer available because it was deleted by the teacher.');
        }
        
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

        $examResult->load(['studentAnswers.question']);

        // Clear session and login lock for this exam+student
        $cacheKey = 'student-session-v2-' . $exam->id . '-' . session('student_id');
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
                $cacheKey = 'student-session-v2-' . $examId . '-' . $studentId;
                Cache::forget($cacheKey);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
            'password' => 'required|string',
        ]);

        $exam = Exam::where('exam_id', $request->exam_id)
            ->with(['questions.answers'])
            ->first();

        if (!$exam) {
            return back()->withErrors(['exam_id' => 'Invalid Exam ID']);
        }

        $student = User::where('username', $request->student_id)
            ->where('role', 'student')
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return back()->withErrors(['student_id' => 'Invalid student ID or password.']);
        }

        $examResult = ExamResult::where('exam_id', $exam->id)
            ->where('student_id', $request->student_id)
            ->latest('submitted_at')
            ->with(['studentAnswers.question', 'studentAnswers.answer', 'exam.questions.answers'])
            ->first();

        if (!$examResult) {
            return back()->withErrors(['exam_id' => 'No results found for these credentials.']);
        }

        $sections = $examResult->evaluateSections();
        $hasUngradedFiles = $sections['has_writing'] && !$sections['writing_fully_graded'];

        $fileUploadAnswers = $examResult->studentAnswers->filter(function ($answer) {
            return $answer->question && $answer->question->isFileUpload();
        });

        return view('student.check-results-display', compact(
            'examResult', 'exam', 'hasUngradedFiles', 'fileUploadAnswers', 'sections'
        ));
    }

    public function explainWithAi(
        Request $request,
        ExamResult $examResult,
        Question $question,
        AiExplanationService $aiExplanationService
    )
    {
        $request->validate([
            'exam_id' => 'required|string',
            'student_id' => 'required|string',
            'index_no' => 'required|string',
        ]);

        abort_unless(
            $examResult->student_id === $request->student_id
            && $examResult->index_no === $request->index_no
            && $examResult->exam_id === $question->exam_id
            && $request->exam_id === optional($examResult->exam)->exam_id,
            403
        );

        abort_if(!$question->isMCQ(), 422, 'AI explanations are available for MCQ questions only.');

        $studentAnswers = $examResult->studentAnswers()
            ->where('question_id', $question->id)
            ->with('answer')
            ->get();
        $selectedAnswerIds = $studentAnswers->pluck('answer_id')->filter()->sort()->values()->all();
        $answerFingerprint = hash('sha256', json_encode($selectedAnswerIds));

        $cached = AiExplanation::where('exam_result_id', $examResult->id)
            ->where('question_id', $question->id)
            ->where('answer_fingerprint', $answerFingerprint)
            ->first();
        if ($cached) {
            return response()->json(['explanation' => $cached->explanation, 'cached' => true]);
        }

        $rateLimitKey = 'ai-explanation:' . $examResult->id . ':' . $examResult->student_id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->json([
                'message' => 'Too many AI requests. Please try again in a minute.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        try {
            $studentAnswerTexts = $studentAnswers->map(fn ($studentAnswer) => [
                'id' => $studentAnswer->answer_id,
                'text' => $studentAnswer->answer?->answer_text,
            ])->values()->all();

            $explanation = $aiExplanationService->explain([
                'question' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $question->answers->map(fn ($answer) => [
                    'id' => $answer->id,
                    'text' => $answer->answer_text,
                    'is_correct' => $answer->is_correct,
                ])->values()->all(),
                'student_answers' => $studentAnswerTexts,
            ]);

            $saved = AiExplanation::create([
                'exam_result_id' => $examResult->id,
                'question_id' => $question->id,
                'answer_fingerprint' => $answerFingerprint,
                'explanation' => $explanation,
            ]);

            return response()->json(['explanation' => $saved->explanation, 'cached' => false]);
        } catch (\Throwable $exception) {
            Log::warning('AI explanation request failed', [
                'exam_result_id' => $examResult->id,
                'question_id' => $question->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'AI explanation is temporarily unavailable. Please try again later.',
            ], 503);
        }
    }
}
