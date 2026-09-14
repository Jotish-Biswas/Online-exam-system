@extends('layouts.shell')

@section('title', 'Admin Dashboard')
@section('meta-description', 'Manage exams and review submissions.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap;">
        Dashboard
    </span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.manage-admins') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="Manage Admins">
    <i class="fas fa-users-cog"></i> <span class="nav-text-hidden">Admins</span>
</a>
<a href="{{ route('admin.students') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="Manage Students">
    <i class="fas fa-user-graduate"></i> <span class="nav-text-hidden">Students</span>
</a>
<a href="{{ route('admin.ai-question-generator') }}" class="btn btn-primary" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="AI Question Generator">
    <i class="fas fa-wand-magic-sparkles"></i> <span class="nav-text-hidden">AI Questions</span>
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
    .dashboard-root {
        padding: 2.5rem 1.25rem;
        max-width: 1080px;
        margin: 0 auto;
    }

    /* Header */
    .dash-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .dash-title {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: var(--text);
        margin: 0 0 0.25rem;
    }
    
    .dash-subtitle {
        font-size: 0.9375rem;
        color: var(--text-muted);
        margin: 0;
    }

    .exam-schedule { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; padding:.65rem .75rem; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--surface-alt); font-size:.78rem; color:var(--text-secondary); }
    .exam-schedule strong { color:var(--text); }
    .schedule-dot { width:7px; height:7px; border-radius:50%; display:inline-block; }
    .schedule-dot--running { background:var(--color-success); }
    .schedule-dot--upcoming { background:var(--color-accent); }
    .schedule-dot--ended { background:var(--color-warning); }
    .schedule-dot--unscheduled { background:var(--text-muted); }

    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 2.5rem;
    }
    @media (min-width: 768px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        box-shadow: var(--shadow-sm);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .stat-icon--exams { background: var(--color-accent-surface); color: var(--color-accent); }
    .stat-icon--subs { background: var(--color-success-bg); color: var(--color-success); }
    .stat-icon--avg { background: var(--surface-alt); color: var(--text); }
    .stat-icon--pass { background: var(--color-warning-bg); color: var(--color-warning); }

    .stat-val {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text);
        line-height: 1;
        letter-spacing: -0.02em;
    }
    .stat-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Exam Cards */
    .exam-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.25rem;
    }

    .exam-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s;
    }
    .exam-card:hover {
        box-shadow: var(--shadow-md);
    }

    .exam-card__header {
        padding: 1.5rem 1.5rem 1rem;
    }
    
    .exam-card__eyebrow {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .exam-card__id {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: var(--color-accent);
        background: var(--color-accent-surface);
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-sm);
    }

    .exam-card__title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
        line-height: 1.3;
    }

    .exam-card__body {
        padding: 0 1.5rem 1.5rem;
        flex: 1;
    }

    .exam-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        margin-bottom: 1rem;
    }
    .exam-meta-item {
        font-size: 0.8125rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .exam-card__desc {
        font-size: 0.875rem;
        color: var(--text-muted);
        line-height: 1.5;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .exam-card__footer {
        padding: 1rem 1.5rem;
        background: var(--surface-alt);
        border-top: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.4rem 0.75rem;
        border-radius: var(--radius-md);
        text-decoration: none;
        transition: all 0.15s;
        border: 1px solid transparent;
        cursor: pointer;
        background: transparent;
    }
    .action-btn--primary { color: var(--color-accent); background: var(--color-accent-surface); }
    .action-btn--primary:hover { background: var(--color-accent); color: white; }
    
    .action-btn--success { color: var(--color-success); background: var(--color-success-bg); }
    .action-btn--success:hover { background: var(--color-success); color: white; }
    
    .action-btn--neutral { color: var(--text-secondary); border-color: var(--border); background: var(--surface); }
    .action-btn--neutral:hover { background: var(--surface-alt); color: var(--text); border-color: var(--border-strong); }
    
    .action-btn--danger { color: var(--color-danger); }
    .action-btn--danger:hover { background: var(--color-danger-bg); }

    /* Empty state */
    .empty-hero {
        text-align: center;
        padding: 4rem 1.5rem;
        background: var(--surface);
        border: 1px dashed var(--border-strong);
        border-radius: var(--radius-xl);
    }
    .empty-hero__icon {
        font-size: 3rem;
        color: var(--border-strong);
        margin-bottom: 1rem;
    }

    @media (max-width: 640px) {
        .nav-text-hidden { display: none; }
    }

    .library-tabs { display:flex; flex-wrap:wrap; gap:.6rem; margin:2rem 0 1.25rem; }
    .library-tab { border:1px solid var(--border); background:var(--surface); color:var(--text-secondary); border-radius:var(--radius-md); padding:.7rem 1rem; font-weight:700; cursor:pointer; }
    .library-tab.is-active { background:var(--color-accent); border-color:var(--color-accent); color:#fff; }
    .library-panel { display:none; }
    .library-panel.is-active { display:block; }
    .library-filters { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.9rem; padding:1.25rem; margin-bottom:1.25rem; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); }
    .library-empty { padding:2rem; text-align:center; color:var(--text-muted); border:1px dashed var(--border-strong); border-radius:var(--radius-lg); }
    @media(max-width:700px) { .library-filters { grid-template-columns:1fr; } }
</style>
@endsection

@section('content')
<div class="dashboard-root page-fade-in">

    <div class="dash-header">
        <div>
            <h1 class="dash-title">Overview</h1>
            <p class="dash-subtitle">
                @if($stats['total_exams'] > 0)
                    {{ $stats['total_exams'] }} exam{{ $stats['total_exams'] === 1 ? '' : 's' }} in your library
                @else
                    No exams found. Create one to get started.
                @endif
            </p>
        </div>
        <a href="{{ route('admin.create-exam') }}" class="btn btn-primary" style="padding:0.6rem 1.25rem;">
            <i class="fas fa-plus"></i> New Exam
        </a>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon--exams"><i class="fas fa-file-alt"></i></div>
            <div>
                <div class="stat-val">{{ $stats['total_exams'] }}</div>
                <div class="stat-label">Total Exams</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--subs"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-val">{{ $stats['total_submissions'] }}</div>
                <div class="stat-label">Submissions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--avg"><i class="fas fa-percentage"></i></div>
            <div>
                <div class="stat-val">{{ $stats['avg_score_pct'] }}%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--pass"><i class="fas fa-trophy"></i></div>
            <div>
                <div class="stat-val">{{ $stats['overall_pass_rate'] }}%</div>
                <div class="stat-label">Pass Rate</div>
            </div>
        </div>
    </div>

    @if(($stats['pending_writing'] ?? 0) > 0)
        <div class="notice notice--warning" style="margin-bottom:1.75rem;">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><strong>{{ $stats['pending_writing'] }}</strong> writing script(s) are waiting. Open an exam’s Grade desk to mark and send feedback.</span>
        </div>
    @endif

    <div class="library-tabs" role="tablist" aria-label="Exam library views">
        <button class="library-tab is-active" type="button" data-library-tab="structured"><i class="fas fa-sitemap"></i> Structured exams</button>
        <button class="library-tab" type="button" data-library-tab="manual"><i class="fas fa-random"></i> Manual exams</button>
        <button class="library-tab" type="button" data-library-tab="active"><i class="fas fa-layer-group"></i> Show all exams</button>
    </div>

    <section class="library-panel is-active" data-library-panel="structured">
        <div class="library-filters">
            <div class="field"><label class="field-label" for="library-group">Group</label><select class="field-input" id="library-group"><option value="">Select group</option>@foreach($architectureGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach<option value="__create_group">＋ Create new group</option><option value="__delete_group">− Delete selected group</option></select></div>
            <div class="field"><label class="field-label" for="library-subject">Subject / Paper</label><select class="field-input" id="library-subject" disabled><option value="">Select subject</option><option value="__create_subject">＋ Create new subject</option><option value="__delete_subject">− Delete selected subject</option></select></div>
            <div class="field"><label class="field-label" for="library-chapter">Chapter</label><select class="field-input" id="library-chapter" disabled><option value="">Select chapter</option><option value="__create_chapter">＋ Create new chapter</option><option value="__delete_chapter">− Delete selected chapter</option></select></div>
        </div>
        <div id="structured-results" class="library-empty">Select a group, subject and chapter to view its exams.</div>
    </section>

    <section class="library-panel" data-library-panel="manual">
        @php($manualExams = $exams->where('creation_mode', 'manual'))
        @if($manualExams->isNotEmpty())
        <div class="exam-grid">@foreach($manualExams as $exam)
            <div class="exam-card">
                @php($scheduleState = $exam->scheduleState())
                <div class="exam-card__header"><div class="exam-card__eyebrow"><span class="exam-card__id">{{ $exam->exam_id }}</span><span class="status-pill {{ $exam->is_active ? 'status-pill--active' : 'status-pill--inactive' }}">{{ $exam->is_active ? 'Active' : 'Deactive' }}</span></div><h3 class="exam-card__title">{{ $exam->exam_name }}</h3></div>
                <div class="exam-schedule"><span class="schedule-dot schedule-dot--{{ $scheduleState }}"></span><strong>{{ ucfirst($scheduleState) }}</strong>@if($scheduleState === 'upcoming')<span>Starts {{ $exam->start_time->format('M d, Y · h:i A') }}</span>@elseif($scheduleState === 'running' && $exam->end_time)<span>Until {{ $exam->end_time->format('M d, Y · h:i A') }}</span>@elseif($scheduleState === 'ended')<span>Ended {{ $exam->end_time->format('M d, Y · h:i A') }}</span>@else<span>Available anytime</span>@endif</div>
                <div class="exam-meta"><span>{{ $exam->questions->count() }} Qs</span><span>{{ $exam->duration_minutes }}m</span><span>{{ $exam->exam_results_count }} submissions</span></div>
                <div class="exam-card__footer"><a href="{{ route('admin.add-questions', $exam->id) }}" class="action-btn action-btn--primary">Builder</a><a href="{{ route('admin.exam-results', $exam->id) }}" class="action-btn action-btn--success">Results</a>@if($exam->questions->where('question_type', 'file_upload')->count() > 0)<a href="{{ route('admin.grade-submissions', $exam->id) }}" class="action-btn action-btn--neutral">Grade</a>@endif<a href="{{ route('admin.edit-exam', $exam->id) }}" class="action-btn action-btn--neutral">Edit</a><form action="{{ route('admin.toggle-status', $exam->id) }}" method="POST" style="margin:0;">@csrf<button class="action-btn {{ $exam->is_active ? 'action-btn--danger' : 'action-btn--success' }}" type="submit">{{ $exam->is_active ? 'Deactive' : 'Active' }}</button></form><button type="button" class="action-btn action-btn--danger" onclick="openDeleteModal({{ $exam->id }}, @js($exam->exam_name))">Delete</button></div>
            </div>
        @endforeach</div>
        @else <div class="library-empty">No manual exams have been created.</div>@endif
    </section>

    <section class="library-panel" data-library-panel="active">
        @if($exams->isNotEmpty())
        <div class="exam-grid">@foreach($exams as $exam)
            <div class="exam-card">
                @php($examChapter = $exam->chapters->first())
                @php($scheduleState = $exam->scheduleState())
                <div class="exam-card__header">
                    <div class="exam-card__eyebrow"><span class="exam-card__id">{{ $exam->exam_id }}</span><span class="status-pill {{ $exam->is_active ? 'status-pill--active' : 'status-pill--inactive' }}">{{ $exam->is_active ? 'Active' : 'Deactive' }}</span></div>
                    <div class="exam-card__eyebrow" style="margin-top:.35rem;color:var(--text-muted);">
                        @if($exam->creation_mode === 'structured' && $exam->academicGroup && $exam->academicSubject)
                            {{ $exam->academicGroup->name }} / {{ $exam->academicSubject->name }} / {{ $examChapter?->title ?? 'Chapter' }}
                        @else
                            Manual exam
                        @endif
                    </div>
                    <h3 class="exam-card__title" style="margin-top:.5rem;">{{ $exam->exam_name }}</h3>
                </div>
                <div class="exam-schedule"><span class="schedule-dot schedule-dot--{{ $scheduleState }}"></span><strong>{{ ucfirst($scheduleState) }}</strong>@if($scheduleState === 'upcoming')<span>Starts {{ $exam->start_time->format('M d, Y · h:i A') }}</span>@elseif($scheduleState === 'running' && $exam->end_time)<span>Until {{ $exam->end_time->format('M d, Y · h:i A') }}</span>@elseif($scheduleState === 'ended')<span>Ended {{ $exam->end_time->format('M d, Y · h:i A') }}</span>@else<span>Available anytime</span>@endif</div>
                <div class="exam-meta"><span>{{ $exam->questions->count() }} Qs</span><span>{{ $exam->duration_minutes }}m</span><span>{{ $exam->exam_results_count }} submissions</span></div>
                <div class="exam-card__footer"><a href="{{ route('admin.add-questions', $exam->id) }}" class="action-btn action-btn--primary">Builder</a><a href="{{ route('admin.exam-results', $exam->id) }}" class="action-btn action-btn--success">Results</a>@if($exam->questions->where('question_type', 'file_upload')->count() > 0)<a href="{{ route('admin.grade-submissions', $exam->id) }}" class="action-btn action-btn--neutral">Grade</a>@endif<a href="{{ route('admin.edit-exam', $exam->id) }}" class="action-btn action-btn--neutral">Edit</a><form action="{{ route('admin.toggle-status', $exam->id) }}" method="POST" style="margin:0;">@csrf<button class="action-btn {{ $exam->is_active ? 'action-btn--danger' : 'action-btn--success' }}" type="submit">{{ $exam->is_active ? 'Deactive' : 'Active' }}</button></form><button type="button" class="action-btn action-btn--danger" onclick="openDeleteModal({{ $exam->id }}, @js($exam->exam_name))">Delete</button></div>
            </div>
        @endforeach</div>
        @else <div class="library-empty">There are no exams yet.</div>@endif
    </section>

    {{-- Retain the full exam card actions below for compatibility with existing dashboard actions. --}}
    @if(false)
        <div class="exam-grid">
            @foreach($exams as $exam)
                <div class="exam-card">
                    <div class="exam-card__header">
                        <div class="exam-card__eyebrow">
                            <span class="exam-card__id">{{ $exam->exam_id }}</span>
                            <span class="status-pill {{ $exam->is_active ? 'status-pill--active' : 'status-pill--inactive' }}">
                                <span class="badge-dot"></span>
                                {{ $exam->is_active ? 'Active' : 'Draft' }}
                            </span>
                        </div>
                        <h3 class="exam-card__title">{{ $exam->exam_name }}</h3>
                    </div>

                    <div class="exam-card__body">
                        <div class="exam-meta">
                            @if($exam->creation_mode === 'structured' && $exam->academicSubject)
                            <div class="exam-meta-item"><i class="fas fa-sitemap"></i> {{ $exam->academicGroup?->name }} · {{ $exam->academicSubject->name }}</div>
                            @else
                            <div class="exam-meta-item"><i class="fas fa-random"></i> Manual exam</div>
                            @endif
                            <div class="exam-meta-item">
                                <i class="fas fa-question-circle"></i>
                                {{ $exam->questions->count() }} Qs
                            </div>

                            @section('scripts')
                            <script>
                            (() => {
                                const groups = @json($architectureGroups);
                                const group = document.getElementById('library-group');
                                const subject = document.getElementById('library-subject');
                                const chapter = document.getElementById('library-chapter');
                                const results = document.getElementById('structured-results');
                                const examCard = exam => `<div class="exam-card"><div class="exam-card__header"><div class="exam-card__eyebrow"><span class="exam-card__id">${exam.exam_id}</span><span class="status-pill ${exam.is_active ? 'status-pill--active' : 'status-pill--inactive'}">${exam.is_active ? 'Active' : 'Deactive'}</span></div><h3 class="exam-card__title">${exam.exam_name}</h3></div><div class="exam-meta"><span>${exam.questions_count} Qs</span><span>${exam.is_active ? 'Active' : 'Deactive'}</span></div><div style="display:flex;gap:.4rem;flex-wrap:wrap;"><a href="/examadmin/exam/${exam.id}/questions" class="action-btn action-btn--primary">Builder</a><form action="/examadmin/exam/${exam.id}/toggle-status" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="action-btn ${exam.is_active ? 'action-btn--danger' : 'action-btn--success'}" type="submit">${exam.is_active ? 'Deactive' : 'Active'}</button></form></div></div>`;
                                function reset(select, label) { select.innerHTML = `<option value="">${label}</option>`; select.disabled = true; }
                                function render() {
                                    const selected = groups.find(item => String(item.id) === group.value)?.subjects.find(item => String(item.id) === subject.value)?.chapters.find(item => String(item.id) === chapter.value);
                                    if (!selected || !selected.exams.length) { results.className = 'library-empty'; results.innerHTML = selected ? 'No exam has been created for this chapter yet.' : 'Select a group, subject and chapter to view its exams.'; return; }
                                    results.className = 'exam-grid'; results.innerHTML = selected.exams.map(examCard).join('');
                                }
                                group.addEventListener('change', () => { reset(subject, 'Select subject'); reset(chapter, 'Select chapter'); const item = groups.find(item => String(item.id) === group.value); (item?.subjects || []).forEach(value => subject.add(new Option(value.name, value.id))); subject.disabled = !item; results.className = 'library-empty'; results.textContent = 'Select a subject and chapter to view its exams.'; });
                                subject.addEventListener('change', () => { reset(chapter, 'Select chapter'); const item = groups.find(item => String(item.id) === group.value)?.subjects.find(item => String(item.id) === subject.value); (item?.chapters || []).forEach(value => chapter.add(new Option(value.title, value.id))); chapter.disabled = !item; render(); });
                                chapter.addEventListener('change', render);
                                document.querySelectorAll('[data-library-tab]').forEach(tab => tab.addEventListener('click', () => { document.querySelectorAll('[data-library-tab], [data-library-panel]').forEach(item => item.classList.remove('is-active')); tab.classList.add('is-active'); document.querySelector(`[data-library-panel="${tab.dataset.libraryTab}"]`).classList.add('is-active'); }));
                            })();
                            </script>
                            @endsection
                            @if($exam->duration_minutes)
                            <div class="exam-meta-item">
                                <i class="far fa-clock"></i>
                                {{ $exam->duration_minutes }}m
                            </div>
                            @endif
                            <div class="exam-meta-item">
                                <i class="far fa-calendar-alt"></i>
                                {{ $exam->created_at->format('M d, Y') }}
                            </div>
                        </div>
                        @if($exam->description)
                            <p class="exam-card__desc">{{ $exam->description }}</p>
                        @endif
                    </div>

                    <div class="exam-card__footer">
                        <a href="{{ route('admin.add-questions', $exam->id) }}" class="action-btn action-btn--primary">
                            <i class="fas fa-layer-group"></i> Builder
                        </a>
                        <a href="{{ route('admin.exam-results', $exam->id) }}" class="action-btn action-btn--success">
                            <i class="fas fa-chart-bar"></i> Results
                        </a>
                        @if($exam->questions->where('question_type', 'file_upload')->count() > 0)
                        <a href="{{ route('admin.grade-submissions', $exam->id) }}" class="action-btn action-btn--neutral">
                            <i class="fas fa-edit"></i> Grade
                        </a>
                        @endif
                        <a href="{{ route('admin.edit-exam', $exam->id) }}" class="action-btn action-btn--neutral">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        <form action="{{ route('admin.toggle-status', $exam->id) }}" method="POST" style="margin:0;">
                            @csrf
                            <button class="action-btn {{ $exam->is_active ? 'action-btn--danger' : 'action-btn--success' }}" type="submit">
                                <i class="fas {{ $exam->is_active ? 'fa-eye-slash' : 'fa-bullhorn' }}"></i>
                                {{ $exam->is_active ? 'Deactive' : 'Active' }}
                            </button>
                        </form>
                        
                        <div style="flex:1;"></div>
                        
                        <button type="button" class="action-btn action-btn--danger" onclick="openDeleteModal({{ $exam->id }}, @js($exam->exam_name))" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination (Uses Tailwind classes implicitly via Laravel Paginator, but we can wrap it) --}}
        <div style="margin-top: 3rem; display:flex; justify-content:center;">
            {{ $exams->links('pagination::tailwind') }}
        </div>
    @endif

</div>

{{-- Custom CSS Modal for Delete Confirmation --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal__header">
            <h3 class="modal__title" style="color:var(--color-danger); display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-exclamation-triangle"></i> Delete Exam
            </h3>
            <button type="button" class="modal__close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body">
            <p style="margin:0 0 1rem; font-size:0.9375rem; color:var(--text);">Are you sure you want to delete <strong id="deleteExamName"></strong>?</p>
            <div class="notice notice--danger">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>This will permanently delete all questions, answers, and student results. This action cannot be undone.</span>
            </div>
        </div>
        <div class="modal__footer" style="display:flex; gap:0.75rem; justify-content:flex-end; padding:1.25rem;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--color-danger); border-color:var(--color-danger);">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const groups = @json($architectureGroups);
    const group = document.getElementById('library-group');
    const subject = document.getElementById('library-subject');
    const chapter = document.getElementById('library-chapter');
    const results = document.getElementById('structured-results');
    if (!group || !subject || !chapter || !results) return;

    const csrf = @json(csrf_token());
    const submitPrompt = (action, fields) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        form.innerHTML = `<input type="hidden" name="_token" value="${csrf}">`;
        for (const field of fields) {
            const value = field.value ?? window.prompt(field.label);
            if (!value) return;
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="${field.name}" value="${String(value).replace(/"/g, '&quot;')}">`);
        }
        document.body.appendChild(form);
        form.submit();
    };
    const refreshSubjects = () => {
        subject.innerHTML = '<option value="">Select subject</option><option value="__create_subject">＋ Create new subject</option><option value="__delete_subject">− Delete selected subject</option>';
        chapter.innerHTML = '<option value="">Select chapter</option><option value="__create_chapter">＋ Create new chapter</option><option value="__delete_chapter">− Delete selected chapter</option>';
        chapter.disabled = true;
        const selected = groups.find(item => String(item.id) === group.value);
        (selected?.subjects || []).forEach(item => subject.add(new Option(item.name, item.id)));
        subject.disabled = !selected;
    };
    const refreshChapters = () => {
        chapter.innerHTML = '<option value="">Select chapter</option><option value="__create_chapter">＋ Create new chapter</option><option value="__delete_chapter">− Delete selected chapter</option>';
        const selected = groups.find(item => String(item.id) === group.value)?.subjects.find(item => String(item.id) === subject.value);
        (selected?.chapters || []).forEach(item => chapter.add(new Option(item.title, item.id)));
        chapter.disabled = !selected;
        renderResults();
    };
    const renderResults = () => {
        const selectedGroup = groups.find(item => String(item.id) === group.value);
        const selectedSubject = selectedGroup?.subjects.find(item => String(item.id) === subject.value);
        const selected = selectedSubject?.chapters.find(item => String(item.id) === chapter.value);
        if (!selected) {
            results.className = 'library-empty';
            results.textContent = 'Select a group, subject and chapter to view its exams.';
            return;
        }
        if (!selected.exams.length) {
            results.className = 'library-empty';
            results.textContent = 'No exam has been created for this chapter yet.';
            return;
        }
        results.className = 'exam-grid';
        results.innerHTML = selected.exams.map(exam => {
            const gradeAction = Number(exam.file_upload_questions_count) > 0
                ? `<a href="/examadmin/exam/${exam.id}/grade-submissions" class="action-btn action-btn--neutral">Grade</a>`
                : '';
            const now = new Date();
            const starts = exam.start_time ? new Date(exam.start_time) : null;
            const ends = exam.end_time ? new Date(exam.end_time) : null;
            const scheduleState = starts && starts > now ? 'upcoming' : (ends && ends < now ? 'ended' : (starts || ends ? 'running' : 'unscheduled'));
            const scheduleText = scheduleState === 'upcoming' ? `Starts ${starts.toLocaleString()}` : scheduleState === 'ended' ? `Ended ${ends.toLocaleString()}` : scheduleState === 'running' && ends ? `Until ${ends.toLocaleString()}` : 'Available anytime';
            const examName = JSON.stringify(exam.exam_name);
            return `<div class="exam-card">
                <div class="exam-card__header">
                    <div class="exam-card__eyebrow"><span class="exam-card__id">${exam.exam_id}</span><span class="status-pill ${exam.is_active ? 'status-pill--active' : 'status-pill--inactive'}">${exam.is_active ? 'Active' : 'Deactive'}</span></div>
                    <div class="exam-card__eyebrow" style="margin-top:.35rem;color:var(--text-muted);">${selectedGroup.name} / ${selectedSubject.name} / ${selected.title}</div>
                    <h3 class="exam-card__title">${exam.exam_name}</h3>
                </div>
                <div class="exam-schedule"><span class="schedule-dot schedule-dot--${scheduleState}"></span><strong>${scheduleState[0].toUpperCase() + scheduleState.slice(1)}</strong><span>${scheduleText}</span></div>
                <div class="exam-meta"><span>${exam.questions_count} Qs</span><span>${exam.duration_minutes ?? ''}m</span><span>${exam.exam_results_count ?? 0} submissions</span></div>
                ${exam.description ? `<p class="exam-card__desc">${exam.description}</p>` : ''}
                <div class="exam-meta"><span>Created ${exam.created_at ? new Date(exam.created_at).toLocaleDateString() : ''}</span></div>
                <div class="exam-card__footer">
                    <a href="/examadmin/exam/${exam.id}/questions" class="action-btn action-btn--primary">Builder</a>
                    <a href="/examadmin/exam/${exam.id}/results" class="action-btn action-btn--success">Results</a>
                    ${gradeAction}
                    <a href="/examadmin/exam/${exam.id}/edit" class="action-btn action-btn--neutral">Edit</a>
                    <form action="/examadmin/exam/${exam.id}/toggle-status" method="POST" style="margin:0;"><input type="hidden" name="_token" value="${csrf}"><button class="action-btn ${exam.is_active ? 'action-btn--danger' : 'action-btn--success'}" type="submit">${exam.is_active ? 'Deactive' : 'Active'}</button></form>
                    <button type="button" class="action-btn action-btn--danger" onclick="openDeleteModal(${exam.id}, ${examName})">Delete</button>
                </div>
            </div>`;
        }).join('');
    };
    group.addEventListener('change', () => {
        if (group.value === '__create_group') {
            submitPrompt('{{ route('admin.architecture.groups.store') }}', [{name: 'Group name'}]);
            group.value = '';
            return;
        }
        if (group.value === '__delete_group') {
            if (!confirm('Delete this group? It must not contain subjects or exams.')) return;
            submitDelete(`/examadmin/architecture/groups/${group.dataset.selected || ''}`);
            return;
        }
        refreshSubjects();
        group.dataset.selected = group.value;
        renderResults();
    });
    subject.addEventListener('change', () => {
        if (subject.value === '__create_subject') {
            if (!group.value) return;
            submitPrompt('{{ route('admin.architecture.subjects.store') }}', [{name: 'name', label: 'Subject / paper name'}, {name: 'academic_group_id', value: group.value}]);
            subject.value = '';
            return;
        }
        if (subject.value === '__delete_subject') {
            if (!confirm('Delete this subject? It must not contain chapters or exams.')) return;
            submitDelete(`/examadmin/architecture/subjects/${subject.dataset.selected || ''}`);
            return;
        }
        refreshChapters();
        subject.dataset.selected = subject.value;
    });
    chapter.addEventListener('change', () => {
        if (chapter.value === '__create_chapter') {
            if (!subject.value) return;
            submitPrompt('{{ route('admin.architecture.chapters.store') }}', [{name: 'title', label: 'Chapter name'}, {name: 'academic_subject_id', value: subject.value}]);
            chapter.value = '';
            return;
        }
        if (chapter.value === '__delete_chapter') {
            if (!confirm('Delete this chapter? It must not contain exams.')) return;
            submitDelete(`/examadmin/architecture/chapters/${chapter.dataset.selected || ''}`);
            return;
        }
        chapter.dataset.selected = chapter.value;
        renderResults();
    });
    document.querySelectorAll('[data-library-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-library-tab], [data-library-panel]').forEach(item => item.classList.remove('is-active'));
        tab.classList.add('is-active');
        document.querySelector(`[data-library-panel="${tab.dataset.libraryTab}"]`).classList.add('is-active');
    }));
    const submitDelete = (action) => {
        if (!action.endsWith('/')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            form.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(form);
            form.submit();
        }
    };
})();

    function openDeleteModal(examId, examName) {
        document.getElementById('deleteExamName').textContent = examName;
        document.getElementById('deleteForm').action = "{{ route('admin.delete-exam', ['exam' => '__EXAM_ID__']) }}".replace('__EXAM_ID__', examId);
        document.getElementById('deleteModal').classList.add('is-active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('is-active');
    }
</script>
@endsection