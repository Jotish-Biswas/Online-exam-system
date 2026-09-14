<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Answer;
use App\Models\ExamResult;
use App\Models\StudentAnswer;
use App\Models\AcademicGroup;
use App\Models\AcademicSubject;
use App\Models\AcademicChapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AdminController extends Controller
{
    public function login()
    {
        return view('portal');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $request->username,
            'password' => $request->password,
        ];

        if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $request->session()->forget(['student_id', 'index_no', 'student_exam_id']);
            session([
                'admin_logged_in' => true,
                'user_role' => \Illuminate\Support\Facades\Auth::user()->role
            ]);
            return redirect()->route('admin.dashboard')->with('success', 'Welcome back, ' . \Illuminate\Support\Facades\Auth::user()->name . '!');
        }

        // Backward compatibility fallback for default credentials
        if ($request->username === 'sitcexm-admin' && $request->password === 'SITC@saudiarabia') {
            $user = \App\Models\User::firstOrCreate(
                ['username' => 'sitcexm-admin'],
                [
                    'name' => 'System Administrator',
                    'email' => 'admin@sitc.edu',
                    'password' => \Illuminate\Support\Facades\Hash::make('SITC@saudiarabia'),
                    'role' => 'admin',
                ]
            );
            \Illuminate\Support\Facades\Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(['student_id', 'index_no', 'student_exam_id']);
            session(['admin_logged_in' => true, 'user_role' => 'admin']);
            return redirect()->route('admin.dashboard')->with('success', 'Welcome to Admin Dashboard!');
        }

        return back()->with('error', 'Invalid username or password.');
    }

    public function logout(Request $request)
    {
        \Illuminate\Support\Facades\Auth::logout();
        session()->forget(['admin_logged_in', 'user_role']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been logged out successfully.');
    }

    public function dashboard()
    {
        $exams = Exam::with(['questions', 'academicGroup', 'academicSubject', 'chapters'])
                    ->withCount('examResults')
                    ->orderBy('created_at', 'desc')
                    ->get();
        $architectureGroups = AcademicGroup::with([
            'subjects.chapters.exams' => fn ($query) => $query
                ->with(['academicGroup', 'academicSubject'])
                ->withCount('examResults')
                ->withCount([
                    'questions',
                    'questions as file_upload_questions_count' => fn ($questionQuery) => $questionQuery->where('question_type', 'file_upload'),
                ])
                ->orderByDesc('created_at'),
        ])->orderBy('name')->get();
        $activeExams = $exams->where('is_active', true)->values();

        $pendingWriting = StudentAnswer::whereHas('question', function ($q) {
            $q->where('question_type', 'file_upload');
        })->where('is_graded', false)->count();

        // Global stats for dashboard overview
        $stats = [
            'total_exams'       => Exam::count(),
            'total_submissions' => ExamResult::count(),
            'avg_score_pct'     => 0,
            'overall_pass_rate' => 0,
            'pending_writing'   => $pendingWriting,
        ];

        $allResults = ExamResult::with('exam')->get();
        if ($allResults->count() > 0) {
            $totalPct = 0;
            $passCount = 0;
            foreach ($allResults as $r) {
                $total = $r->total_marks ?: ($r->total_questions ?: 1);
                $pct   = $total > 0 ? ($r->score / $total) * 100 : 0;
                $totalPct += $pct;
                $passMark = $r->exam ? ($r->exam->pass_percentage ?? 40) : 40;
                if ($pct >= $passMark) $passCount++;
            }
            $stats['avg_score_pct']     = round($totalPct / $allResults->count(), 1);
            $stats['overall_pass_rate'] = round(($passCount / $allResults->count()) * 100, 1);
        }

        return view('admin.dashboard', compact('exams', 'stats', 'architectureGroups', 'activeExams'));
    }

    public function aiQuestionGenerator()
    {
        $exams = Exam::orderByDesc('created_at')->get(['id', 'exam_name', 'exam_id']);

        return view('admin.ai-question-generator', compact('exams'));
    }

    public function generateAiQuestions(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'prompt' => 'required|string|max:5000',
            'youtube_urls' => 'nullable|array|max:5',
            'youtube_urls.*' => 'url|max:500',
            'sources' => 'nullable|array|max:5',
            'sources.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        if (!$request->hasFile('sources') && !$request->filled('youtube_urls')) {
            return back()->withInput()->withErrors(['sources' => 'Upload a PDF/image or provide a YouTube URL.']);
        }

        try {
            $http = Http::timeout(config('services.ai_question_generator.timeout'))
                ->accept('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            foreach ($request->file('sources', []) as $source) {
                $http = $http->attach('sources', fopen($source->getRealPath(), 'r'), $source->getClientOriginalName());
            }
            $response = $http->post(rtrim(config('services.ai_question_generator.url'), '/') . '/generate', [
                'prompt' => $request->string('prompt')->toString(),
                'youtube_urls' => json_encode(array_values($request->input('youtube_urls', []))),
            ]);
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['source' => 'AI service is unreachable. Start the Python service and try again.']);
        }

        if (!$response->successful()) {
            $detail = data_get($response->json(), 'detail', 'AI generation failed.');

            return back()->withInput()->withErrors(['source' => $detail]);
        }

        return response($response->body(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="ai-generated-questions.xlsx"',
        ]);
    }

    public function chatAiQuestions(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'prompt' => 'required|string|max:5000',
            'history' => 'nullable|json|max:50000',
            'youtube_urls' => 'nullable|array|max:5',
            'youtube_urls.*' => 'url|max:500',
            'sources' => 'nullable|array|max:5',
            'sources.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        if (!$request->hasFile('sources') && !$request->filled('youtube_urls')) {
            return response()->json(['message' => 'Add a PDF/image or YouTube URL before sending your first message.'], 422);
        }

        try {
            $http = Http::timeout(config('services.ai_question_generator.timeout'));
            foreach ($request->file('sources', []) as $source) {
                $http = $http->attach('sources', fopen($source->getRealPath(), 'r'), $source->getClientOriginalName());
            }
            $response = $http->post(rtrim(config('services.ai_question_generator.url'), '/') . '/chat', [
                'prompt' => $request->string('prompt')->toString(),
                'history' => $request->input('history', '[]'),
                'youtube_urls' => json_encode(array_values($request->input('youtube_urls', []))),
            ]);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'AI service is unreachable. Start the Python service and try again.'], 502);
        }

        if (!$response->successful()) {
            return response()->json([
                'message' => data_get($response->json(), 'detail', 'AI generation failed.'),
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function createExam()
    {
        $groups = AcademicGroup::with('subjects.chapters')->orderBy('name')->get();
        return view('admin.create-exam', compact('groups'));
    }

    public function storeAcademicGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|alpha_dash|unique:academic_groups,slug',
        ]);
        AcademicGroup::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
        ]);
        return back()->with('success', 'Academic group created.');
    }

    public function storeAcademicSubject(Request $request)
    {
        $data = $request->validate([
            'academic_group_id' => 'required|exists:academic_groups,id',
            'name' => 'required|string|max:150',
            'paper' => 'nullable|string|max:50',
        ]);
        AcademicSubject::create($data + ['sort_order' => AcademicSubject::where('academic_group_id', $data['academic_group_id'])->max('sort_order') + 1]);
        return back()->with('success', 'Subject created.');
    }

    public function storeAcademicChapter(Request $request)
    {
        $data = $request->validate([
            'academic_subject_id' => 'required|exists:academic_subjects,id',
            'title' => 'required|string|max:255',
        ]);
        AcademicChapter::create($data + ['sort_order' => AcademicChapter::where('academic_subject_id', $data['academic_subject_id'])->max('sort_order') + 1]);
        return back()->with('success', 'Chapter created.');
    }

    public function deleteAcademicGroup(AcademicGroup $group)
    {
        if ($group->subjects()->exists() || $group->exams()->exists()) {
            return back()->with('error', 'Delete the group subjects and exams before deleting this group.');
        }
        $group->delete();
        return back()->with('success', 'Academic group deleted.');
    }

    public function deleteAcademicSubject(AcademicSubject $subject)
    {
        if ($subject->chapters()->exists() || $subject->exams()->exists()) {
            return back()->with('error', 'Delete the subject chapters and exams before deleting this subject.');
        }
        $subject->delete();
        return back()->with('success', 'Subject deleted.');
    }

    public function deleteAcademicChapter(AcademicChapter $chapter)
    {
        if ($chapter->exams()->exists()) {
            return back()->with('error', 'This chapter has exams. Delete those exams before deleting the chapter.');
        }
        $chapter->delete();
        return back()->with('success', 'Chapter deleted.');
    }

    public function storeExam(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|string|unique:exams,exam_id',
            'exam_name' => 'required|string|max:255',
            'creation_mode' => 'nullable|in:structured,manual',
            'academic_group_id' => 'nullable|required_if:creation_mode,structured|exists:academic_groups,id',
            'academic_subject_id' => 'nullable|required_if:creation_mode,structured|exists:academic_subjects,id',
            'chapters' => 'nullable|array|required_if:creation_mode,structured|min:1',
            'chapters.*' => 'integer|exists:academic_chapters,id',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'enable_anti_cheating' => 'nullable|boolean',
            'negative_marking' => 'nullable|numeric|min:0|max:10',
            'pass_percentage' => 'nullable|numeric|min:1|max:100',
            'mcq_pass_percentage' => 'nullable|numeric|min:1|max:100',
            'writing_pass_percentage' => 'nullable|numeric|min:1|max:100',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $mcqPass = $request->input('mcq_pass_percentage', $request->input('pass_percentage', 40.00));
        $writingPass = $request->input('writing_pass_percentage', $request->input('pass_percentage', 40.00));

        $mode = $request->input('creation_mode', 'manual');
        $subject = $mode === 'structured'
            ? AcademicSubject::where('id', $request->academic_subject_id)
                ->where('academic_group_id', $request->academic_group_id)->firstOrFail()
            : null;
        $chapterIds = $mode === 'structured'
            ? AcademicChapter::where('academic_subject_id', $subject->id)
                ->whereIn('id', $request->input('chapters', []))->pluck('id')->all()
            : [];
        if ($mode === 'structured' && count($chapterIds) !== count(array_unique($request->input('chapters', [])))) {
            return back()->withErrors(['chapters' => 'Selected chapters must belong to the chosen subject.'])->withInput();
        }

        $exam = Exam::create([
            'exam_id' => $request->exam_id,
            'exam_name' => $request->exam_name,
            'creation_mode' => $mode,
            'academic_group_id' => $mode === 'structured' ? $request->academic_group_id : null,
            'academic_subject_id' => $mode === 'structured' ? $subject->id : null,
            'description' => $request->description,
            'duration_minutes' => $request->input('duration_minutes', 30),
            'shuffle_questions' => $request->has('shuffle_questions'),
            'shuffle_options' => $request->has('shuffle_options'),
            'enable_anti_cheating' => $request->has('enable_anti_cheating'),
            'negative_marking' => $request->input('negative_marking', 0.00),
            'pass_percentage' => $mcqPass,
            'mcq_pass_percentage' => $mcqPass,
            'writing_pass_percentage' => $writingPass,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'is_active' => false
        ]);
        $exam->chapters()->sync($chapterIds);

        return redirect()->route('admin.add-questions', $exam->id);
    }

    public function editExam($examId)
    {
        $exam = Exam::with('questions')->findOrFail($examId);
        return view('admin.edit-exam', compact('exam'));
    }

    public function updateExam(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);
        
        $request->validate([
            'exam_id' => 'required|string|unique:exams,exam_id,' . $exam->id,
            'exam_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'enable_anti_cheating' => 'nullable|boolean',
            'negative_marking' => 'nullable|numeric|min:0|max:10',
            'pass_percentage' => 'nullable|numeric|min:1|max:100',
            'mcq_pass_percentage' => 'nullable|numeric|min:1|max:100',
            'writing_pass_percentage' => 'nullable|numeric|min:1|max:100',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'is_active' => 'boolean'
        ]);

        $mcqPass = $request->input('mcq_pass_percentage', $request->input('pass_percentage', 40.00));
        $writingPass = $request->input('writing_pass_percentage', $request->input('pass_percentage', 40.00));

        $exam->update([
            'exam_id' => $request->exam_id,
            'exam_name' => $request->exam_name,
            'description' => $request->description,
            'duration_minutes' => $request->input('duration_minutes', 30),
            'shuffle_questions' => $request->has('shuffle_questions'),
            'shuffle_options' => $request->has('shuffle_options'),
            'enable_anti_cheating' => $request->has('enable_anti_cheating'),
            'negative_marking' => $request->input('negative_marking', 0.00),
            'pass_percentage' => $mcqPass,
            'mcq_pass_percentage' => $mcqPass,
            'writing_pass_percentage' => $writingPass,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => $request->has('is_active') ? true : false
        ]);

        return redirect()->route('admin.dashboard')
                        ->with('success', 'Exam updated successfully!');
    }

    public function addQuestions($examId)
    {
        $exam = Exam::findOrFail($examId);
        $questions = $exam->questions()->with('answers')->get();
        return view('admin.add-questions', compact('exam', 'questions'));
    }

    public function storeQuestion(Request $request, $examId)
    {
        // Base validation rules
        $rules = [
            'question_text' => 'required|string',
            'question_type' => 'required|in:single,multiple,file_upload',
            'marks' => 'nullable|numeric|min:0.25|max:100',
            'explanation' => 'nullable|string|max:2000',
        ];

        // Add type-specific validation rules
        if ($request->question_type === 'file_upload') {
            $rules += [
                'file_upload_settings.allowed_extensions' => 'nullable|array',
                'file_upload_settings.allowed_extensions.*' => 'string|in:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt',
                'file_upload_settings.max_size_mb' => 'nullable|integer|min:1|max:100',
            ];
        } else {
            $rules += [
                'answers' => 'required|array|min:2',
                'answers.*' => 'required|string',
                'correct_answers' => 'required|array|min:1',
                'correct_answers.*' => 'required|integer'
            ];
        }

        // Filter out empty answers before validation
        if ($request->has('answers')) {
            $filteredAnswers = array_filter($request->answers, function($answer) {
                return !empty(trim($answer));
            });
            $filteredAnswers = array_values($filteredAnswers); // Re-index array
            $request->merge(['answers' => $filteredAnswers]);
            
            // Adjust correct_answers indices to match filtered answers
            if ($request->has('correct_answers')) {
                $originalAnswers = $request->input('answers');
                $correctAnswersAdjusted = [];
                
                foreach ($request->correct_answers as $originalIndex) {
                    // Find the new index in filtered array
                    $newIndex = 0;
                    $currentIndex = 0;
                    
                    foreach ($originalAnswers as $idx => $answer) {
                        if (!empty(trim($answer))) {
                            if ($idx == $originalIndex) {
                                $correctAnswersAdjusted[] = $newIndex;
                                break;
                            }
                            $newIndex++;
                        }
                    }
                }
                
                $request->merge(['correct_answers' => $correctAnswersAdjusted]);
            }
        }

        $request->validate($rules);

        $exam = Exam::findOrFail($examId);
        
        // Prepare question data
        $questionData = [
            'exam_id' => $exam->id,
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'marks' => $request->input('marks', 1.00),
            'explanation' => $request->explanation,
        ];

        // Add file upload settings if it's a file upload question
        if ($request->question_type === 'file_upload') {
            $questionData['file_upload_settings'] = [
                'allowed_extensions' => $request->input('file_upload_settings.allowed_extensions', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']),
                'max_size_mb' => $request->input('file_upload_settings.max_size_mb', 10),
            ];
        }

        $question = Question::create($questionData);

        // Create answers only for MCQ questions
        if (in_array($request->question_type, ['single', 'multiple'])) {
            foreach ($request->answers as $index => $answerText) {
                if (!empty(trim($answerText))) {
                    Answer::create([
                        'question_id' => $question->id,
                        'answer_text' => $answerText,
                        'is_correct' => in_array($index, $request->correct_answers)
                    ]);
                }
            }
        }

        return redirect()->route('admin.add-questions', $examId)->with('success', 'Question added successfully!');
    }

    public function editQuestion($questionId)
    {
        $question = Question::with(['answers', 'exam'])->findOrFail($questionId);
        return view('admin.edit-question', compact('question'));
    }

    public function updateQuestion(Request $request, $questionId)
    {
        // Base validation rules
        $rules = [
            'question_text' => 'required|string',
            'question_type' => 'required|in:single,multiple,file_upload',
            'marks' => 'nullable|numeric|min:0.25|max:100',
            'explanation' => 'nullable|string|max:2000',
        ];

        // Add type-specific validation rules
        if ($request->question_type === 'file_upload') {
            $rules += [
                'file_upload_settings.allowed_extensions' => 'nullable|array',
                'file_upload_settings.allowed_extensions.*' => 'string|in:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,txt',
                'file_upload_settings.max_size_mb' => 'nullable|integer|min:1|max:100',
            ];
        } else {
            $rules += [
                'answers' => 'required|array|min:2',
                'answers.*' => 'nullable|string',
                'correct_answers' => 'required|array|min:1',
                'correct_answers.*' => 'required|integer'
            ];
        }

        // Filter out empty answers before validation (same logic as storeQuestion)
        if ($request->has('answers')) {
            $filteredAnswers = array_filter($request->answers, function($answer) {
                return !empty(trim($answer));
            });
            $filteredAnswers = array_values($filteredAnswers); // Re-index array
            $request->merge(['answers' => $filteredAnswers]);
            
            // Adjust correct_answers indices to match filtered answers
            if ($request->has('correct_answers')) {
                $originalAnswers = $request->input('answers');
                $correctAnswersAdjusted = [];
                
                foreach ($request->correct_answers as $originalIndex) {
                    // Find the new index in filtered array
                    $newIndex = 0;
                    $currentIndex = 0;
                    
                    foreach ($originalAnswers as $idx => $answer) {
                        if (!empty(trim($answer))) {
                            if ($idx == $originalIndex) {
                                $correctAnswersAdjusted[] = $newIndex;
                                break;
                            }
                            $newIndex++;
                        }
                    }
                }
                
                $request->merge(['correct_answers' => $correctAnswersAdjusted]);
            }
        }

        $request->validate($rules);

        $question = Question::with(['answers', 'exam'])->findOrFail($questionId);
        
        // Prepare update data
        $updateData = [
            'question_text' => $request->question_text,
            'question_type' => $request->question_type,
            'marks' => $request->input('marks', 1.00),
            'explanation' => $request->explanation,
        ];

        // Add file upload settings if it's a file upload question
        if ($request->question_type === 'file_upload') {
            $updateData['file_upload_settings'] = [
                'allowed_extensions' => $request->input('file_upload_settings.allowed_extensions', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']),
                'max_size_mb' => $request->input('file_upload_settings.max_size_mb', 10),
            ];
        } else {
            $updateData['file_upload_settings'] = null;
        }

        // Update question
        $question->update($updateData);

        // Handle answers based on question type
        if ($request->question_type === 'file_upload') {
            // Remove all existing answers for file upload questions
            $question->answers()->delete();
        } else {
            // Delete existing answers and create new ones for MCQ questions
            $question->answers()->delete();

            // Create new answers
            foreach ($request->answers as $index => $answerText) {
                if (!empty(trim($answerText))) {
                    Answer::create([
                        'question_id' => $question->id,
                        'answer_text' => $answerText,
                        'is_correct' => in_array($index, $request->correct_answers)
                    ]);
                }
            }
        }

        return redirect()->route('admin.add-questions', $question->exam_id)
                        ->with('success', 'Question updated successfully!');
    }

    public function gradeFileSubmission(Request $request, $studentAnswerId)
    {
        $studentAnswer = StudentAnswer::with(['question', 'examResult'])->findOrFail($studentAnswerId);

        if (!$studentAnswer->question->isFileUpload()) {
            return back()->with('error', 'This is not a writing / file upload question.');
        }

        $this->persistWritingGrade($request, $studentAnswer, $studentAnswer->question);

        return back()->with('success', 'Marks and feedback sent back to the student.');
    }

    public function gradeWritingQuestion(Request $request, $examResultId, $questionId)
    {
        $examResult = ExamResult::with('exam')->findOrFail($examResultId);
        $question = Question::where('exam_id', $examResult->exam_id)->findOrFail($questionId);

        if (!$question->isFileUpload()) {
            return back()->with('error', 'This is not a writing / file upload question.');
        }

        $studentAnswer = StudentAnswer::firstOrCreate(
            [
                'exam_result_id' => $examResult->id,
                'question_id' => $question->id,
            ],
            [
                'answer_id' => null,
                'is_graded' => false,
            ]
        );

        $this->persistWritingGrade($request, $studentAnswer, $question);

        return back()->with('success', 'Marks and feedback sent back to the student.');
    }

    private function persistWritingGrade(Request $request, StudentAnswer $studentAnswer, Question $question): void
    {
        $maxMarks = (float) ($question->marks ?? 1);

        $request->validate([
            'manual_score' => 'required|numeric|min:0|max:' . $maxMarks,
            'admin_feedback' => 'nullable|string|max:5000',
            'annotated_file' => 'nullable|file|max:20480',
            'annotated_image' => 'nullable|string',
        ]);

        $payload = [
            'manual_score' => $request->manual_score,
            'admin_feedback' => $request->admin_feedback,
            'is_graded' => true,
            'feedback_released_at' => now(),
        ];

        if ($request->hasFile('annotated_file') && $request->file('annotated_file')->isValid()) {
            if ($studentAnswer->annotated_file_path) {
                Storage::disk('public')->delete($studentAnswer->annotated_file_path);
            }
            $file = $request->file('annotated_file');
            $filename = 'marked_' . $studentAnswer->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $payload['annotated_file_path'] = $file->storeAs('exam_feedback', $filename, 'public');
            $payload['annotated_original_filename'] = $file->getClientOriginalName();
        } elseif ($request->filled('annotated_image') && str_starts_with($request->annotated_image, 'data:image/')) {
            $raw = $request->annotated_image;
            if (preg_match('/^data:image\/(\w+);base64,/', $raw, $matches)) {
                $raw = substr($raw, strpos($raw, ',') + 1);
                $binary = base64_decode($raw);
                if ($binary !== false && strlen($binary) <= 15 * 1024 * 1024) {
                    if ($studentAnswer->annotated_file_path) {
                        Storage::disk('public')->delete($studentAnswer->annotated_file_path);
                    }
                    $ext = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
                    $path = 'exam_feedback/marked_' . $studentAnswer->id . '_' . time() . '.' . $ext;
                    Storage::disk('public')->put($path, $binary);
                    $payload['annotated_file_path'] = $path;
                    $payload['annotated_original_filename'] = 'teacher-marked.' . $ext;
                }
            }
        }

        $studentAnswer->update($payload);
        $studentAnswer->examResult->recalculateScore();
    }

    public function gradeFileSubmissionPage($studentAnswerId)
    {
        $studentAnswer = StudentAnswer::with('examResult')->findOrFail($studentAnswerId);

        return redirect()->route('admin.grade-submissions', $studentAnswer->examResult->exam_id);
    }

    public function gradeSubmissions(Request $request, $examId)
    {
        $exam = Exam::with(['questions'])->findOrFail($examId);

        $fileUploadQuestions = $exam->questions->where('question_type', 'file_upload')->values();

        if ($fileUploadQuestions->isEmpty()) {
            return redirect()->route('admin.exam-results', $examId)
                           ->with('error', 'This exam has no writing / file upload questions to grade.');
        }

        $fileQuestionIds = $fileUploadQuestions->pluck('id');

        $submissions = ExamResult::where('exam_id', $examId)
            ->with(['studentAnswers' => function ($query) use ($fileQuestionIds) {
                $query->whereIn('question_id', $fileQuestionIds)->with('question');
            }])
            ->orderBy('submitted_at', 'desc')
            ->get();

        if ($request->boolean('ungraded')) {
            $submissions = $submissions->filter(function ($submission) use ($fileQuestionIds) {
                foreach ($fileQuestionIds as $questionId) {
                    $answer = $submission->studentAnswers->firstWhere('question_id', $questionId);
                    if (!$answer || !$answer->is_graded) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        return view('admin.grade-submissions', compact('exam', 'fileUploadQuestions', 'submissions'));
    }

    public function gradeDesk(Request $request, $examId, $examResultId)
    {
        $exam = Exam::with(['questions'])->findOrFail($examId);
        $fileUploadQuestions = $exam->questions->where('question_type', 'file_upload')->values();

        if ($fileUploadQuestions->isEmpty()) {
            return redirect()->route('admin.exam-results', $examId)
                ->with('error', 'This exam has no writing questions to grade.');
        }

        $fileQuestionIds = $fileUploadQuestions->pluck('id');

        $submissions = ExamResult::where('exam_id', $examId)
            ->with(['studentAnswers' => function ($query) use ($fileQuestionIds) {
                $query->whereIn('question_id', $fileQuestionIds)->with('question');
            }])
            ->orderBy('submitted_at', 'desc')
            ->get();

        $examResult = $submissions->firstWhere('id', (int) $examResultId);
        if (!$examResult) {
            return redirect()->route('admin.grade-submissions', $examId)
                ->with('error', 'Student submission not found.');
        }

        $activeQuestionId = (int) $request->query('question', $fileUploadQuestions->first()->id);
        $activeQuestion = $fileUploadQuestions->firstWhere('id', $activeQuestionId) ?? $fileUploadQuestions->first();
        $studentAnswer = $examResult->studentAnswers->firstWhere('question_id', $activeQuestion->id);

        $currentIndex = $submissions->search(fn ($s) => $s->id === $examResult->id);
        $prevSubmission = $currentIndex > 0 ? $submissions[$currentIndex - 1] : null;
        $nextSubmission = ($currentIndex !== false && $currentIndex < $submissions->count() - 1)
            ? $submissions[$currentIndex + 1]
            : null;

        return view('admin.grade-desk', compact(
            'exam',
            'fileUploadQuestions',
            'submissions',
            'examResult',
            'activeQuestion',
            'studentAnswer',
            'prevSubmission',
            'nextSubmission'
        ));
    }

    public function deleteQuestion($questionId)
    {
        $question = Question::with(['exam', 'studentAnswers'])->findOrFail($questionId);
        
        // Check if this question has been answered by students
        if ($question->studentAnswers()->count() > 0) {
            return redirect()->route('admin.add-questions', $question->exam_id)
                           ->with('error', 'Cannot delete question that has been answered by students.');
        }

        // Delete the question (answers will be deleted automatically due to foreign key constraints)
        $examId = $question->exam_id;
        $question->delete();

        return redirect()->route('admin.add-questions', $examId)
                        ->with('success', 'Question deleted successfully!');
    }

    public function importQuestions(Request $request, $examId)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $exam = Exam::findOrFail($examId);

        try {
            $import = new \App\Imports\QuestionsImport($exam->id);
            Excel::import($import, $request->file('file'));

            return redirect()->route('admin.add-questions', $exam->id)
                ->with('success', "Successfully imported {$import->importedCount} questions from file!");
        } catch (\Exception $e) {
            return redirect()->route('admin.add-questions', $exam->id)
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadQuestionTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions Template');

        $headers = [
            'A1' => 'Question',
            'B1' => 'Type',
            'C1' => 'Option A',
            'D1' => 'Option B',
            'E1' => 'Option C',
            'D1' => 'Option D',
            'G1' => 'Correct Answers',
            'H1' => 'Marks',
            'I1' => 'Explanation'
        ];
        $headers = [
            'A1' => 'question',
            'B1' => 'type',
            'C1' => 'option_a',
            'D1' => 'option_b',
            'E1' => 'option_c',
            'F1' => 'option_d',
            'G1' => 'correct_answers',
            'H1' => 'marks',
            'I1' => 'explanation'
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Sample Row 1 (Single choice)
        $sheet->setCellValue('A2', 'What is the capital city of France?');
        $sheet->setCellValue('B2', 'single');
        $sheet->setCellValue('C2', 'London');
        $sheet->setCellValue('D2', 'Paris');
        $sheet->setCellValue('E2', 'Rome');
        $sheet->setCellValue('F2', 'Berlin');
        $sheet->setCellValue('G2', 'B');
        $sheet->setCellValue('H2', 1);
        $sheet->setCellValue('I2', 'Paris is the capital of France.');

        // Sample Row 2 (Multiple choice)
        $sheet->setCellValue('A3', 'Which of the following are primary colors?');
        $sheet->setCellValue('B3', 'multiple');
        $sheet->setCellValue('C3', 'Red');
        $sheet->setCellValue('D3', 'Green');
        $sheet->setCellValue('E3', 'Blue');
        $sheet->setCellValue('F3', 'Yellow');
        $sheet->setCellValue('G3', 'A,C,D');
        $sheet->setCellValue('H3', 2);
        $sheet->setCellValue('I3', 'Red, Yellow, and Blue are primary colors.');

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'questions_bulk_import_template.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function toggleExamStatus($examId)
    {
        $exam = Exam::findOrFail($examId);
        $exam->update(['is_active' => !$exam->is_active]);
        
        $status = $exam->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.dashboard')->with('success', "Exam {$status} successfully!");
    }

    public function examResults(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);
        
        $query = ExamResult::where('exam_id', $exam->id)
                          ->with('studentAnswers.question', 'studentAnswers.answer', 'exam.questions.answers');
        
        // Apply search filter if provided - search both student_id and index_no
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('student_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('index_no', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // Get total count for all data (without pagination)
        $totalCount = ExamResult::where('exam_id', $exam->id)->count();
        
        // Get filtered count for search results
        $filteredCount = $query->count();
        
        // Calculate average score for all data (not just current page)
        $averageScore = ExamResult::where('exam_id', $exam->id)->avg('score') ?? 0;

        // Apply pagination - 20 items per page
        $results = $query->orderBy('submitted_at', 'desc')->paginate(20);

        // Append search parameters to pagination links
        $results->appends($request->query());

        // Keep search value for the form
        $searchData = [
            'search' => $request->search,
        ];

        // ── Analytics data for Chart.js ────────────────────────────────────
        $allResults = ExamResult::where('exam_id', $exam->id)
            ->with(['exam.questions.answers', 'studentAnswers.question', 'studentAnswers.answer'])
            ->get();

        $rankedResults = $allResults
            ->sortByDesc('submitted_at')
            ->groupBy('student_id')
            ->map(fn ($studentResults) => $studentResults->first())
            ->filter(fn ($result) => $result->isRankEligible() && $result->evaluateSections()['overall_ready'])
            ->sort(function ($first, $second) {
                $scoreOrder = (float) $second->score <=> (float) $first->score;

                return $scoreOrder ?: ($first->submitted_at->timestamp <=> $second->submitted_at->timestamp);
            })
            ->values();

        $lastRank = 0;
        $lastScore = null;
        $rankedResults->each(function ($result, $index) use (&$lastRank, &$lastScore) {
            $score = (float) $result->score;
            if ($lastScore === null || $score < $lastScore) {
                $lastRank = $index + 1;
            }
            $result->rank = $lastRank;
            $lastScore = $score;
        });

        $rankByResultId = $rankedResults->pluck('rank', 'id');
        $scheduledSubmissionCount = $allResults->filter(fn ($result) => $result->isRankEligible())->count();

        // Score distribution: 10 percentage buckets
        $buckets = array_fill(0, 10, 0);
        $passCount = 0;
        $failCount = 0;
        $pendingCount = 0;
        foreach ($allResults as $r) {
            $s = $r->evaluateSections();
            if (!$s['overall_ready']) {
                $pendingCount++;
                continue;
            }
            $pct = $s['overall_percentage'] ?? 0;
            $idx = min(9, (int) floor($pct / 10));
            $buckets[$idx]++;
            $s['overall_passed'] ? $passCount++ : $failCount++;
        }
        $scoreDistributionLabels = ['0-9%','10-19%','20-29%','30-39%','40-49%','50-59%','60-69%','70-79%','80-89%','90-100%'];
        $scoreDistributionData   = $buckets;

        // Submissions over time
        $submissionsOverTime = ExamResult::where('exam_id', $exam->id)
            ->selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return view('admin.exam-results', compact(
            'exam', 'results', 'searchData', 'totalCount', 'filteredCount', 'averageScore',
            'scoreDistributionLabels', 'scoreDistributionData', 'rankedResults', 'rankByResultId', 'scheduledSubmissionCount',
            'passCount', 'failCount', 'pendingCount', 'submissionsOverTime'
        ));
    }
    public function printResults($examId)
    {
        $exam = Exam::with('questions')->findOrFail($examId);
        $results = ExamResult::where('exam_id', $exam->id)
            ->with(['exam.questions.answers', 'studentAnswers.question', 'studentAnswers.answer'])
            ->orderBy('score', 'desc')
            ->get();
        return view('admin.print-results', compact('exam', 'results'));
    }

    public function printMarksheet($resultId)
    {
        $examResult = ExamResult::with(['studentAnswers.question.answers', 'studentAnswers.answer', 'exam.questions.answers'])->findOrFail($resultId);
        $exam = $examResult->exam;
        return view('admin.print-marksheet', compact('exam', 'examResult'));
    }

    public function downloadResults($examId)
    {
        try {
            $exam = Exam::findOrFail($examId);
            
            // Get all results for this exam
            $results = ExamResult::where('exam_id', $exam->id)
                                ->with('studentAnswers.question', 'studentAnswers.answer')
                                ->orderBy('submitted_at', 'desc')
                                ->get();
            
            if ($results->isEmpty()) {
                return redirect()->back()->with('error', 'No results found for this exam.');
            }

            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Disable automatic calculation to prevent formula issues
            $spreadsheet->getCalculationEngine()->disableCalculationCache();
            \PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance($spreadsheet)->setCalculationCacheEnabled(false);
            
            // Set document properties
            $spreadsheet->getProperties()
                       ->setCreator(config('brand.name'))
                       ->setTitle($exam->exam_name . ' - Results')
                       ->setSubject('Exam Results')
                       ->setDescription('Detailed exam results for ' . $exam->exam_name);

            // Sheet title
            $sheet->setTitle('Exam Results');
        
        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Exam info section
        $this->setSafeStringValue($sheet, 'A1', 'Exam Name:');
        $this->setSafeStringValue($sheet, 'B1', $exam->exam_name);
        $this->setSafeStringValue($sheet, 'A2', 'Exam ID:');
        $this->setSafeStringValue($sheet, 'B2', $exam->exam_id);
        $this->setSafeStringValue($sheet, 'A3', 'Total Submissions:');
        $sheet->setCellValue('B3', $results->count());
        $this->setSafeStringValue($sheet, 'A4', 'Average Score:');
        $sheet->setCellValue('B4', round($results->avg('score'), 2));
        $this->setSafeStringValue($sheet, 'A5', 'Generated On:');
        $this->setSafeStringValue($sheet, 'B5', now()->format('Y-m-d H:i:s'));

        // Style exam info
        $sheet->getStyle('A1:A5')->getFont()->setBold(true);
        $sheet->getStyle('A1:B5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Summary header row (starting from row 7)
        $summaryRow = 7;
        $headers = [
            'A' => 'Student ID',
            'B' => 'Index Number', 
            'C' => 'Score',
            'D' => 'Correct Answers',
            'E' => 'Total Questions',
            'F' => 'Percentage',
            'G' => 'Time Taken',
            'H' => 'Tab Violations',
            'I' => 'Submitted At'
        ];

        foreach ($headers as $col => $header) {
            $this->setSafeStringValue($sheet, $col . $summaryRow, $header);
        }
        
        $sheet->getStyle('A' . $summaryRow . ':I' . $summaryRow)->applyFromArray($headerStyle);

        // Fill summary data
        $row = $summaryRow + 1;
        foreach ($results as $result) {
            $percentage = $result->total_questions > 0 ? round(($result->correct_answers / $result->total_questions) * 100, 1) : 0;
            $timeTaken = $result->time_taken_seconds ? (floor($result->time_taken_seconds / 60) . 'm ' . ($result->time_taken_seconds % 60) . 's') : 'N/A';
            
            $this->setSafeStringValue($sheet, 'A' . $row, $result->student_id);
            $this->setSafeStringValue($sheet, 'B' . $row, $result->index_no);
            $sheet->setCellValue('C' . $row, $result->score);
            $sheet->setCellValue('D' . $row, $result->correct_answers);
            $sheet->setCellValue('E' . $row, $result->total_questions);
            $this->setSafeStringValue($sheet, 'F' . $row, $percentage . '%');
            $this->setSafeStringValue($sheet, 'G' . $row, $timeTaken);
            $sheet->setCellValue('H' . $row, $result->tab_switch_count ?? 0);
            $this->setSafeStringValue($sheet, 'I' . $row, $result->submitted_at->format('Y-m-d H:i:s'));
            
            // Style data rows
            $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
            }
            
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add detailed answers sheet
        $detailSheet = $spreadsheet->createSheet(1);
        $detailSheet->setTitle('Detailed Answers');
        
        $detailRow = 1;
        
        // Detailed header
        $detailHeaders = [
            'A' => 'Student ID',
            'B' => 'Index Number',
            'C' => 'Question #',
            'D' => 'Question Text',
            'E' => 'Question Type',
            'F' => 'Student Answer',
            'G' => 'Correct Answer',
            'H' => 'Is Correct',
            'I' => 'File Submission',
            'J' => 'File Size',
            'K' => 'Grading Status',
            'L' => 'Graded Score',
            'M' => 'Grading Notes'
        ];

        foreach ($detailHeaders as $col => $header) {
            $this->setSafeStringValue($detailSheet, $col . $detailRow, $header);
        }
        
        $detailSheet->getStyle('A' . $detailRow . ':M' . $detailRow)->applyFromArray($headerStyle);

        $detailRow++;

        // Fill detailed data
        foreach ($results as $result) {
            foreach ($result->studentAnswers as $index => $studentAnswer) {
                $question = $studentAnswer->question;
                $isFileUpload = $question->isFileUpload();
                
                $this->setSafeStringValue($detailSheet, 'A' . $detailRow, $result->student_id);
                $this->setSafeStringValue($detailSheet, 'B' . $detailRow, $result->index_no);
                $detailSheet->setCellValue('C' . $detailRow, $index + 1);
                $this->setSafeStringValue($detailSheet, 'D' . $detailRow, $question->question_text);
                $this->setSafeStringValue($detailSheet, 'E' . $detailRow, $isFileUpload ? 'File Upload' : 'Multiple Choice');
                
                if ($isFileUpload) {
                    // Handle file upload questions
                    $this->setSafeStringValue($detailSheet, 'F' . $detailRow, 'File Uploaded');
                    $this->setSafeStringValue($detailSheet, 'G' . $detailRow, 'Manual Grading Required');
                    $this->setSafeStringValue($detailSheet, 'H' . $detailRow, $studentAnswer->is_graded ?
                        ((float)$studentAnswer->manual_score >= (float)($question->marks ?? 1) ? 'Full marks' : 'Graded') : 'Pending');
                    
                    // File submission details
                    if ($studentAnswer->file_path) {
                        $fileUrl = url('storage/' . $studentAnswer->file_path);
                        $this->setSafeStringValue($detailSheet, 'I' . $detailRow, $fileUrl);
                        $this->setSafeStringValue($detailSheet, 'J' . $detailRow, $studentAnswer->getFormattedFileSize());
                    } else {
                        $this->setSafeStringValue($detailSheet, 'I' . $detailRow, 'No file submitted');
                        $this->setSafeStringValue($detailSheet, 'J' . $detailRow, '');
                    }
                    
                    $this->setSafeStringValue($detailSheet, 'K' . $detailRow, 
                        $studentAnswer->is_graded ? 'Graded' : 'Pending');
                    $this->setSafeStringValue($detailSheet, 'L' . $detailRow,
                        $studentAnswer->is_graded ? $studentAnswer->manual_score : '');
                    $this->setSafeStringValue($detailSheet, 'M' . $detailRow,
                        $studentAnswer->admin_feedback ?? '');
                } else {
                    // Handle MCQ questions
                    $correctAnswer = $question->answers->where('is_correct', true)->first();
                    
                    $this->setSafeStringValue($detailSheet, 'F' . $detailRow, 
                        $studentAnswer->answer ? $studentAnswer->answer->answer_text : 'No answer');
                    $this->setSafeStringValue($detailSheet, 'G' . $detailRow, 
                        $correctAnswer ? $correctAnswer->answer_text : 'N/A');
                        $this->setSafeStringValue($detailSheet, 'H' . $detailRow, 
                        ($studentAnswer->answer && $studentAnswer->answer->is_correct) ? 'Correct' : 'Incorrect');
                    
                    // Empty file-related columns for MCQ
                    $this->setSafeStringValue($detailSheet, 'I' . $detailRow, '');
                    $this->setSafeStringValue($detailSheet, 'J' . $detailRow, '');
                    $this->setSafeStringValue($detailSheet, 'K' . $detailRow, 'Auto-graded');
                    $this->setSafeStringValue($detailSheet, 'L' . $detailRow, $studentAnswer->is_correct ? '1' : '0');
                    $this->setSafeStringValue($detailSheet, 'M' . $detailRow, '');
                }
                
                // Style detail rows
                $detailSheet->getStyle('A' . $detailRow . ':M' . $detailRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Color code correct/incorrect answers
                if ($isFileUpload) {
                    if ($studentAnswer->is_graded) {
                        if ($studentAnswer->is_correct) {
                            $detailSheet->getStyle('H' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
                        } else {
                            $detailSheet->getStyle('H' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
                        }
                    } else {
                        $detailSheet->getStyle('H' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEB9C');
                    }
                } else {
                    if ($studentAnswer->is_correct) {
                        $detailSheet->getStyle('H' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
                    } else {
                        $detailSheet->getStyle('H' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFC7CE');
                    }
                }
                
                if ($detailRow % 2 == 0) {
                    $detailSheet->getStyle('A' . $detailRow . ':M' . $detailRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
                }
                
                $detailRow++;
            }
        }

        // Auto-size columns for detail sheet
        foreach (range('A', 'M') as $column) {
            $detailSheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Generate filename
        $filename = 'exam_results_' . $exam->exam_id . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Create writer and save to output
        $writer = new Xlsx($spreadsheet);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        // Save to php://output
        $writer->save('php://output');
        
        exit;
        
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Excel download error for exam ID ' . $examId . ': ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'An error occurred while generating the Excel file. Please try again.');
        }
    }

    private function setSafeStringValue($sheet, $cell, $value)
    {
        // Handle null or empty values
        if ($value === null) {
            $value = '';
        }
        
        // Convert value to string and escape any potential formula characters
        $safeValue = (string) $value;
        
        // Trim whitespace
        $safeValue = trim($safeValue);
        
        // If the value starts with =, +, -, @ or has formula-like content, prepend with single quote
        if (preg_match('/^[=+\-@]/', $safeValue) || strpos($safeValue, '=') !== false) {
            $safeValue = "'" . $safeValue;
        }
        
        // Remove or escape any problematic characters that might cause issues
        $safeValue = str_replace(["\r\n", "\r", "\n"], " ", $safeValue); // Replace line breaks with spaces
        
        $sheet->setCellValueExplicit($cell, $safeValue, DataType::TYPE_STRING);
    }

    // =========================================================
    // Phase 4: Delete Exam
    // =========================================================
    public function deleteExam($examId)
    {
        $exam = Exam::findOrFail($examId);
        $examName = $exam->exam_name;

        \Illuminate\Support\Facades\DB::transaction(function () use ($exam) {
            $questionIds = $exam->questions()->pluck('id');
            $resultIds = $exam->examResults()->pluck('id');

            foreach ($exam->examResults()->with('studentAnswers')->get() as $result) {
                foreach ($result->studentAnswers as $studentAnswer) {
                    if ($studentAnswer->file_path) {
                        Storage::disk('public')->delete($studentAnswer->file_path);
                    }
                    if ($studentAnswer->annotated_file_path) {
                        Storage::disk('public')->delete($studentAnswer->annotated_file_path);
                    }
                }

                Cache::forget('student-login-' . $exam->id . '-' . $result->student_id);
            }

            StudentAnswer::whereIn('exam_result_id', $resultIds)
                ->orWhereIn('question_id', $questionIds)
                ->delete();
            $exam->examResults()->delete();
            Answer::whereIn('question_id', $questionIds)->delete();
            $exam->questions()->delete();
            $exam->delete();
        });

        return redirect()->route('admin.dashboard')
                        ->with('success', "Exam \"{$examName}\" and all related student data have been permanently deleted.");
    }

    // =========================================================
    // Phase 4: Clone Exam
    // =========================================================
    public function cloneExam($examId)
    {
        $original = Exam::with(['questions.answers'])->findOrFail($examId);

        // Generate a unique exam_id
        $baseId  = $original->exam_id . '_copy';
        $newId   = $baseId;
        $counter = 1;
        while (Exam::where('exam_id', $newId)->exists()) {
            $newId = $baseId . '_' . $counter++;
        }

        $cloned = Exam::create([
            'exam_id'              => $newId,
            'exam_name'            => 'Copy of ' . $original->exam_name,
            'description'          => $original->description,
            'duration_minutes'     => $original->duration_minutes,
            'shuffle_questions'    => $original->shuffle_questions,
            'shuffle_options'      => $original->shuffle_options,
            'enable_anti_cheating' => $original->enable_anti_cheating,
            'negative_marking'     => $original->negative_marking,
            'pass_percentage'      => $original->pass_percentage,
            'mcq_pass_percentage'  => $original->mcq_pass_percentage,
            'writing_pass_percentage' => $original->writing_pass_percentage,
            'start_time'           => null,
            'end_time'             => null,
            'created_by'           => \Illuminate\Support\Facades\Auth::id(),
            'is_active'            => false,
        ]);

        foreach ($original->questions as $question) {
            $newQuestion = \App\Models\Question::create([
                'exam_id'              => $cloned->id,
                'question_text'        => $question->question_text,
                'question_type'        => $question->question_type,
                'marks'                => $question->marks,
                'explanation'          => $question->explanation,
                'file_upload_settings' => $question->file_upload_settings,
            ]);

            foreach ($question->answers as $answer) {
                \App\Models\Answer::create([
                    'question_id' => $newQuestion->id,
                    'answer_text' => $answer->answer_text,
                    'is_correct'  => $answer->is_correct,
                ]);
            }
        }

        return redirect()->route('admin.add-questions', $cloned->id)
                        ->with('success', "Exam cloned successfully! Review and activate when ready.");
    }

    // =========================================================
    // Phase 6: Delete Student Result (allow re-take)
    // =========================================================
    public function deleteResult($resultId)
    {
        $result = ExamResult::with(['studentAnswers', 'exam'])->findOrFail($resultId);
        $examId = $result->exam_id;

        // Release the login lock so student can retake
        $cacheKey = 'student-login-' . $result->exam_id . '-' . $result->student_id;
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        $result->studentAnswers()->delete();
        $result->delete();

        return redirect()->route('admin.exam-results', $examId)
                        ->with('success', "Result for student \"{$result->student_id}\" deleted. They can now retake the exam.");
    }

    // =========================================================
    // Phase 6: Export Results as CSV
    // =========================================================
    public function exportResultsCsv($examId)
    {
        $exam    = Exam::findOrFail($examId);
        $results = ExamResult::where('exam_id', $exam->id)
            ->with(['exam.questions.answers', 'studentAnswers.question', 'studentAnswers.answer'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        $filename = 'results_' . $exam->exam_id . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($results, $exam) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Exam Name:', $exam->exam_name]);
            fputcsv($handle, ['Exam ID:', $exam->exam_id]);
            fputcsv($handle, ['MCQ Pass %:', $exam->mcqPassPercentage() . '%']);
            fputcsv($handle, ['Writing Pass %:', $exam->writingPassPercentage() . '%']);
            fputcsv($handle, ['Generated On:', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Student ID', 'Index No', 'MCQ Score', 'MCQ Total', 'MCQ %', 'MCQ Pass/Fail',
                'Writing Score', 'Writing Total', 'Writing %', 'Writing Pass/Fail',
                'Overall Score', 'Total Marks', 'Overall %', 'Overall Pass/Fail',
                'Time Taken', 'Tab Violations', 'Submitted At'
            ]);

            foreach ($results as $r) {
                $s = $r->evaluateSections();
                $overall = !$s['overall_ready'] ? 'PENDING' : ($s['overall_passed'] ? 'PASS' : 'FAIL');
                $mcqStatus = !$s['has_mcq'] ? 'N/A' : ($s['mcq_passed'] ? 'PASS' : 'FAIL');
                $writeStatus = !$s['has_writing'] ? 'N/A' : (!$s['writing_fully_graded'] ? 'PENDING' : ($s['writing_passed'] ? 'PASS' : 'FAIL'));
                $minutes = $r->time_taken_seconds ? floor($r->time_taken_seconds / 60) . 'm ' . ($r->time_taken_seconds % 60) . 's' : 'N/A';

                fputcsv($handle, [
                    $r->student_id,
                    $r->index_no,
                    $s['mcq_obtained'],
                    $s['mcq_total'],
                    ($s['mcq_percentage'] ?? '—') . ($s['mcq_percentage'] !== null ? '%' : ''),
                    $mcqStatus,
                    $s['writing_fully_graded'] ? $s['writing_obtained'] : 'Pending',
                    $s['writing_total'],
                    $s['writing_percentage'] !== null ? $s['writing_percentage'] . '%' : 'Pending',
                    $writeStatus,
                    $s['overall_ready'] ? $s['obtained'] : 'Pending',
                    $s['total'],
                    $s['overall_percentage'] !== null ? $s['overall_percentage'] . '%' : 'Pending',
                    $overall,
                    $minutes,
                    $r->tab_switch_count ?? 0,
                    $r->submitted_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================
    // Phase 6: Multi-Admin Management
    // =========================================================
    public function manageAdmins()
    {
        $admins = \App\Models\User::where('role', 'admin')->orderBy('created_at', 'desc')->get();
        return view('admin.manage-admins', compact('admins'));
    }

    public function manageStudents()
    {
        $students = \App\Models\User::whereIn('role', ['student', 'inactive_student'])
            ->latest()->get();
        return view('admin.students', compact('students'));
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string|max:255',
            'college' => 'nullable|string|max:255',
            'student_group' => 'nullable|in:Science,Arts,Commerce',
            'whatsapp' => 'nullable|string|max:30',
            'index_no' => 'nullable|string|max:100',
        ]);

        $data['email'] = $data['username'] . '@student.local';
        $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        $data['role'] = 'student';
        \App\Models\User::create($data);

        return redirect()->route('admin.students')->with('success', "Student account for \"{$data['name']}\" created successfully.");
    }

    public function deleteStudent($userId)
    {
        $student = \App\Models\User::whereIn('role', ['student', 'inactive_student'])->findOrFail($userId);
        $student->delete();
        return redirect()->route('admin.students')->with('success', 'Student account deleted.');
    }

    public function toggleStudentStatus($userId)
    {
        $student = \App\Models\User::whereIn('role', ['student', 'inactive_student'])->findOrFail($userId);
        $student->role = $student->role === 'student' ? 'inactive_student' : 'student';
        $student->save();
        return redirect()->route('admin.students')->with('success', 'Student account status updated.');
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        \App\Models\User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role'     => 'admin',
        ]);

        return redirect()->route('admin.manage-admins')
                        ->with('success', "Admin account for \"{$request->name}\" created successfully.");
    }

    public function deleteAdmin($userId)
    {
        $currentUserId = \Illuminate\Support\Facades\Auth::id();
        if ($userId == $currentUserId) {
            return redirect()->route('admin.manage-admins')
                            ->with('error', 'You cannot delete your own account.');
        }

        $user = \App\Models\User::where('role', 'admin')->findOrFail($userId);
        $user->delete();

        return redirect()->route('admin.manage-admins')
                        ->with('success', "Admin \"{$user->name}\" has been deleted.");
    }

    public function toggleAdminStatus($userId)
    {
        $currentUserId = \Illuminate\Support\Facades\Auth::id();
        if ($userId == $currentUserId) {
            return redirect()->route('admin.manage-admins')
                            ->with('error', 'You cannot change your own status.');
        }

        $user = \App\Models\User::where('role', 'admin')->findOrFail($userId);
        // We'll use the existing `is_active` field if it exists; otherwise we use a workaround via role
        // Toggle using a simple flag — store inactive state by prefixing role
        if (str_starts_with($user->role, 'inactive_')) {
            $user->role = 'admin';
            $msg = "Admin \"{$user->name}\" activated.";
        } else {
            $user->role = 'inactive_admin';
            $msg = "Admin \"{$user->name}\" deactivated.";
        }
        $user->save();

        return redirect()->route('admin.manage-admins')->with('success', $msg);
    }
}
