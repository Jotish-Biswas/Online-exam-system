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
</style>
@endsection

@section('content')
<div class="dashboard-root page-fade-in">

    <div class="dash-header">
        <div>
            <h1 class="dash-title">Overview</h1>
            <p class="dash-subtitle">
                @if($exams->total() > 0)
                    Showing {{ $exams->firstItem() }}–{{ $exams->lastItem() }} of {{ $exams->total() }} exams
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

    {{-- Exams Grid --}}
    @if($exams->count() > 0)
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
                            <div class="exam-meta-item">
                                <i class="fas fa-question-circle"></i>
                                {{ $exam->questions->count() }} Qs
                            </div>
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
                        <a href="{{ route('admin.edit-exam', $exam->id) }}" class="action-btn action-btn--neutral">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        
                        <div style="flex:1;"></div>
                        
                        <button type="button" class="action-btn action-btn--danger" onclick="openDeleteModal('{{ $exam->id }}', '{{ addslashes($exam->exam_name) }}')" title="Delete">
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
    @else
        <div class="empty-hero">
            <div class="empty-hero__icon"><i class="fas fa-folder-open"></i></div>
            <h2 style="font-size:1.5rem; font-weight:700; color:var(--text); margin:0 0 0.5rem;">No Exams Found</h2>
            <p style="font-size:0.9375rem; color:var(--text-muted); margin:0 0 1.5rem;">You haven't created any exams yet. Start by creating one.</p>
            <a href="{{ route('admin.create-exam') }}" class="btn btn-primary">
                Create First Exam
            </a>
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
    function openDeleteModal(examId, examName) {
        document.getElementById('deleteExamName').textContent = examName;
        document.getElementById('deleteForm').action = '/admin/exam/' + examId;
        document.getElementById('deleteModal').classList.add('is-active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('is-active');
    }
</script>
@endsection