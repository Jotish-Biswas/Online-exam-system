@extends('layouts.shell')

@section('title', 'Create New Exam')
@section('meta-description', 'Create a new exam.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap;">
        New Exam
    </span>
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
        max-width: 720px;
        margin: 0 auto;
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

    /* Simple custom checkbox to simulate switch */
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
</style>
@endsection

@section('content')
<div class="form-root page-fade-in">

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text); margin: 0 0 0.5rem; letter-spacing: -0.02em;">Create Exam</h1>
        <p style="font-size: 0.9375rem; color: var(--text-muted); margin: 0;">Configure basic details, scoring, and security rules.</p>
    </div>

    <form action="{{ route('admin.store-exam') }}" method="POST" novalidate>
        @csrf
        
        {{-- General Details --}}
        <div class="form-section">
            <div class="form-section__header">
                <i class="fas fa-info-circle"></i> General Details
            </div>
            
            <div class="form-grid">
                <div class="field">
                    <label class="field-label" for="exam_id">Exam ID</label>
                    <input class="field-input {{ $errors->has('exam_id') ? 'is-error' : '' }}" 
                           type="text" id="exam_id" name="exam_id" value="{{ old('exam_id') }}" 
                           placeholder="e.g. PHY-101-MIDTERM" required>
                    <p class="field-hint">Students will use this exact ID to log in.</p>
                    @error('exam_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="exam_name">Exam Name</label>
                    <input class="field-input {{ $errors->has('exam_name') ? 'is-error' : '' }}" 
                           type="text" id="exam_name" name="exam_name" value="{{ old('exam_name') }}" 
                           placeholder="e.g. Physics 101 Midterm" required>
                    @error('exam_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="description">Description (Optional)</label>
                    <textarea class="field-input {{ $errors->has('description') ? 'is-error' : '' }}" 
                              id="description" name="description" rows="3" 
                              placeholder="Brief instructions or details...">{{ old('description') }}</textarea>
                    @error('description') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Exam Source --}}
        <div class="form-section">
            <div class="form-section__header">
                <i class="fas fa-sitemap"></i> Exam Source
            </div>
            <div class="form-grid" style="margin-bottom:1.25rem;">
                <label class="switch-field" for="creation_mode_structured">
                    <input type="radio" class="switch-input" id="creation_mode_structured" name="creation_mode" value="structured" {{ old('creation_mode', 'structured') === 'structured' ? 'checked' : '' }}>
                    <div><span class="switch-label">Use academic architecture</span><span class="switch-desc">Organize this exam under a group, subject and selected chapters.</span></div>
                </label>
                <label class="switch-field" for="creation_mode_manual">
                    <input type="radio" class="switch-input" id="creation_mode_manual" name="creation_mode" value="manual" {{ old('creation_mode') === 'manual' ? 'checked' : '' }}>
                    <div><span class="switch-label">Manual / random exam</span><span class="switch-desc">Skip academic grouping and build a free-form exam.</span></div>
                </label>
            </div>
            <div id="architecture-fields" class="form-grid form-grid--2col">
                <div class="field">
                    <label class="field-label" for="academic_group_id">Academic Group</label>
                    <select class="field-input" id="academic_group_id" name="academic_group_id">
                        <option value="">Choose group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ (string) old('academic_group_id') === (string) $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_group_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label class="field-label" for="academic_subject_id">Subject / Paper</label>
                    <select class="field-input" id="academic_subject_id" name="academic_subject_id"><option value="">Choose subject</option></select>
                    @error('academic_subject_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field" style="grid-column:1/-1;">
                    <label class="field-label" for="chapters">Chapters</label>
                    <select class="field-input" id="chapters" name="chapters[]" multiple size="6"><option value="">Choose a subject first</option></select>
                    <p class="field-hint">Hold Ctrl / Cmd to select multiple chapters.</p>
                    @error('chapters') <p class="field-error">{{ $message }}</p> @enderror
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
                           value="{{ old('duration_minutes', 30) }}" min="1" max="1440" required>
                    @error('duration_minutes') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="negative_marking">Negative Mark</label>
                    <input class="field-input {{ $errors->has('negative_marking') ? 'is-error' : '' }}" 
                           type="number" id="negative_marking" name="negative_marking" 
                           value="{{ old('negative_marking', '0.00') }}" step="0.05" min="0" max="10">
                    <p class="field-hint">0.00 for no penalty (MCQ only).</p>
                    @error('negative_marking') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-grid form-grid--2col" style="margin-top:1.25rem;">
                <div class="field">
                    <label class="field-label" for="mcq_pass_percentage">MCQ pass %</label>
                    <input class="field-input {{ $errors->has('mcq_pass_percentage') ? 'is-error' : '' }}"
                           type="number" id="mcq_pass_percentage" name="mcq_pass_percentage"
                           value="{{ old('mcq_pass_percentage', 40) }}" min="1" max="100">
                    <p class="field-hint">Students must pass MCQ on its own.</p>
                    @error('mcq_pass_percentage') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label class="field-label" for="writing_pass_percentage">Writing pass %</label>
                    <input class="field-input {{ $errors->has('writing_pass_percentage') ? 'is-error' : '' }}"
                           type="number" id="writing_pass_percentage" name="writing_pass_percentage"
                           value="{{ old('writing_pass_percentage', 40) }}" min="1" max="100">
                    <p class="field-hint">Writing is graded separately. Overall pass needs both.</p>
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
                    <input class="field-input" type="datetime-local" id="start_time" name="start_time" value="{{ old('start_time') }}">
                </div>
                <div class="field">
                    <label class="field-label" for="end_time">Closes At</label>
                    <input class="field-input" type="datetime-local" id="end_time" name="end_time" value="{{ old('end_time') }}">
                </div>
            </div>
            <p style="font-size:0.8125rem; color:var(--text-muted); margin:0.5rem 0 0;">Students can only take the exam during this time window.</p>
        </div>

        {{-- Security & Randomization --}}
        <div class="form-section">
            <div class="form-section__header">
                <i class="fas fa-shield-alt"></i> Security & Integrity
            </div>

            <div class="form-grid">
                <label class="switch-field" for="shuffle_questions">
                    <input type="checkbox" class="switch-input" id="shuffle_questions" name="shuffle_questions" value="1" {{ old('shuffle_questions', true) ? 'checked' : '' }}>
                    <div>
                        <span class="switch-label">Randomize Questions</span>
                        <span class="switch-desc">Shuffle the question sequence uniquely for each student.</span>
                    </div>
                </label>

                <label class="switch-field" for="shuffle_options">
                    <input type="checkbox" class="switch-input" id="shuffle_options" name="shuffle_options" value="1" {{ old('shuffle_options', true) ? 'checked' : '' }}>
                    <div>
                        <span class="switch-label">Randomize Options</span>
                        <span class="switch-desc">Shuffle MCQ answer options (A, B, C, D) for each question.</span>
                    </div>
                </label>

                <label class="switch-field" for="enable_anti_cheating">
                    <input type="checkbox" class="switch-input" id="enable_anti_cheating" name="enable_anti_cheating" value="1" {{ old('enable_anti_cheating', true) ? 'checked' : '' }}>
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
                Save & Continue <i class="fas fa-arrow-right" style="margin-left:0.25rem;"></i>
            </button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
(() => {
    const groups = @json($groups);
    const groupSelect = document.getElementById('academic_group_id');
    const subjectSelect = document.getElementById('academic_subject_id');
    const chapterSelect = document.getElementById('chapters');
    const fields = document.getElementById('architecture-fields');
    const oldSubject = @json(old('academic_subject_id'));
    const oldChapters = @json(old('chapters', []));

    function refreshSubjects() {
        const group = groups.find(item => String(item.id) === groupSelect.value);
        subjectSelect.innerHTML = '<option value="">Choose subject</option>';
        (group?.subjects || []).forEach(subject => subjectSelect.add(
            new Option(subject.name, subject.id, false, String(subject.id) === String(oldSubject))
        ));
        refreshChapters();
    }
    function refreshChapters() {
        const group = groups.find(item => String(item.id) === groupSelect.value);
        const subject = (group?.subjects || []).find(item => String(item.id) === subjectSelect.value);
        chapterSelect.innerHTML = '';
        if (!subject) {
            chapterSelect.add(new Option('Choose a subject first', ''));
            return;
        }
        subject.chapters.forEach(chapter => chapterSelect.add(new Option(
            `${chapter.sort_order + 1}. ${chapter.title}`, chapter.id, false,
            oldChapters.map(String).includes(String(chapter.id))
        )));
    }
    function toggleArchitecture() {
        const structured = document.querySelector('input[name="creation_mode"]:checked').value === 'structured';
        fields.hidden = !structured;
        groupSelect.required = subjectSelect.required = chapterSelect.required = structured;
    }
    groupSelect.addEventListener('change', refreshSubjects);
    subjectSelect.addEventListener('change', refreshChapters);
    document.querySelectorAll('input[name="creation_mode"]').forEach(input => input.addEventListener('change', toggleArchitecture));
    refreshSubjects();
    toggleArchitecture();
})();
</script>
@endsection
