@extends('layouts.shell')

@section('title', 'Grade File Submissions - ' . $exam->exam_name)

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">
        Grading: {{ $exam->exam_name }}
    </span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="Back to Results">
    <i class="fas fa-arrow-left"></i> <span class="nav-text-hidden">Back</span>
</a>
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="Dashboard">
    <i class="fas fa-tachometer-alt"></i> <span class="nav-text-hidden">Dashboard</span>
</a>
<form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
    @csrf
    <button type="submit" class="btn btn-ghost" style="padding:0.4rem 0.75rem; color:var(--color-danger);" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </button>
</form>
@endsection

@section('head')
<style>
    .page-container {
        padding-top: 2rem;
        padding-bottom: 3rem;
    }

    .page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.25rem;
        color: var(--text);
    }

    .page-subtitle {
        color: var(--text-muted);
        font-size: 0.9375rem;
        margin: 0;
    }

    /* Info panels */
    .info-panel {
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        margin-bottom: 2rem;
    }

    .info-grid {
        display: grid;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .q-summary {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem;
    }

    /* Submissions */
    .submission-block {
        margin-bottom: 2rem;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
    }

    .sub-header {
        background: var(--surface-alt);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        background: var(--color-accent-surface);
        color: var(--color-accent);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.125rem;
    }

    .sub-body {
        padding: 1.5rem;
        display: grid;
        gap: 1.5rem;
    }
    @media (min-width: 992px) {
        .sub-body { grid-template-columns: repeat(2, 1fr); }
    }

    .grade-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        display: flex;
        flex-direction: column;
    }

    .grade-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .grade-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .file-attachment {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--surface-alt);
        padding: 0.75rem 1rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        margin: 1rem 0;
        gap: 1rem;
    }

    .grade-form-area {
        margin-top: auto;
        padding-top: 1.25rem;
        border-top: 1px dashed var(--border-strong);
    }

    @media (max-width: 640px) {
        .nav-text-hidden { display: none; }
        .file-attachment { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection

@section('content')
<div class="page-container page-fade-in">
    
    <div class="page-header">
        <div>
            <h1 class="page-title">Grade writing submissions</h1>
            <p class="page-subtitle">{{ $exam->exam_name }} <span class="badge badge-neutral" style="margin-left:0.5rem;">ID: {{ $exam->exam_id }}</span></p>
        </div>
        <div style="text-align:right;">
            <div class="badge badge-accent" style="font-size:0.875rem; padding:0.4rem 0.75rem; margin-bottom:0.25rem;">
                Students: {{ $submissions->count() }}
            </div>
            <div style="font-size:0.8125rem; color:var(--text-muted); margin-bottom:0.5rem;">
                Writing questions: {{ $fileUploadQuestions->count() }}
            </div>
            @if(request()->boolean('ungraded'))
                <a href="{{ route('admin.grade-submissions', $exam->id) }}" class="btn btn-secondary" style="min-height:34px; padding:0.35rem 0.75rem;">Show all</a>
            @else
                <a href="{{ route('admin.grade-submissions', [$exam->id, 'ungraded' => 1]) }}" class="btn btn-secondary" style="min-height:34px; padding:0.35rem 0.75rem;">Ungraded only</a>
            @endif
        </div>
    </div>

    @if($submissions->count() > 0)
        <!-- File Upload Questions Summary -->
        <div class="info-panel">
            <h3 style="font-size:1.125rem; margin:0 0 1rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-pen-fancy" style="color:var(--text-muted);"></i> Writing questions in this exam
            </h3>
            <div class="info-grid">
                @foreach($fileUploadQuestions as $index => $question)
                    <div class="q-summary">
                        <div style="font-size:0.875rem; font-weight:700; color:var(--text); margin-bottom:0.5rem;">
                            Question {{ $loop->iteration }}
                            <span class="badge badge-neutral" style="margin-left:0.35rem;">{{ number_format((float)($question->marks ?? 1), 2) }} marks</span>
                        </div>
                        <p style="font-size:0.9375rem; line-height:1.5; color:var(--text-secondary); margin:0 0 0.75rem;">
                            {{ Str::limit($question->question_text, 100) }}
                        </p>
                        <div style="font-size:0.75rem; color:var(--text-muted); display:flex; gap:1rem;">
                            <span><strong>Allowed:</strong> {{ strtoupper(implode(', ', $question->getAllowedExtensions())) }}</span>
                            <span><strong>Max Size:</strong> {{ $question->getMaxFileSize() }} MB</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Submissions List -->
        @foreach($submissions as $submission)
            <div class="submission-block">
                <div class="sub-header">
                    <div class="student-info">
                        <div class="student-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:1rem; color:var(--text);">{{ $submission->student_id }}</div>
                            <div style="font-size:0.8125rem; color:var(--text-muted);">
                                Index: {{ $submission->index_no }} &bull; Submitted: {{ $submission->submitted_at->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                    <div>
                        @php
                            $totalFileQuestions = $fileUploadQuestions->count();
                            $gradedFileQuestions = $fileUploadQuestions->filter(function ($q) use ($submission) {
                                $sa = $submission->studentAnswers->firstWhere('question_id', $q->id);
                                return $sa && $sa->is_graded;
                            })->count();
                        @endphp
                        <a href="{{ route('admin.grade-desk', [$exam->id, $submission->id]) }}" class="btn btn-primary" style="min-height:34px; padding:0.35rem 0.8rem; margin-right:0.4rem;">
                            <i class="fas fa-chalkboard"></i> Open grade desk
                        </a>
                        <span class="badge {{ $gradedFileQuestions == $totalFileQuestions ? 'badge-success' : 'badge-warning' }}" style="font-size:0.875rem; padding:0.3rem 0.6rem;">
                            {{ $gradedFileQuestions }}/{{ $totalFileQuestions }} Graded
                        </span>
                    </div>
                </div>

                <div class="sub-body">
                    @foreach($fileUploadQuestions as $question)
                        @php
                            $studentAnswer = $submission->studentAnswers->firstWhere('question_id', $question->id);
                            $maxMarks = (float) ($question->marks ?? 1);
                            $gradeAction = $studentAnswer
                                ? route('admin.grade-file-submission', $studentAnswer->id)
                                : route('admin.grade-writing-question', [$submission->id, $question->id]);
                        @endphp
                        <div class="grade-card">
                            <div class="grade-header">
                                <span style="font-weight:700; font-size:0.9375rem;">
                                    Question {{ $loop->iteration }}
                                    <span style="font-weight:500; color:var(--text-muted);">· {{ number_format($maxMarks, 2) }} marks</span>
                                </span>
                                @if($studentAnswer && $studentAnswer->is_graded)
                                    <span class="badge badge-success">Score: {{ number_format((float)$studentAnswer->manual_score, 2) }}/{{ number_format($maxMarks, 2) }}</span>
                                @else
                                    <span class="badge badge-neutral">Not Graded</span>
                                @endif
                            </div>

                            <div class="grade-body">
                                <p style="font-size:0.9375rem; line-height:1.5; color:var(--text-secondary); margin:0;">
                                    {{ Str::limit($question->question_text, 160) }}
                                </p>

                                @if($studentAnswer && $studentAnswer->file_path)
                                <div class="file-attachment">
                                    <div style="display:flex; align-items:center; gap:0.75rem; min-width:0;">
                                        <i class="fas fa-file-alt" style="font-size:1.5rem; color:var(--color-accent);"></i>
                                        <div style="min-width:0;">
                                            <div style="font-weight:600; font-size:0.875rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $studentAnswer->original_filename }}</div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">{{ $studentAnswer->getFormattedFileSize() }}</div>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:0.5rem;">
                                        <button type="button" class="btn btn-secondary btn-icon view-file-btn"
                                                data-file-url="{{ $studentAnswer->getFileUrl() }}"
                                                data-file-name="{{ $studentAnswer->original_filename }}" title="Preview">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ $studentAnswer->getFileUrl() }}" class="btn btn-primary btn-icon" target="_blank" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                                @else
                                <div class="file-attachment">
                                    <div style="font-size:0.875rem; color:var(--text-muted);">
                                        No file uploaded. You can still award 0 or partial marks.
                                    </div>
                                </div>
                                @endif

                                <div class="grade-form-area">
                                    <form action="{{ $gradeAction }}" method="POST" class="grading-form">
                                        @csrf
                                        @method('PUT')
                                        <div style="display:flex; gap:1rem; align-items:flex-start; flex-wrap:wrap;">
                                            <div class="field" style="width: 140px; flex-shrink:0;">
                                                <label class="field-label">Marks (0–{{ rtrim(rtrim(number_format($maxMarks, 2), '0'), '.') }})</label>
                                                <input type="number" class="field-input score-input" style="min-height:38px; padding:0.5rem;"
                                                       name="manual_score" min="0" max="{{ $maxMarks }}" step="0.25"
                                                       value="{{ $studentAnswer->manual_score ?? '' }}" required>
                                                <div style="display:flex; gap:0.35rem; margin-top:0.4rem;">
                                                    <button type="button" class="btn btn-ghost" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="this.closest('form').querySelector('.score-input').value='{{ $maxMarks }}'">Full</button>
                                                    <button type="button" class="btn btn-ghost" style="padding:0.15rem 0.4rem; font-size:0.7rem;" onclick="this.closest('form').querySelector('.score-input').value='0'">Zero</button>
                                                </div>
                                            </div>
                                            <div class="field" style="flex:1; min-width:200px;">
                                                <label class="field-label">Feedback (Optional)</label>
                                                <textarea class="field-input" name="admin_feedback" rows="2" style="min-height:38px; padding:0.5rem;" placeholder="Add feedback...">{{ $studentAnswer->admin_feedback ?? '' }}</textarea>
                                            </div>
                                            <div style="margin-top:1.4rem;">
                                                <button type="submit" class="btn btn-success" style="min-height:38px; padding:0.5rem 1rem;">
                                                    <i class="fas fa-check"></i> {{ ($studentAnswer && $studentAnswer->is_graded) ? 'Update' : 'Save marks' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    @if($studentAnswer && $studentAnswer->is_graded && $studentAnswer->admin_feedback)
                                        <div style="margin-top:0.75rem; font-size:0.8125rem; background:var(--color-success-bg); border:1px solid rgba(19,115,51,.15); padding:0.5rem 0.75rem; border-radius:var(--radius-sm); color:var(--text-secondary);">
                                            <strong>Feedback given:</strong> {{ $studentAnswer->admin_feedback }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    @else
        <div style="text-align:center; padding:4rem 1.5rem; background:var(--surface); border:1px dashed var(--border-strong); border-radius:var(--radius-xl);">
            <i class="fas fa-folder-open" style="font-size:3rem; color:var(--border-strong); margin-bottom:1rem;"></i>
            <h2 style="font-size:1.5rem; font-weight:700; color:var(--text); margin:0 0 0.5rem;">No submissions to grade</h2>
            <p style="font-size:0.9375rem; color:var(--text-muted); margin:0 0 1.5rem;">No students have sat this exam yet, or nothing matches the ungraded filter.</p>
            <a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-primary">
                &larr; Back to Results
            </a>
        </div>
    @endif
</div>

<!-- Custom File Preview Modal -->
<div class="modal-overlay" id="filePreviewModal">
    <div class="modal" style="max-width:800px; width:90%;">
        <div class="modal__header">
            <h3 class="modal__title" id="filePreviewModalLabel" style="display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-file"></i> File Preview
            </h3>
            <button type="button" class="modal__close" onclick="closePreviewModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body" style="padding:0; background:var(--surface-alt); min-height:300px; display:flex; align-items:center; justify-content:center;">
            <div id="filePreviewContent" style="width:100%; text-align:center; padding:2rem;">
                <p>Loading file preview...</p>
            </div>
        </div>
        <div class="modal__footer" style="display:flex; justify-content:flex-end; gap:0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Close</button>
            <a href="#" id="downloadFileBtn" class="btn btn-primary" target="_blank">
                <i class="fas fa-download"></i> Download File
            </a>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function closePreviewModal() {
        document.getElementById('filePreviewModal').classList.remove('is-active');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filePreviewModal = document.getElementById('filePreviewModal');
        const filePreviewContent = document.getElementById('filePreviewContent');
        const downloadFileBtn = document.getElementById('downloadFileBtn');
        const modalTitle = document.getElementById('filePreviewModalLabel');

        // Handle file preview buttons
        document.querySelectorAll('.view-file-btn').forEach(button => {
            button.addEventListener('click', function() {
                const fileUrl = this.dataset.fileUrl;
                const fileName = this.dataset.fileName;
                const fileExtension = fileName.split('.').pop().toLowerCase();
                
                modalTitle.innerHTML = '<i class="fas fa-file"></i> ' + fileName;
                downloadFileBtn.href = fileUrl;
                
                // Clear previous content
                filePreviewContent.innerHTML = '<p style="color:var(--text-muted);">Loading preview...</p>';
                
                // Show different previews based on file type
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                    filePreviewContent.innerHTML = `
                        <img src="${fileUrl}" alt="File preview" style="max-width:100%; max-height:60vh; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                    `;
                } else if (fileExtension === 'pdf') {
                    filePreviewContent.innerHTML = `
                        <iframe src="${fileUrl}" style="width:100%; height:60vh; border:none; border-radius:var(--radius-md);"></iframe>
                        <p style="margin-top:1rem; font-size:0.875rem; color:var(--text-muted);">If the PDF doesn't display, <a href="${fileUrl}" target="_blank" style="color:var(--color-accent);">click here to open it</a>.</p>
                    `;
                } else if (['txt', 'csv', 'json'].includes(fileExtension)) {
                    fetch(fileUrl)
                        .then(response => response.text())
                        .then(text => {
                            filePreviewContent.innerHTML = `
                                <div style="text-align:left; background:var(--surface); padding:1rem; border-radius:var(--radius-md); border:1px solid var(--border); overflow-y:auto; max-height:60vh;">
                                    <pre style="margin:0; font-family:var(--font-mono); font-size:0.875rem; color:var(--text); white-space:pre-wrap;">${text}</pre>
                                </div>
                            `;
                        })
                        .catch(() => {
                            filePreviewContent.innerHTML = `
                                <p style="color:var(--text-muted);">Cannot preview this file type. Please download to view.</p>
                            `;
                        });
                } else {
                    filePreviewContent.innerHTML = `
                        <div style="text-align:center; padding:3rem 1rem;">
                            <i class="fas fa-file-alt" style="font-size:3rem; color:var(--border-strong); margin-bottom:1rem;"></i>
                            <p style="color:var(--text-muted); margin:0 0 0.5rem;">Preview not available for this file type (${fileExtension.toUpperCase()}).</p>
                            <p style="font-size:0.9375rem; color:var(--text);">Please download the file to view its contents.</p>
                        </div>
                    `;
                }
                
                filePreviewModal.classList.add('is-active');
            });
        });

        // Add smooth form submission animation
        document.querySelectorAll('.grading-form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                button.disabled = true;
                button.style.opacity = '0.8';
            });
        });
    });
</script>
@endsection