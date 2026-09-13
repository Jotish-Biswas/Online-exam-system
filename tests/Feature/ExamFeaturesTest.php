<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\Answer;
use App\Models\ExamResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Phase 1 Tests ────────────────────────────────────────────────────────────

test('admin can create exam with duration and security settings', function () {
    $response = $this->withSession(['admin_logged_in' => true])
        ->post(route('admin.store-exam'), [
            'exam_id'              => 'TEST101',
            'exam_name'            => 'Automated Test Exam',
            'description'          => 'Test description',
            'duration_minutes'     => 45,
            'shuffle_questions'    => 1,
            'shuffle_options'      => 1,
            'enable_anti_cheating' => 1,
        ]);

    $exam = Exam::where('exam_id', 'TEST101')->first();
    expect($exam)->not->toBeNull()
        ->and($exam->duration_minutes)->toBe(45)
        ->and($exam->shuffle_questions)->toBeTrue()
        ->and($exam->shuffle_options)->toBeTrue()
        ->and($exam->enable_anti_cheating)->toBeTrue();
});

test('student can authenticate, view exam and submit with partial answers', function () {
    $exam = Exam::create([
        'exam_id'              => 'FLOW202',
        'exam_name'            => 'Student Flow Exam',
        'duration_minutes'     => 30,
        'shuffle_questions'    => true,
        'shuffle_options'      => true,
        'enable_anti_cheating' => true,
        'is_active'            => true,
    ]);

    $q1 = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => 'What is 2 + 2?',
        'question_type' => 'single',
    ]);
    $a1Correct = Answer::create([
        'question_id' => $q1->id,
        'answer_text' => '4',
        'is_correct'  => true,
    ]);
    Answer::create([
        'question_id' => $q1->id,
        'answer_text' => '5',
        'is_correct'  => false,
    ]);

    $q2 = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => 'What is 3 + 3?',
        'question_type' => 'single',
    ]);
    Answer::create([
        'question_id' => $q2->id,
        'answer_text' => '6',
        'is_correct'  => true,
    ]);

    // Student Login
    $loginResp = $this->post(route('student.authenticate'), [
        'exam_id'    => 'FLOW202',
        'student_id' => 'STU_TEST_01',
        'index_no'   => 'IND_TEST_01',
    ]);
    $loginResp->assertRedirect(route('student.exam-preview', $exam->id));

    // Access Exam Room
    $examResp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_TEST_01',
        'index_no'        => 'IND_TEST_01',
        'exam_started_at' => now()->timestamp,
    ])->get(route('student.exam', $exam->id));

    $examResp->assertStatus(200);
    $examResp->assertSee('Question Palette');
    $examResp->assertSee('timerDisplay');

    // Submit Exam with only Question 1 answered (Question 2 skipped/unanswered)
    $submitResp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_TEST_01',
        'index_no'        => 'IND_TEST_01',
        'exam_started_at' => now()->subMinutes(5)->timestamp,
    ])->post(route('student.submit-exam', $exam->id), [
        'answers' => [
            $q1->id => $a1Correct->id,
            // q2 intentionally omitted (skipped)
        ],
        'tab_switch_count' => 1,
    ]);

    $submitResp->assertStatus(200);
    $submitResp->assertSee('Submission Details'); // always shown in result page summary

    $result = ExamResult::where('exam_id', $exam->id)
        ->where('student_id', 'STU_TEST_01')
        ->first();

    expect($result)->not->toBeNull()
        ->and((float)$result->score)->toBe(1.0)
        ->and($result->correct_answers)->toBe(1)
        ->and($result->tab_switch_count)->toBe(1)
        ->and($result->time_taken_seconds)->toBeGreaterThanOrEqual(290);
});

// ─── Phase 2 Tests ────────────────────────────────────────────────────────────

