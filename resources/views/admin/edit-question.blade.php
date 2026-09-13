@extends('layouts.shell')

@section('title', 'Edit Question — ' . $question->exam->exam_name)
@section('meta-description', 'Edit an existing question.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $question->exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Edit Question</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.add-questions', $question->exam_id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-arrow-left"></i> Back to Builder
</a>
@endsection

@section('head')
<style>
    .edit-root {
        padding: 2.5rem 1.25rem;
        max-width: 720px;
        margin: 0 auto;
    }

    .form-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
    }
    
    .form-section__header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    .form-section__header i {
        color: var(--color-accent);
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
</style>
@endsection

@section('content')
<div class="edit-root page-fade-in">

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text); margin: 0 0 0.5rem; letter-spacing: -0.02em;">Edit Question</h1>
        <p style="font-size: 0.9375rem; color: var(--text-muted); margin: 0;">Modify question details and options.</p>
    </div>

    <form action="{{ route('admin.update-question', $question->id) }}" method="POST" id="questionForm">
        @csrf
        @method('PUT')
        
        <div class="form-section">
            <div class="form-section__header">
                <i class="fas fa-edit"></i> Question Details
            </div>
            
            <div class="field" style="margin-bottom:1.5rem;">
                <label class="field-label" for="question_text">Question Text</label>
                <textarea class="field-input {{ $errors->has('question_text') ? 'is-error' : '' }}" 
                          id="question_text" name="question_text" rows="3" 
                          placeholder="Type the question here..." required>{{ old('question_text', $question->question_text) }}</textarea>
                @error('question_text') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <label class="field-label">Question Type</label>
            <div class="type-selector">
                <label>
                    <input type="radio" class="type-radio" name="question_type" id="single" value="single" {{ old('question_type', $question->question_type) == 'single' ? 'checked' : '' }}>
                    <div class="type-card">
                        <div class="type-card__icon"><i class="far fa-dot-circle"></i></div>
                        <div class="type-card__label">Single Choice</div>
                    </div>
                </label>
                <label>
                    <input type="radio" class="type-radio" name="question_type" id="multiple" value="multiple" {{ old('question_type', $question->question_type) == 'multiple' ? 'checked' : '' }}>
                    <div class="type-card">
                        <div class="type-card__icon"><i class="far fa-check-square"></i></div>
                        <div class="type-card__label">Multiple Choice</div>
                    </div>
                </label>
                <label>
                    <input type="radio" class="type-radio" name="question_type" id="file_upload" value="file_upload" {{ old('question_type', $question->question_type) == 'file_upload' ? 'checked' : '' }}>
                    <div class="type-card">
                        <div class="type-card__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="type-card__label">File Upload</div>
                    </div>
                </label>
            </div>

            {{-- MCQ Answers --}}
            <div id="mcqAnswersContainer" style="margin-bottom:1.5rem; display: {{ $question->question_type === 'file_upload' ? 'none' : 'block' }};">
                <label class="field-label">Options</label>
                <div id="answersContainer">
                    @for($i = 0; $i < 4; $i++)
                        @php
                            $ansVal = old('answers.' . $i, $question->answers[$i]->answer_text ?? '');
                            $isCorrect = false;
                            if (old('correct_answers')) {
                                $isCorrect = in_array($i, old('correct_answers', []));
                            } else {
                                $isCorrect = isset($question->answers[$i]) && $question->answers[$i]->is_correct;
                            }
                        @endphp
                        <div class="answer-row">
                            <div class="answer-row__prefix">{{ chr(65 + $i) }}</div>
                            <input type="text" class="field-input answer-row__input answer-text-input" 
                                   name="answers[]" placeholder="Option {{ chr(65 + $i) }}"
                                   value="{{ $ansVal }}" {{ $i < 2 ? 'required' : '' }}>
                            <label class="answer-row__correct">
                                <input class="correct-answer" type="{{ $question->question_type === 'multiple' ? 'checkbox' : 'radio' }}" 
                                       name="correct_answers[]" value="{{ $i }}" {{ $isCorrect ? 'checked' : '' }}>
                                <span>Correct</span>
                            </label>
                        </div>
                    @endfor
                </div>
                @error('answers') <p class="field-error" style="display:block;">{{ $message }}</p> @enderror
                @error('correct_answers') <p class="field-error" style="display:block;">{{ $message }}</p> @enderror
            </div>

            {{-- File Upload Settings --}}
            <div id="fileUploadSettings" style="display: {{ $question->question_type === 'file_upload' ? 'block' : 'none' }}; margin-bottom:1.5rem; padding:1.25rem; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-md);">
                <label class="field-label" style="margin-bottom:1rem;">File Upload Rules</label>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <div>
                        <label class="field-label" style="font-size:0.75rem;">Allowed Extensions</label>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            @php
                                $extensions = ['pdf'=>'PDF', 'doc'=>'DOC', 'docx'=>'DOCX', 'xls'=>'XLS', 'xlsx'=>'XLSX', 'jpg'=>'JPG', 'png'=>'PNG', 'zip'=>'ZIP'];
                                $savedExts = old('file_upload_settings.allowed_extensions', $question->file_upload_settings['allowed_extensions'] ?? ['pdf', 'doc', 'docx', 'jpg', 'png']);
                            @endphp
                            @foreach($extensions as $ext => $label)
                                <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.8125rem; cursor:pointer;">
                                    <input type="checkbox" name="file_upload_settings[allowed_extensions][]" value="{{ $ext }}" {{ in_array($ext, $savedExts) ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="field">
                            <label class="field-label" for="max_size_mb" style="font-size:0.75rem;">Max Size (MB)</label>
                            <input class="field-input" type="number" name="file_upload_settings[max_size_mb]" id="max_size_mb" min="1" max="100" 
                                   value="{{ old('file_upload_settings.max_size_mb', $question->file_upload_settings['max_size_mb'] ?? 10) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 2fr; gap:1.25rem; margin-bottom:1.5rem;">
                <div class="field mb-0">
                    <label class="field-label" for="marks">Marks / Points</label>
                    <input class="field-input {{ $errors->has('marks') ? 'is-error' : '' }}" 
                           type="number" step="0.25" min="0.25" max="100" 
                           id="marks" name="marks" value="{{ old('marks', $question->marks ?? '1.00') }}" required>
                    @error('marks') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field mb-0">
                    <label class="field-label" for="explanation">Explanation (Optional)</label>
                    <input class="field-input {{ $errors->has('explanation') ? 'is-error' : '' }}" 
                           type="text" id="explanation" name="explanation" value="{{ old('explanation', $question->explanation) }}"
                           placeholder="Shown to students after exam...">
                </div>
            </div>
            
            <div style="text-align:right; margin-top: 2rem;">
                <a href="{{ route('admin.add-questions', $question->exam_id) }}" class="btn btn-secondary" style="margin-right: 0.5rem;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding-left:1.5rem; padding-right:1.5rem;">
                    <i class="fas fa-save" style="margin-right:0.4rem;"></i> Update Question
                </button>
            </div>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    const typeRadios = document.querySelectorAll('.type-radio');
    const mcqContainer = document.getElementById('mcqAnswersContainer');
    const fileContainer = document.getElementById('fileUploadSettings');
    const correctCheckboxes = document.querySelectorAll('.correct-answer');

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
            } else {
                correctCheckboxes.forEach(cb => {
                    cb.type = 'checkbox';
                    cb.name = 'correct_answers[]';
                });
            }
        }
    }

    typeRadios.forEach(r => r.addEventListener('change', updateType));
    
    // ensure required state dynamically
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
</script>
@endsection