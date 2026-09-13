<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentController;

Route::get('/', [StudentController::class, 'login'])->name('home');

// Admin Authentication Routes (no middleware)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('login');
    Route::post('/authenticate', [AdminController::class, 'authenticate'])->name('authenticate');
    Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
});

// Admin Routes (protected by middleware)
Route::prefix('examadmin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/create-exam', [AdminController::class, 'createExam'])->name('create-exam');
    Route::post('/store-exam', [AdminController::class, 'storeExam'])->name('store-exam');
    Route::get('/exam/{exam}/edit', [AdminController::class, 'editExam'])->name('edit-exam');
    Route::put('/exam/{exam}', [AdminController::class, 'updateExam'])->name('update-exam');
    Route::get('/exam/{exam}/questions', [AdminController::class, 'addQuestions'])->name('add-questions');
    Route::post('/exam/{exam}/questions', [AdminController::class, 'storeQuestion'])->name('store-question');
    Route::post('/exam/{exam}/questions/import', [AdminController::class, 'importQuestions'])->name('import-questions');
    Route::get('/questions/template/download', [AdminController::class, 'downloadQuestionTemplate'])->name('download-question-template');
    Route::get('/question/{question}/edit', [AdminController::class, 'editQuestion'])->name('edit-question');
    Route::put('/question/{question}', [AdminController::class, 'updateQuestion'])->name('update-question');
    Route::delete('/question/{question}', [AdminController::class, 'deleteQuestion'])->name('delete-question');
    Route::get('/student-answer/{studentAnswer}/grade', [AdminController::class, 'gradeFileSubmissionPage'])->name('grade-file-submission-page');
    Route::put('/student-answer/{studentAnswer}/grade', [AdminController::class, 'gradeFileSubmission'])->name('grade-file-submission');
    Route::put('/exam-result/{examResult}/question/{question}/grade', [AdminController::class, 'gradeWritingQuestion'])->name('grade-writing-question');
    Route::get('/exam/{exam}/grade-submissions', [AdminController::class, 'gradeSubmissions'])->name('grade-submissions');
    Route::post('/exam/{exam}/toggle-status', [AdminController::class, 'toggleExamStatus'])->name('toggle-status');
    Route::get('/exam/{exam}/results', [AdminController::class, 'examResults'])->name('exam-results');
    Route::get('/exam/{exam}/results/download', [AdminController::class, 'downloadResults'])->name('download-results');
    Route::get('/exam/{exam}/results/print', [AdminController::class, 'printResults'])->name('print-results');
    Route::get('/exam-result/{result}/marksheet', [AdminController::class, 'printMarksheet'])->name('print-marksheet');
    Route::get('/exam/{exam}/results/export-csv', [AdminController::class, 'exportResultsCsv'])->name('export-results-csv');
    Route::delete('/exam/{exam}', [AdminController::class, 'deleteExam'])->name('delete-exam');
    Route::post('/exam/{exam}/clone', [AdminController::class, 'cloneExam'])->name('clone-exam');
    Route::delete('/exam-result/{result}', [AdminController::class, 'deleteResult'])->name('delete-result');
    Route::get('/manage-admins', [AdminController::class, 'manageAdmins'])->name('manage-admins');
    Route::post('/manage-admins', [AdminController::class, 'storeAdmin'])->name('store-admin');
    Route::delete('/admin-user/{user}', [AdminController::class, 'deleteAdmin'])->name('delete-admin');
    Route::post('/admin-user/{user}/toggle', [AdminController::class, 'toggleAdminStatus'])->name('toggle-admin-status');
});

// Student Routes
Route::get('/start-exam', [StudentController::class, 'login'])->name('student.login');
Route::get('/check-results', [StudentController::class, 'checkResultsForm'])->name('student.checkResultsForm');
Route::post('/check-results', [StudentController::class, 'checkResults'])->name('student.checkResults');
Route::prefix('student')->name('student.')->group(function () {
    Route::post('/authenticate', [StudentController::class, 'authenticate'])->name('authenticate');
    Route::get('/exam/{exam}/preview', [StudentController::class, 'examPreview'])->name('exam-preview');
    Route::get('/exam/{exam}', [StudentController::class, 'exam'])->name('exam');
    Route::post('/exam/{exam}/submit', [StudentController::class, 'submitExam'])->name('submit-exam');
    Route::post('/logout', [StudentController::class, 'logout'])->name('logout');
});
