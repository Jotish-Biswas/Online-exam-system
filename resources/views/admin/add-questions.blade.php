@extends('layouts.shell')

@section('title', 'Manage Questions — ' . $exam->exam_name)
@section('meta-description', 'Add and manage questions for ' . $exam->exam_name)

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Builder</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-arrow-left"></i> Dashboard
</a>
@endsection

@section('head')
<style>
    .builder-root {
        padding: 2.5rem 1.25rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .builder-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2rem;
    }

    .builder-layout {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    @media (min-width: 900px) {
        .builder-layout { flex-direction: row; align-items: flex-start; }
        .builder-main { flex: 1; min-width: 0; }
        .builder-side { width: 420px; flex-shrink: 0; }
    }

    /* Cards */
    .b-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }
    .b-card__header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--surface-alt);
    }
    .b-card__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .b-card__title i { color: var(--color-accent); }
    .b-card__body {
        padding: 1.5rem;
    }

    /* Type Selector */
    .type-selector {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .type-radio {
        display: none;
    }
    .type-card {
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem 0.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
        background: var(--surface-alt);
    }
    .type-card:hover { border-color: var(--border-strong); }
    .type-radio:checked + .type-card {
        border-color: var(--color-accent);
        background: var(--color-accent-surface);
        box-shadow: 0 0 0 1px var(--color-accent);
    }
    .type-card__icon {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
    }
    .type-radio:checked + .type-card .type-card__icon {
        color: var(--color-accent);
    }
    .type-card__label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text);
    }

    /* Answer Row */
    .answer-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        align-items: center;
    }
    .answer-row__prefix {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--text-muted);
        flex-shrink: 0;
    }
    .answer-row__input {
        flex: 1;
    }
    .answer-row__correct {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0 0.5rem;
        height: 36px;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.15s;
    }
    .answer-row__correct:has(input:checked) {
        background: var(--color-success-bg);
        border-color: rgba(19,115,51,.25);
        color: var(--color-success);
    }
    .answer-row__correct input {
        margin: 0;
        cursor: pointer;
    }
    .answer-row__correct span {
        font-size: 0.8125rem;
        font-weight: 600;
    }

    /* Question List */
    .q-item {
        padding: 1.25rem;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 1rem;
        background: var(--surface);
    }
    .q-item__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    .q-item__title {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--text);
        margin: 0;
        line-height: 1.4;
    }
    .q-item__actions {
        display: flex;
        gap: 0.4rem;
        flex-shrink: 0;
        margin-left: 1rem;
    }
    .q-item__meta {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')
<div class="builder-root page-fade-in">

    <div class="builder-header">
        <div>
            <h1 style="font-size:1.75rem; font-weight:800; color:var(--text); margin:0 0 0.25rem;">{{ $exam->exam_name }}</h1>
            <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
                Exam ID: <span style="font-weight:600; color:var(--text);">{{ $exam->exam_id }}</span>
            </p>
        </div>
        <div style="text-align:right;">
            <span class="badge {{ $exam->is_active ? 'badge-success' : 'badge-neutral' }}" style="padding:0.4rem 0.8rem; font-size:0.8125rem;">
                <span class="badge-dot"></span>
                {{ $exam->is_active ? 'Active' : 'Draft' }}
            </span>
        </div>
    </div>

    <div class="builder-layout">
        
        {{-- Left: Add Question Form & Import --}}
        <div class="builder-main">
            
            <div class="b-card">
                <div class="b-card__header">
                    <h2 class="b-card__title"><i class="fas fa-plus"></i> Add New Question</h2>
                </div>
                <div class="b-card__body">
                    <form action="{{ route('admin.store-question', $exam->id) }}" method="POST" id="questionForm" novalidate>
                        @csrf
                        
                        <div class="field" style="margin-bottom:1.5rem;">
                            <label class="field-label" for="question_text">Question Text</label>
                            <textarea class="field-input {{ $errors->has('question_text') ? 'is-error' : '' }}" 
                                      id="question_text" name="question_text" rows="3" 
                                      placeholder="Type the question here..." required>{{ old('question_text') }}</textarea>
                            @error('question_text') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <label class="field-label">Question Type</label>
                        <div class="type-selector">
                            <label>
                                <input type="radio" class="type-radio" name="question_type" id="single" value="single" {{ old('question_type', 'single') == 'single' ? 'checked' : '' }}>
                                <div class="type-card">
                                    <div class="type-card__icon"><i class="far fa-dot-circle"></i></div>
                                    <div class="type-card__label">Single Choice</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" class="type-radio" name="question_type" id="multiple" value="multiple" {{ old('question_type') == 'multiple' ? 'checked' : '' }}>
                                <div class="type-card">
                                    <div class="type-card__icon"><i class="far fa-check-square"></i></div>
                                    <div class="type-card__label">Multiple Choice</div>
                                </div>
                            </label>
                            <label>
                                <input type="radio" class="type-radio" name="question_type" id="file_upload" value="file_upload" {{ old('question_type') == 'file_upload' ? 'checked' : '' }}>
                                <div class="type-card">
                                    <div class="type-card__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="type-card__label">File Upload</div>
                                </div>
                            </label>
                        </div>

                        {{-- MCQ Answers --}}
                        <div id="mcqAnswersContainer" style="margin-bottom:1.5rem;">
                            <label class="field-label">Options</label>
                            <div id="answersContainer">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="answer-row">
                                        <div class="answer-row__prefix">{{ chr(65 + $i) }}</div>
                                        <input type="text" class="field-input answer-row__input answer-text-input" 
                                               name="answers[]" placeholder="Option {{ chr(65 + $i) }}"
                                               value="{{ old('answers.' . $i) }}" {{ $i < 2 ? 'required' : '' }}>
                                        <label class="answer-row__correct">
                                            <input class="correct-answer" type="checkbox" name="correct_answers[]" value="{{ $i }}"
                                                   {{ old('correct_answers') && in_array($i, old('correct_answers', [])) ? 'checked' : '' }}>
                                            <span>Correct</span>
                                        </label>
                                    </div>
                                @endfor
                            </div>
                            <p class="field-hint" id="correct-answers-help" style="margin-top:0.5rem;"></p>
                            @error('answers') <p class="field-error" style="display:block;">{{ $message }}</p> @enderror
                            @error('correct_answers') <p class="field-error" style="display:block;">{{ $message }}</p> @enderror
                        </div>

                        {{-- File Upload Settings --}}
                        <div id="fileUploadSettings" style="display:none; margin-bottom:1.5rem; padding:1.25rem; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-md);">
                            <label class="field-label" style="margin-bottom:1rem;">File Upload Rules</label>
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                                <div>
                                    <label class="field-label" style="font-size:0.75rem;">Allowed Extensions</label>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                                        @php
                                            $extensions = ['pdf'=>'PDF', 'doc'=>'DOC', 'docx'=>'DOCX', 'xls'=>'XLS', 'xlsx'=>'XLSX', 'jpg'=>'JPG', 'png'=>'PNG', 'zip'=>'ZIP'];
                                            $oldExts = old('file_upload_settings.allowed_extensions', ['pdf', 'doc', 'docx', 'jpg', 'png']);
                                        @endphp
                                        @foreach($extensions as $ext => $label)
                                            <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.8125rem; cursor:pointer;">
                                                <input type="checkbox" name="file_upload_settings[allowed_extensions][]" value="{{ $ext }}" {{ in_array($ext, $oldExts) ? 'checked' : '' }}>
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <div class="field">
                                        <label class="field-label" for="max_size_mb" style="font-size:0.75rem;">Max Size (MB)</label>
                                        <input class="field-input" type="number" name="file_upload_settings[max_size_mb]" id="max_size_mb" min="1" max="100" value="{{ old('file_upload_settings.max_size_mb', 10) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 2fr; gap:1.25rem; margin-bottom:1.5rem;">
                            <div class="field mb-0">
                                <label class="field-label" for="marks">Marks / Points</label>
                                <input class="field-input {{ $errors->has('marks') ? 'is-error' : '' }}" 
                                       type="number" step="0.25" min="0.25" max="100" 
                                       id="marks" name="marks" value="{{ old('marks', '1.00') }}" required>
                                @error('marks') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="field mb-0">
                                <label class="field-label" for="explanation">Explanation (Optional)</label>
                                <input class="field-input {{ $errors->has('explanation') ? 'is-error' : '' }}" 
                                       type="text" id="explanation" name="explanation" value="{{ old('explanation') }}"
                                       placeholder="Shown to students after exam...">
                            </div>
                        </div>

                        <div style="text-align:right;">
                            <button type="submit" class="btn btn-primary" style="padding-left:1.5rem; padding-right:1.5rem;">
                                <i class="fas fa-plus" style="margin-right:0.4rem;"></i> Add Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Bulk Import --}}
            <div class="b-card">
                <div class="b-card__header" style="background:var(--color-success-bg);">
                    <h2 class="b-card__title" style="color:var(--color-success);"><i class="fas fa-file-excel"></i> Bulk Import</h2>
                    <a href="{{ route('admin.download-question-template') }}" class="btn btn-ghost" style="color:var(--color-success); padding:0.25rem 0.5rem; font-size:0.75rem;">
                        <i class="fas fa-download"></i> Template
                    </a>
                </div>
                <div class="b-card__body">
                    <form action="{{ route('admin.import-questions', $exam->id) }}" method="POST" enctype="multipart/form-data" style="display:flex; align-items:flex-end; gap:1rem;">
                        @csrf
                        <div class="field mb-0" style="flex:1;">
                            <label class="field-label" for="excel_file">Upload Excel/CSV</label>
                            <input class="field-input" type="file" id="excel_file" name="file" accept=".xlsx,.xls,.csv" required style="padding:0.4rem; font-size:0.875rem;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:var(--color-success); border-color:var(--color-success);">
                            Import
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Right: Question List --}}
        <div class="builder-side">
            <div class="b-card">
                <div class="b-card__header">
                    <h2 class="b-card__title"><i class="fas fa-list-ul"></i> Questions ({{ $questions->count() }})</h2>
                </div>
                <div class="b-card__body" style="padding:1.25rem; max-height:800px; overflow-y:auto;">
                    
                    @if($questions->count() > 0)
                        @foreach($questions as $index => $question)
                            <div class="q-item">
                                <div class="q-item__header">
                                    <h3 class="q-item__title">
                                        <span style="color:var(--text-muted); margin-right:0.25rem;">{{ $index + 1 }}.</span>
                                        {{ $question->question_text }}
                                    </h3>
                                    <div class="q-item__actions">
                                        <a href="{{ route('admin.edit-question', $question->id) }}" class="btn btn-ghost" style="padding:0.25rem 0.4rem; color:var(--text-muted);" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-ghost" style="padding:0.25rem 0.4rem; color:var(--color-danger);" 
                                                onclick="openDeleteModal('{{ $question->id }}')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                @if($question->question_type === 'file_upload')
                                    <div class="notice notice--info" style="padding:0.5rem 0.75rem; margin-bottom:0.5rem;">
                                        <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        <span style="font-size:0.75rem;">File Upload: Max {{ $question->file_upload_settings['max_size_mb'] ?? 10 }}MB</span>
                                    </div>
                                @else
                                    <div style="font-size:0.8125rem; color:var(--text-secondary);">
                                        @foreach($question->answers as $ansIdx => $ans)
                                            <div style="display:flex; align-items:center; gap:0.4rem; margin-bottom:0.25rem;">
                                                <span style="font-weight:700; color:var(--text-muted);">{{ chr(65 + $ansIdx) }}.</span>
                                                <span style="{{ $ans->is_correct ? 'color:var(--color-success); font-weight:600;' : '' }}">
                                                    {{ $ans->answer_text }}
                                                    @if($ans->is_correct) <i class="fas fa-check" style="font-size:0.7rem; margin-left:0.2rem;"></i> @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="q-item__meta">
                                    <span class="badge badge-neutral">{{ $question->marks ?? 1.00 }} Marks</span>
                                    @if($question->question_type === 'file_upload')
                                        <span class="badge badge-warning">File Upload</span>
                                    @elseif($question->question_type === 'multiple')
                                        <span class="badge badge-info">Multiple Choice</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align:center; padding:3rem 1rem;">
                            <i class="fas fa-inbox" style="font-size:2.5rem; color:var(--border-strong); margin-bottom:1rem;"></i>
                            <p style="font-size:0.9375rem; color:var(--text-muted); margin:0;">No questions added yet.<br>Use the form to add one.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>