test('per-question marks are awarded correctly on submission', function () {
    $exam = Exam::create([
        'exam_id'         => 'MARKS303',
        'exam_name'       => 'Marks Test Exam',
        'duration_minutes' => 60,
        'is_active'       => true,
        'negative_marking' => 0,
    ]);

    // Q1 worth 2 marks
    $q1 = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => '2+2?',
        'question_type' => 'single',
        'marks'         => 2.00,
    ]);
    $q1correct = Answer::create(['question_id' => $q1->id, 'answer_text' => '4', 'is_correct' => true]);
    Answer::create(['question_id' => $q1->id, 'answer_text' => '5', 'is_correct' => false]);

    // Q2 worth 3 marks
    $q2 = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => '3+3?',
        'question_type' => 'single',
        'marks'         => 3.00,
    ]);
    $q2correct = Answer::create(['question_id' => $q2->id, 'answer_text' => '6', 'is_correct' => true]);
    Answer::create(['question_id' => $q2->id, 'answer_text' => '7', 'is_correct' => false]);

    // Answer both correctly (should get 2+3 = 5 marks)
    $resp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_MARKS_01',
        'index_no'        => 'IDX_MARKS_01',
        'exam_started_at' => now()->timestamp,
    ])->post(route('student.submit-exam', $exam->id), [
        'answers' => [
            $q1->id => $q1correct->id,
            $q2->id => $q2correct->id,
        ],
    ]);

    $resp->assertStatus(200);
    $result = ExamResult::where('exam_id', $exam->id)->where('student_id', 'STU_MARKS_01')->first();

    expect($result)->not->toBeNull()
        ->and((float)$result->score)->toBe(5.0)
        ->and((float)$result->total_marks)->toBe(5.0)
        ->and($result->correct_answers)->toBe(2);
});

test('negative marking deducts marks for wrong answers and score cannot go below zero', function () {
    $exam = Exam::create([
        'exam_id'          => 'NEG404',
        'exam_name'        => 'Negative Marking Exam',
        'duration_minutes' => 30,
        'is_active'        => true,
        'negative_marking' => 0.25,
    ]);

    $q1 = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => '1+1?',
        'question_type' => 'single',
        'marks'         => 1.00,
    ]);
    $q1wrong = Answer::create(['question_id' => $q1->id, 'answer_text' => '3', 'is_correct' => false]);
    Answer::create(['question_id' => $q1->id, 'answer_text' => '2', 'is_correct' => true]);

    // Only 1 question, answer wrong → score 0 - 0.25 = should be clamped to 0
    $resp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_NEG_01',
        'index_no'        => 'IDX_NEG_01',
        'exam_started_at' => now()->timestamp,
    ])->post(route('student.submit-exam', $exam->id), [
        'answers' => [
            $q1->id => $q1wrong->id,
        ],
    ]);

    $resp->assertStatus(200);
    $result = ExamResult::where('exam_id', $exam->id)->where('student_id', 'STU_NEG_01')->first();

    expect($result)->not->toBeNull()
        ->and((float)$result->score)->toBe(0.0); // clamped to 0, not negative
});

test('student is blocked before exam start_time window', function () {
    $exam = Exam::create([
        'exam_id'         => 'SCHED505',
        'exam_name'       => 'Scheduled Exam',
        'duration_minutes' => 30,
        'is_active'       => true,
        'start_time'      => now()->addHour(), // starts in 1 hour
    ]);

    $resp = $this->post(route('student.authenticate'), [
        'exam_id'    => 'SCHED505',
        'student_id' => 'STU_SCHED_01',
        'index_no'   => 'IDX_SCHED_01',
    ]);

    // Should be redirected back with an error about the schedule
    $resp->assertRedirect();
    $resp->assertSessionHasErrors('exam_id');
});

test('student is blocked after exam end_time window', function () {
    $exam = Exam::create([
        'exam_id'         => 'SCHED606',
        'exam_name'       => 'Expired Exam',
        'duration_minutes' => 30,
        'is_active'       => true,
        'end_time'        => now()->subHour(), // closed 1 hour ago
    ]);

    $resp = $this->post(route('student.authenticate'), [
        'exam_id'    => 'SCHED606',
        'student_id' => 'STU_SCHED_02',
        'index_no'   => 'IDX_SCHED_02',
    ]);

    $resp->assertRedirect();
    $resp->assertSessionHasErrors('exam_id');
});

