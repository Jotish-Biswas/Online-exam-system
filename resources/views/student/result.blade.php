@extends('layouts.shell')

@section('title', 'Exam Result — ' . $exam->exam_name)
@section('meta-description', 'Your result for ' . $exam->exam_name)

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Result</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('student.login') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-home"></i> Home
</a>
@endsection

@section('head')
<style>
    .result-root {
        padding: 2.5rem 1.25rem;
        max-width: 680px;
        margin: 0 auto;
    }

    /* Score banner */
    .score-banner {
        text-align: center;
        padding: 2.5rem 1.5rem;
        border-radius: var(--radius-lg);
        margin-bottom: 1.5rem;
        border: 1px solid var(--border);
    }

    .score-banner--pass {
        background: var(--color-success-bg);
        border-color: rgba(19,115,51,.2);
    }

    .score-banner--fail {
        background: var(--color-danger-bg);
        border-color: rgba(183,28,28,.2);
    }

    .score-ring-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 1.5rem;
    }

    /* We use the app.css score-ring */
    .score-ring__track {
        stroke: var(--surface);
    }
    .score-banner--pass .score-ring__fill { stroke: var(--color-success); }
    .score-banner--fail .score-ring__fill { stroke: var(--color-danger); }

    .score-banner__title {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.25rem;
    }

    .score-banner--pass .score-banner__title { color: var(--color-success); }
    .score-banner--fail .score-banner__title { color: var(--color-danger); }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 480px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    .stat-box {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 1rem 0.5rem;
        text-align: center;
    }

    .stat-box__num {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-box__label {
        font-size: 0.7rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    /* Details card */
    .details-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .details-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.875rem;
    }
    .details-row:last-child { border-bottom: none; padding-bottom: 0; }

    .details-row__label { color: var(--text-secondary); font-weight: 500; }
    .details-row__val { color: var(--text); text-align: right; }

    /* Action bar */
    .action-bar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
    }
    @media (min-width: 480px) {
        .action-bar { flex-direction: row; align-items: center; justify-content: space-between; }
    }
</style>
@endsection

@section('content')
@php
    $sections = $examResult->evaluateSections();
    $hasWriting = $sections['has_writing'];
    $writingPending = $hasWriting && !$sections['writing_fully_graded'];
    $mcqPct = $sections['mcq_percentage'] ?? 0;
    $radius = 60;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - (($sections['has_mcq'] ? $mcqPct : 0) / 100) * $circumference;
@endphp