{{-- Delete Modal --}}
<div class="modal-overlay" id="deleteQModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal__header">
            <h3 class="modal__title" style="color:var(--color-danger); display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-exclamation-triangle"></i> Delete Question
            </h3>
            <button type="button" class="modal__close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body">
            <p style="font-size:0.9375rem; margin:0 0 1rem; color:var(--text);">Are you sure you want to delete this question?</p>
            <div class="notice notice--danger">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Cannot be undone. Prevented if students have already answered it.</span>
            </div>
        </div>
        <div class="modal__footer" style="display:flex; justify-content:flex-end; gap:0.5rem; padding:1.25rem;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteQForm" method="POST" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--color-danger); border-color:var(--color-danger);">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const typeRadios = document.querySelectorAll('.type-radio');
    const mcqContainer = document.getElementById('mcqAnswersContainer');
    const fileContainer = document.getElementById('fileUploadSettings');
    const correctCheckboxes = document.querySelectorAll('.correct-answer');
    const helpText = document.getElementById('correct-answers-help');

    function updateType() {
        const type = document.querySelector('.type-radio:checked').value;
        
        if (type === 'file_upload') {
            mcqContainer.style.display = 'none';
            fileContainer.style.display = 'block';
            document.querySelectorAll('.answer-text-input').forEach(i => i.removeAttribute('required'));
        } else {
            mcqContainer.style.display = 'block';
            fileContainer.style.display = 'none';
            
            if (type === 'single') {
                correctCheckboxes.forEach(cb => {
                    cb.type = 'radio';
                    cb.name = 'correct_answers[]';
                });
                helpText.textContent = "Select exactly one correct option.";
            } else {
                correctCheckboxes.forEach(cb => {
                    cb.type = 'checkbox';
                    cb.name = 'correct_answers[]';
                });
                helpText.textContent = "Select one or more correct options.";
            }
        }
    }

    typeRadios.forEach(r => r.addEventListener('change', updateType));
    updateType();

    // Answer requirements
    function updateAnswerReqs() {
        const inputs = document.querySelectorAll('.answer-text-input');
        let filled = 0;
        inputs.forEach(i => { if(i.value.trim() !== '') filled++; });
        inputs.forEach((input, index) => {
            if (index < Math.max(2, filled)) {
                input.setAttribute('required', 'required');
            } else if (input.value.trim() === '') {
                input.removeAttribute('required');
            }
        });
    }
    document.querySelectorAll('.answer-text-input').forEach(i => {
        i.addEventListener('input', updateAnswerReqs);
    });

    // Form validation
    document.getElementById('questionForm').addEventListener('submit', function(e) {
        const type = document.querySelector('.type-radio:checked').value;
        if (type !== 'file_upload') {
            const checked = document.querySelectorAll('.correct-answer:checked');
            const filled = Array.from(document.querySelectorAll('.answer-text-input')).filter(i => i.value.trim() !== '');
            
            if (filled.length < 2) {
                e.preventDefault();
                alert("Provide at least 2 options.");
                return;
            }
            if (checked.length === 0) {
                e.preventDefault();
                alert("Select at least one correct option.");
                return;
            }
            
            // disable empty ones before submit
            document.querySelectorAll('.answer-text-input').forEach((input, idx) => {
                if (input.value.trim() === '') {
                    input.disabled = true;
                    const cb = document.querySelector(`.correct-answer[value="${idx}"]`);
                    if (cb) cb.disabled = true;
                }
            });
        }
    });

    // Delete Modal
    function openDeleteModal(id) {
        document.getElementById('deleteQForm').action = `/admin/question/${id}`;
        document.getElementById('deleteQModal').classList.add('is-active');
    }
    function closeDeleteModal() {
        document.getElementById('deleteQModal').classList.remove('is-active');
    }
</script>
@endsection