test('exam within schedule window allows student access', function () {
    $exam = Exam::create([
        'exam_id'         => 'SCHED707',
        'exam_name'       => 'Active Window Exam',
        'duration_minutes' => 60,
        'is_active'       => true,
        'start_time'      => now()->subMinute(),  // started 1 min ago
        'end_time'        => now()->addHour(),    // ends in 1 hour
    ]);

    Question::create([
        'exam_id'       => $exam->id,
        'question_text' => 'Test Q?',
        'question_type' => 'single',
    ]);

    $resp = $this->post(route('student.authenticate'), [
        'exam_id'    => 'SCHED707',
        'student_id' => 'STU_SCHED_03',
        'index_no'   => 'IDX_SCHED_03',
    ]);

    $resp->assertRedirect(route('student.exam-preview', $exam->id));
});

test('isPassed returns true when score meets pass_percentage', function () {
    $exam = Exam::create([
        'exam_id'          => 'PASS808',
        'exam_name'        => 'Pass Test Exam',
        'duration_minutes' => 30,
        'is_active'        => true,
        'pass_percentage'  => 50,
        'negative_marking' => 0,
    ]);

    $q = Question::create([
        'exam_id'       => $exam->id,
        'question_text' => '1+1?',
        'question_type' => 'single',
        'marks'         => 1.00,
    ]);
    $correct = Answer::create(['question_id' => $q->id, 'answer_text' => '2', 'is_correct' => true]);
    Answer::create(['question_id' => $q->id, 'answer_text' => '3', 'is_correct' => false]);

    $resp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_PASS_01',
        'index_no'        => 'IDX_PASS_01',
        'exam_started_at' => now()->timestamp,
    ])->post(route('student.submit-exam', $exam->id), [
        'answers' => [$q->id => $correct->id],
    ]);

    $result = ExamResult::where('exam_id', $exam->id)->where('student_id', 'STU_PASS_01')->first();

    expect($result)->not->toBeNull()
        ->and($result->isPassed())->toBeTrue();
});

test('isPassed returns false when score is below pass_percentage', function () {
    $exam = Exam::create([
        'exam_id'          => 'FAIL909',
        'exam_name'        => 'Fail Test Exam',
        'duration_minutes' => 30,
        'is_active'        => true,
        'pass_percentage'  => 80,
        'negative_marking' => 0,
    ]);

    $q1 = Question::create(['exam_id' => $exam->id, 'question_text' => '1+1?', 'question_type' => 'single', 'marks' => 1.0]);
    $q1c = Answer::create(['question_id' => $q1->id, 'answer_text' => '2', 'is_correct' => true]);
    Answer::create(['question_id' => $q1->id, 'answer_text' => '3', 'is_correct' => false]);

    $q2 = Question::create(['exam_id' => $exam->id, 'question_text' => '2+2?', 'question_type' => 'single', 'marks' => 1.0]);
    $q2w = Answer::create(['question_id' => $q2->id, 'answer_text' => '9', 'is_correct' => false]);
    Answer::create(['question_id' => $q2->id, 'answer_text' => '4', 'is_correct' => true]);

    // Answer Q1 correct, Q2 wrong → 1/2 = 50% < 80% → should FAIL
    $resp = $this->withSession([
        'student_exam_id' => $exam->id,
        'student_id'      => 'STU_FAIL_01',
        'index_no'        => 'IDX_FAIL_01',
        'exam_started_at' => now()->timestamp,
    ])->post(route('student.submit-exam', $exam->id), [
        'answers' => [
            $q1->id => $q1c->id,
            $q2->id => $q2w->id,
        ],
    ]);

    $result = ExamResult::where('exam_id', $exam->id)->where('student_id', 'STU_FAIL_01')->first();

    expect($result)->not->toBeNull()
        ->and($result->isPassed())->toBeFalse();
});