<div class="result-root page-fade-in">

    @if($writingPending)
        <div class="score-banner" style="background:var(--color-accent-surface); border-color:rgba(10,102,194,.2);">
            <div class="score-ring-wrap">
                <div class="score-ring">
                    <svg viewBox="0 0 140 140">
                        <circle class="score-ring__track" cx="70" cy="70" r="{{ $radius }}"></circle>
                        <circle class="score-ring__fill" cx="70" cy="70" r="{{ $radius }}"
                                style="stroke:var(--color-accent);"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $offset }}"></circle>
                    </svg>
                    <div class="score-ring__label">
                        @if($sections['has_mcq'])
                            <span style="font-size:1.75rem; font-weight:800; color:var(--text); line-height:1;">{{ $mcqPct }}%</span>
                            <span style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.1rem;">MCQ only</span>
                        @else
                            <span style="font-size:1.1rem; font-weight:700;">Submitted</span>
                        @endif
                    </div>
                </div>
            </div>

            <h1 class="score-banner__title" style="color:var(--color-accent);">Exam submitted</h1>
            <p style="font-size:0.9375rem; color:var(--text-secondary); margin:0;">
                Your MCQ marks are ready now. Writing marks and overall result will appear after the teacher grades your scripts.
            </p>
        </div>
    @else
        @php $passed = $sections['overall_passed']; $pct = $sections['overall_percentage'] ?? 0; $offsetAll = $circumference - ($pct / 100) * $circumference; @endphp
        <div class="score-banner {{ $passed ? 'score-banner--pass' : 'score-banner--fail' }}">
            <div class="score-ring-wrap">
                <div class="score-ring">
                    <svg viewBox="0 0 140 140">
                        <circle class="score-ring__track" cx="70" cy="70" r="{{ $radius }}"></circle>
                        <circle class="score-ring__fill" cx="70" cy="70" r="{{ $radius }}"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $offsetAll }}"></circle>
                    </svg>
                    <div class="score-ring__label">
                        <span style="font-size:1.75rem; font-weight:800; color:var(--text); line-height:1;">{{ $pct }}%</span>
                        <span style="font-size:0.75rem; color:var(--text-secondary); margin-top:0.1rem;">
                            {{ number_format($sections['obtained'], 2) }} / {{ number_format($sections['total'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
            <h1 class="score-banner__title">
                {{ $passed ? 'Congratulations, you passed!' : 'Exam Completed' }}
            </h1>
            <p style="font-size:0.9375rem; color:var(--text-secondary); margin:0;">
                {{ $passed ? 'You passed both required sections.' : 'You need to pass MCQ and writing separately.' }}
            </p>
            <div style="margin-top:1rem;">
                <span class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}" style="font-size:0.875rem; padding:0.4rem 0.8rem;">
                    {{ $passed ? 'PASSED' : 'FAILED' }}
                </span>
            </div>
        </div>
    @endif

    <div class="stats-grid">
        @if($sections['has_mcq'])
        <div class="stat-box">
            <div class="stat-box__num" style="color:{{ $sections['mcq_passed'] ? 'var(--color-success)' : 'var(--color-danger)' }};">
                {{ number_format($sections['mcq_obtained'], 1) }}/{{ number_format($sections['mcq_total'], 1) }}
            </div>
            <div class="stat-box__label">MCQ · {{ $sections['mcq_percentage'] }}% · pass {{ $sections['mcq_pass_mark'] }}%</div>
        </div>
        @endif
        @if($hasWriting)
        <div class="stat-box">
            <div class="stat-box__num" style="color:var(--text-muted);">
                {{ $writingPending ? '—' : number_format($sections['writing_obtained'], 1) . '/' . number_format($sections['writing_total'], 1) }}
            </div>
            <div class="stat-box__label">{{ $writingPending ? 'Writing pending' : 'Writing · pass '.$sections['writing_pass_mark'].'%' }}</div>
        </div>
        @endif
        <div class="stat-box">
            <div class="stat-box__num">
                @if($writingPending)
                    Hidden
                @else
                    {{ $sections['overall_passed'] ? 'Pass' : 'Fail' }}
                @endif
            </div>
            <div class="stat-box__label">Overall</div>
        </div>
        <div class="stat-box">
            <div class="stat-box__num">
                @if($examResult->time_taken_seconds)
                    {{ floor($examResult->time_taken_seconds / 60) }}m {{ $examResult->time_taken_seconds % 60 }}s
                @else
                    &mdash;
                @endif
            </div>
            <div class="stat-box__label">Time Taken</div>
        </div>
    </div>

    {{-- Details Card --}}
    <div class="details-card">
        <h3 style="font-size:1rem; font-weight:600; color:var(--text); margin:0 0 1rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-file-invoice text-accent"></i> Submission Details
        </h3>

        <div class="details-row">
            <span class="details-row__label">Student ID</span>
            <span class="details-row__val">{{ $examResult->student_id }}</span>
        </div>
        <div class="details-row">
            <span class="details-row__label">Index Number</span>
            <span class="details-row__val">{{ $examResult->index_no }}</span>
        </div>
        <div class="details-row">
            <span class="details-row__label">Exam ID</span>
            <span class="details-row__val">{{ $exam->exam_id }}</span>
        </div>
        <div class="details-row">
            <span class="details-row__label">Submitted At</span>
            <span class="details-row__val">{{ $examResult->submitted_at->format('M d, Y h:i A') }}</span>
        </div>
        @if($exam->negative_marking > 0)
        <div class="details-row">
            <span class="details-row__label text-danger">Negative Marking Applied</span>
            <span class="details-row__val text-danger">−{{ $exam->negative_marking }} per wrong answer</span>
        </div>
        @endif

        {{-- Integrity Notice --}}
        @if($examResult->tab_switch_count > 0)
            <div class="notice notice--warning" style="margin-top:1.25rem;">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span><strong>Integrity Note:</strong> {{ $examResult->tab_switch_count }} tab switch(es) recorded during this session.</span>
            </div>
        @else
            <div class="notice notice--success" style="margin-top:1.25rem;">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
                <span><strong>Integrity Verified:</strong> No tab switches recorded.</span>
            </div>
        @endif
    </div>

    {{-- Action Bar --}}
    <div class="action-bar">
        <div style="flex:1;">
            <div style="font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:0.25rem;">
                {{ $writingPending ? 'Writing is with your teacher' : 'Review your answers' }}
            </div>
            <div style="font-size:0.75rem; color:var(--text-muted);">
                {{ $writingPending ? 'Come back with Exam ID + Student ID + Index to see writing feedback.' : 'See which MCQ items you got right, plus writing comments.' }}
            </div>
        </div>
        <a href="{{ route('student.checkResultsForm') }}" class="btn btn-primary" style="flex-shrink:0;">
            {{ $writingPending ? 'Check later for writing marks' : 'View Detailed Breakdown' }}
            <i class="fas fa-arrow-right" style="margin-left:0.3rem;"></i>
        </a>
    </div>

</div>
@endsection
