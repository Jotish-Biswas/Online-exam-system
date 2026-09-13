@extends('layouts.shell')

@section('title', 'Exam Preview — ' . $exam->exam_name)
@section('meta-description', 'Exam instructions and details for ' . $exam->exam_name)

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">ID: {{ $exam->exam_id }}</span>
</div>
@endsection

@section('nav-actions')
<div class="shell-nav__info" style="text-align:right; display:none; flex-direction:column;" id="studentInfoNav">
    <span style="font-weight:500; color:var(--text); font-size:0.8rem;">{{ session('student_id') }}</span>
    <span style="font-size:0.75rem; color:var(--text-muted);">Roll: {{ session('index_no') }}</span>
</div>
<script>document.getElementById('studentInfoNav').style.display='flex';</script>
@endsection

@section('head')
<style>
    .preview-root {
        padding: 2.5rem 1.25rem;
        max-width: 680px;
        margin: 0 auto;
    }

    /* Hero */
    .preview-hero {
        text-align: center;
        margin-bottom: 2rem;
    }

    .preview-hero__title {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.5rem;
        color: var(--text);
    }

    .preview-hero__desc {
        font-size: 0.9375rem;
        color: var(--text-secondary);
        margin: 0 auto;
        max-width: 480px;
        line-height: 1.6;
    }

    /* Stats row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem 1rem;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .stat-card__num {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-card__label {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    /* Instructions list */
    .instructions-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .instruction-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
    }

    .instruction-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.9rem;
    }

    .icon-info { background: var(--color-accent-surface); color: var(--color-accent); }
    .icon-success { background: var(--color-success-bg); color: var(--color-success); }
    .icon-warning { background: var(--color-warning-bg); color: var(--color-warning); }
    .icon-danger { background: var(--color-danger-bg); color: var(--color-danger); }
    .icon-neutral { background: var(--surface-alt); color: var(--text-muted); }

    .instruction-text {
        font-size: 0.875rem;
        line-height: 1.5;
        color: var(--text-secondary);
        margin: 0;
    }

    .instruction-text strong {
        color: var(--text);
        font-weight: 600;
    }

    /* Footer / Start */
    .preview-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
        flex-wrap: wrap;
    }
</style>
@endsection

@section('content')
<div class="preview-root page-fade-in">

    {{-- Hero --}}
    <div class="preview-hero">
        <h1 class="preview-hero__title">{{ $exam->exam_name }}</h1>
        @if($exam->description)
            <p class="preview-hero__desc">{{ $exam->description }}</p>
        @else
            <p class="preview-hero__desc">Please review the rules and exam format before starting.</p>
        @endif
    </div>

    {{-- Stats Row --}}
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-card__num">{{ $totalQuestions }}</div>
            <div class="stat-card__label">Questions</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num">{{ $totalMarks }}</div>
            <div class="stat-card__label">Total Marks</div>
        </div>
        @if($exam->duration_minutes)
        <div class="stat-card">
            <div class="stat-card__num">{{ $exam->duration_minutes }}</div>
            <div class="stat-card__label">Minutes</div>
        </div>
        @endif
        <div class="stat-card">
            <div class="stat-card__num">{{ $exam->pass_percentage ?? 40 }}%</div>
            <div class="stat-card__label">Pass Mark</div>
        </div>
        @if($hasNegative)
        <div class="stat-card" style="border-color: rgba(183,28,28,.2); background: var(--color-danger-bg);">
            <div class="stat-card__num" style="color: var(--color-danger);">−{{ $exam->negative_marking }}</div>
            <div class="stat-card__label" style="color: var(--color-danger);">Wrong Answer</div>
        </div>
        @endif
    </div>

    {{-- Anti-cheating notice --}}
    @if($exam->enable_anti_cheating)
    <div class="notice notice--danger" style="margin-bottom: 1.5rem;">
        <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <div>
            <strong style="display:block; margin-bottom:0.25rem;">Anti-Cheating Monitored Exam</strong>
            <span style="font-size:0.8125rem;">This exam monitors browser tab switches. Leaving the page will trigger a violation and may submit your exam early. Copying and pasting are disabled.</span>
        </div>
    </div>
    @endif

    {{-- Instructions --}}
    <h3 style="font-size:1rem; font-weight:600; color:var(--text); margin:0 0 1rem;">Exam Instructions</h3>
    <div class="instructions-list">

        @if($hasMCQ)
        <div class="instruction-item">
            <div class="instruction-icon icon-info"><i class="fas fa-check-square"></i></div>
            <p class="instruction-text">
                <strong>Multiple Choice:</strong> Select the correct answer. Some questions may require multiple selections to be correct.
            </p>
        </div>
        @endif

        @if($hasFileUpload)
        <div class="instruction-item">
            <div class="instruction-icon icon-info"><i class="fas fa-paperclip"></i></div>
            <p class="instruction-text">
                <strong>File Uploads:</strong> You will need to upload files for certain questions. Pay attention to the allowed file format and max size limits.
            </p>
        </div>
        @endif

        @if($hasNegative)
        <div class="instruction-item">
            <div class="instruction-icon icon-danger"><i class="fas fa-minus-circle"></i></div>
            <p class="instruction-text">
                <strong>Negative Marking:</strong> <strong>{{ $exam->negative_marking }} mark(s)</strong> will be deducted for every incorrect choice. Unanswered questions do not incur a penalty.
            </p>
        </div>
        @endif

        @if($exam->shuffle_questions || $exam->shuffle_options)
        <div class="instruction-item">
            <div class="instruction-icon icon-warning"><i class="fas fa-random"></i></div>
            <p class="instruction-text">
                <strong>Randomized Content:</strong> Your question and answer order is randomized uniquely for you.
            </p>
        </div>
        @endif

        <div class="instruction-item">
            <div class="instruction-icon icon-success"><i class="fas fa-cloud-upload-alt"></i></div>
            <p class="instruction-text">
                <strong>Progress Auto-Saves:</strong> Your answers are saved locally as you type. If you accidentally refresh, you won't lose your work.
            </p>
        </div>

        @if($exam->duration_minutes)
        <div class="instruction-item">
            <div class="instruction-icon icon-neutral"><i class="far fa-clock"></i></div>
            <p class="instruction-text">
                <strong>Timed Exam:</strong> The timer begins when you click Start. The exam will submit automatically when time runs out.
            </p>
        </div>
        @endif

    </div>

    {{-- Footer --}}
    <div class="preview-footer">
        <div>
            <div style="font-size:0.8125rem; color:var(--text-muted); margin-bottom:0.25rem;">Logged in as <strong>{{ session('student_id') }}</strong></div>
            <span class="status-pill status-pill--active">
                <span class="badge-dot"></span> Ready to begin
            </span>
        </div>
        <a href="{{ route('student.exam', $exam->id) }}" class="btn btn-primary btn-lg" id="startExamBtn" style="padding-left:1.75rem; padding-right:1.75rem;">
            Start Exam
            <i class="fas fa-arrow-right" style="margin-left:0.25rem;"></i>
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script>
    // Prevent accidental back navigation
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, null, location.href);
    });
</script>
@endsection
