@extends('layouts.shell')

@section('title', 'Edit Exam — ' . $exam->exam_name)
@section('meta-description', 'Edit exam configuration.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Edit</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-times"></i> Cancel
</a>
@endsection

@section('head')
<style>
    .form-root {
        padding: 2.5rem 1.25rem;
        max-width: 900px;
        margin: 0 auto;
    }

    .edit-layout {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    @media (min-width: 840px) {
        .edit-layout { flex-direction: row; align-items: flex-start; }
        .edit-main { flex: 1; min-width: 0; }
        .edit-sidebar { width: 300px; flex-shrink: 0; }
    }

    .form-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
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

    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media (min-width: 640px) {
        .form-grid--2col { grid-template-columns: 1fr 1fr; }
        .form-grid--3col { grid-template-columns: repeat(3, 1fr); }
    }

    .switch-field {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: border-color 0.15s;
    }
    .switch-field:hover {
        border-color: var(--border-strong);
    }

    /* Status switch */
    .switch-field--status {
        background: var(--surface);
        border-color: var(--border-strong);
    }
    .switch-field--status:has(input:checked) {
        background: var(--color-success-bg);
        border-color: rgba(19,115,51,.25);
    }

    .switch-input {
        appearance: none;
        width: 36px;
        height: 20px;
        background: var(--border-strong);
        border-radius: 20px;
        position: relative;
        cursor: pointer;
        outline: none;
        transition: background 0.2s;
        flex-shrink: 0;
        margin-top: 0.15rem;
    }
    .switch-input::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: white;
        border-radius: 50%;
        transition: transform 0.2s;
    }
    .switch-input:checked {
        background: var(--color-success);
    }
    .switch-input:checked::after {
        transform: translateX(16px);
    }

    .switch-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.4;
        display: block;
        margin-bottom: 0.25rem;
    }
    .switch-desc {
        font-size: 0.75rem;
        color: var(--text-muted);
        line-height: 1.4;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 2rem;
    }

    /* Sidebar info block */
    .info-block {
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    .info-block__title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    .info-row:last-child { margin-bottom: 0; }
    .info-row__label { color: var(--text-muted); }
    .info-row__val { color: var(--text); font-weight: 500; }
</style>
@endsection

@section('content')
<div class="form-root page-fade-in">

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text); margin: 0 0 0.5rem; letter-spacing: -0.02em;">Edit Exam</h1>
        <p style="font-size: 0.9375rem; color: var(--text-muted); margin: 0;">Update configuration and security settings.</p>
    </div>

    <form action="{{ route('admin.update-exam', $exam->id) }}" method="POST" novalidate>
        @csrf
        @method('PUT')
        
        <div class="edit-layout">
            
            {{-- Main Form Content --}}
            <div class="edit-main">
                
                {{-- Status --}}
                <label class="switch-field switch-field--status" for="is_active" style="margin-bottom:1.5rem;">
                    <input type="checkbox" class="switch-input" id="is_active" name="is_active" value="1" {{ old('is_active', $exam->is_active) ? 'checked' : '' }}>
                    <div>
                        <span class="switch-label">Exam is Active</span>
                        <span class="switch-desc">When active, students can see and attempt this exam (if within schedule window).</span>
                    </div>
                </label>

                {{-- General Details --}}
                <div class="form-section">
                    <div class="form-section__header">
                        <i class="fas fa-info-circle"></i> General Details
                    </div>
                    
                    <div class="form-grid">
                        <div class="field">
                            <label class="field-label" for="exam_id">Exam ID</label>
                            <input class="field-input {{ $errors->has('exam_id') ? 'is-error' : '' }}" 
                                   type="text" id="exam_id" name="exam_id" value="{{ old('exam_id', $exam->exam_id) }}" 
                                   placeholder="e.g. PHY-101-MIDTERM" required>
                            @error('exam_id') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label class="field-label" for="exam_name">Exam Name</label>
                            <input class="field-input {{ $errors->has('exam_name') ? 'is-error' : '' }}" 
                                   type="text" id="exam_name" name="exam_name" value="{{ old('exam_name', $exam->exam_name) }}" 
                                   placeholder="e.g. Physics 101 Midterm" required>
                            @error('exam_name') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label class="field-label" for="description">Description (Optional)</label>
                            <textarea class="field-input {{ $errors->has('description') ? 'is-error' : '' }}" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Brief instructions or details...">{{ old('description', $exam->description) }}</textarea>
                            @error('description') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Configuration --}}
                <div class="form-section">
                    <div class="form-section__header">
                        <i class="fas fa-sliders-h"></i> Configuration
                    </div>
                    
                    <div class="form-grid form-grid--3col">
                        <div class="field">
                            <label class="field-label" for="duration_minutes">Duration (Minutes)</label>
                            <input class="field-input {{ $errors->has('duration_minutes') ? 'is-error' : '' }}" 
                                   type="number" id="duration_minutes" name="duration_minutes" 
                                   value="{{ old('duration_minutes', $exam->duration_minutes ?? 30) }}" min="1" max="1440" required>
                            @error('duration_minutes') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label class="field-label" for="negative_marking">Negative Mark</label>
                            <input class="field-input {{ $errors->has('negative_marking') ? 'is-error' : '' }}" 
                                   type="number" id="negative_marking" name="negative_marking" 
                                   value="{{ old('negative_marking', $exam->negative_marking ?? '0.00') }}" step="0.05" min="0" max="10">
                            @error('negative_marking') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid form-grid--2col" style="margin-top:1.25rem;">
                        <div class="field">
                            <label class="field-label" for="mcq_pass_percentage">MCQ pass %</label>
                            <input class="field-input {{ $errors->has('mcq_pass_percentage') ? 'is-error' : '' }}"
                                   type="number" id="mcq_pass_percentage" name="mcq_pass_percentage"
                                   value="{{ old('mcq_pass_percentage', $exam->mcq_pass_percentage ?? $exam->pass_percentage ?? 40) }}" min="1" max="100">
                            @error('mcq_pass_percentage') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label class="field-label" for="writing_pass_percentage">Writing pass %</label>
                            <input class="field-input {{ $errors->has('writing_pass_percentage') ? 'is-error' : '' }}"
                                   type="number" id="writing_pass_percentage" name="writing_pass_percentage"
                                   value="{{ old('writing_pass_percentage', $exam->writing_pass_percentage ?? $exam->pass_percentage ?? 40) }}" min="1" max="100">
                            <p class="field-hint">Overall pass requires both sections.</p>
                            @error('writing_pass_percentage') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Schedule Window --}}
                <div class="form-section">
                    <div class="form-section__header">
                        <i class="far fa-calendar-alt"></i> Schedule Window <span style="font-size:0.75rem; font-weight:500; color:var(--text-muted); margin-left:auto;">Optional</span>
                    </div>
                    
                    <div class="form-grid form-grid--2col">
                        <div class="field">
                            <label class="field-label" for="start_time">Available From</label>
                            <input class="field-input" type="datetime-local" id="start_time" name="start_time" 
                                   value="{{ old('start_time', $exam->start_time ? $exam->start_time->format('Y-m-d\TH:i') : '') }}">
                        </div>
                        <div class="field">
                            <label class="field-label" for="end_time">Closes At</label>
                            <input class="field-input" type="datetime-local" id="end_time" name="end_time" 
                                   value="{{ old('end_time', $exam->end_time ? $exam->end_time->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>
                </div>

                {{-- Security & Randomization --}}
                <div class="form-section">
                    <div class="form-section__header">
                        <i class="fas fa-shield-alt"></i> Security & Integrity
                    </div>

                    <div class="form-grid">
                        <label class="switch-field" for="shuffle_questions">
                            <input type="checkbox" class="switch-input" id="shuffle_questions" name="shuffle_questions" value="1" {{ old('shuffle_questions', $exam->shuffle_questions) ? 'checked' : '' }}>
                            <div>
                                <span class="switch-label">Randomize Questions</span>
                                <span class="switch-desc">Shuffle the question sequence uniquely for each student.</span>
                            </div>
                        </label>

                        <label class="switch-field" for="shuffle_options">
                            <input type="checkbox" class="switch-input" id="shuffle_options" name="shuffle_options" value="1" {{ old('shuffle_options', $exam->shuffle_options) ? 'checked' : '' }}>
                            <div>
                                <span class="switch-label">Randomize Options</span>
                                <span class="switch-desc">Shuffle MCQ answer options (A, B, C, D) for each question.</span>
                            </div>
                        </label>

                        <label class="switch-field" for="enable_anti_cheating">
                            <input type="checkbox" class="switch-input" id="enable_anti_cheating" name="enable_anti_cheating" value="1" {{ old('enable_anti_cheating', $exam->enable_anti_cheating) ? 'checked' : '' }}>
                            <div>
                                <span class="switch-label">Anti-Cheating Monitoring</span>
                                <span class="switch-desc">Track tab switching, block copy/paste, and disable right-click.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding-left:1.5rem; padding-right:1.5rem;">
                        <i class="fas fa-save" style="margin-right:0.3rem;"></i> Save Changes
                    </button>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="edit-sidebar">
                
                <div class="info-block">
                    <div class="info-block__title"><i class="fas fa-info-circle"></i> Info</div>
                    <div class="info-row">
                        <span class="info-row__label">Questions</span>
                        <span class="info-row__val">{{ $exam->questions->count() }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-row__label">Created</span>
                        <span class="info-row__val">{{ $exam->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-row__label">Updated</span>
                        <span class="info-row__val">{{ $exam->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <div class="info-block">
                    <div class="info-block__title"><i class="fas fa-bolt"></i> Actions</div>
                    <a href="{{ route('admin.add-questions', $exam->id) }}" class="btn btn-outline" style="width:100%; justify-content:center; margin-bottom:0.75rem;">
                        <i class="fas fa-layer-group"></i> Manage Questions
                    </a>
                    <a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-outline" style="width:100%; justify-content:center;">
                        <i class="fas fa-chart-bar"></i> View Results
                    </a>
                </div>

            </div>

        </div>
    </form>

</div>
@endsection