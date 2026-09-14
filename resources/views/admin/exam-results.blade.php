@extends('layouts.shell')

@section('title', 'Exam Results — ' . $exam->exam_name)
@section('meta-description', 'View and analyze exam results.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Results</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-arrow-left"></i> Dashboard
</a>
@endsection

@section('head')
<style>
    .results-root {
        padding: 2.5rem 1.25rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Header */
    .r-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }
    .r-title {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--text);
        margin: 0 0 0.5rem;
    }
    .r-meta {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .r-summary-block {
        text-align: right;
    }
    .r-summary-val {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--color-accent);
        background: var(--color-accent-surface);
        padding: 0.25rem 0.75rem;
        border-radius: var(--radius-sm);
        display: inline-block;
        margin-bottom: 0.25rem;
    }
    .r-summary-lbl {
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
        .result-schedule {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
            margin-top: .9rem;
            padding: .7rem .85rem;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: .82rem;
        }
        .result-schedule strong { color: var(--text); }
        .student-cell { display: flex; flex-direction: column; gap: .2rem; }
        .student-cell small { color: var(--text-muted); font-size: .75rem; }
        .score-stack { display: flex; flex-direction: column; gap: .25rem; }
        .score-stack strong { color: var(--text); }
        .score-stack small { color: var(--text-muted); }

    /* Charts Row */
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 992px) {
        .charts-grid { grid-template-columns: 5fr 3fr 4fr; }
    }

    .chart-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
    }
    .chart-card__header {
        margin-bottom: 1rem;
    }
    .chart-card__title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--text);
        margin: 0 0 0.2rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .chart-card__title i { color: var(--text-muted); }
    .chart-card__desc {
        font-size: 0.8125rem;
        color: var(--text-muted);
        margin: 0;
    }
    .chart-wrapper {
        flex: 1;
        position: relative;
        min-height: 200px;
    }

    /* Stats Row */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 2.5rem;
    }
    @media (min-width: 768px) {
        .stats-row { grid-template-columns: repeat(4, 1fr); }
    }
    .stat-pill {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem 1rem;
        text-align: center;
    }
    .stat-pill__val { font-size: 1.75rem; font-weight: 800; line-height: 1; margin-bottom: 0.4rem; }
    .stat-pill__lbl { font-size: 0.8125rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }

    /* Actions Bar */
    .actions-bar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
    }
    .search-box {
        display: flex;
        gap: 0.5rem;
        flex: 1;
        min-width: 250px;
        max-width: 400px;
    }
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Table Card */
    .table-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
    }
    .table-card__header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-card__title {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        color: var(--text);
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th, .data-table td {
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
        font-size: 0.875rem;
    }
    .data-table th {
        background: var(--surface-alt);
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover { background: var(--surface-alt); }

    .tbl-actions {
        display: flex;
        gap: 0.4rem;
    }

    .modal-overlay.is-active {
        display: flex;
    }
</style>
@endsection

@section('content')
<div class="results-root page-fade-in">

    @php($scheduleState = $exam->scheduleState())

    {{-- Header --}}
    <div class="r-header">
        <div>
            <h1 class="r-title">{{ $exam->exam_name }}</h1>
            <div class="r-meta">
                <span class="badge badge-neutral">ID: {{ $exam->exam_id }}</span>
                @if($exam->questions->where('question_type', 'file_upload')->count() > 0)
                    <span class="badge badge-info">MCQ {{ $exam->mcqPassPercentage() }}% · Writing {{ $exam->writingPassPercentage() }}%</span>
                @elseif($exam->pass_percentage)
                    <span class="badge badge-info">MCQ pass: {{ $exam->mcqPassPercentage() }}%</span>
                @endif
                @if($exam->negative_marking > 0)
                    <span class="badge badge-warning">−{{ $exam->negative_marking }} penalty</span>
                @endif
                    <div class="result-schedule">
                        <span class="schedule-dot schedule-dot--{{ $scheduleState }}"></span>
                        <strong>{{ ucfirst($scheduleState) }}</strong>
                        @if($scheduleState === 'upcoming')
                            <span>Starts {{ $exam->start_time->format('M d, Y · h:i A') }}</span>
                        @elseif($scheduleState === 'running' && $exam->end_time)
                            <span>Running until {{ $exam->end_time->format('M d, Y · h:i A') }}</span>
                        @elseif($scheduleState === 'ended')
                            <span>Ended {{ $exam->end_time->format('M d, Y · h:i A') }}. Late attempts are not ranked.</span>
                        @else
                            <span>No scheduled window. Attempts are kept, but no rank is generated.</span>
                        @endif
                    </div>
            </div>
        </div>
        <div class="r-summary-block">
            <div class="r-summary-val">
                @if(request()->filled('search'))
                    Filtered: {{ $filteredCount }} / {{ $totalCount }}
                @else
                    {{ $totalCount }} Submissions
                @endif
            </div>
            @if($totalCount > 0)
                <div class="r-summary-lbl">Avg Score: {{ number_format($averageScore, 1) }}</div>
            @endif
        </div>
    </div>

    @if($totalCount > 0)
        {{-- Charts --}}
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title"><i class="fas fa-chart-bar"></i> Score Distribution</h3>
                    <p class="chart-card__desc">Student score breakdown by percentage range</p>
                </div>
                <div class="chart-wrapper">
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title"><i class="fas fa-chart-pie"></i> Pass/Fail Ratio</h3>
                    <p class="chart-card__desc">Overall pass needs MCQ {{ $exam->mcqPassPercentage() }}% and writing {{ $exam->writingPassPercentage() }}% (pending scripts excluded)</p>
                </div>
                <div class="chart-wrapper" style="display:flex; justify-content:center; align-items:center; flex-direction:column;">
                    <div style="height:160px; width:160px; position:relative;">
                        <canvas id="passFailChart"></canvas>
                    </div>
                    <div style="display:flex; gap:1rem; margin-top:1rem; font-size:0.8125rem;">
                        <span style="color:var(--text-secondary);"><strong style="color:var(--color-success);">{{ $passCount }}</strong> Passed</span>
                        <span style="color:var(--text-secondary);"><strong style="color:var(--color-danger);">{{ $failCount }}</strong> Failed</span>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title"><i class="fas fa-chart-line"></i> Submissions Timeline</h3>
                    <p class="chart-card__desc">Daily completion volume</p>
                </div>
                <div class="chart-wrapper">
                    <canvas id="submissionsTimeChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="stats-row">
            <div class="stat-pill">
                <div class="stat-pill__val" style="color:var(--color-accent);">{{ $totalCount }}</div>
                <div class="stat-pill__lbl">Total</div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill__val" style="color:var(--color-success);">{{ $passCount }}</div>
                <div class="stat-pill__lbl">Passed</div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill__val" style="color:var(--color-warning);">{{ $pendingCount ?? 0 }}</div>
                <div class="stat-pill__lbl">Pending writing</div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill__val" style="color:var(--text);">{{ number_format($averageScore, 1) }}</div>
                <div class="stat-pill__lbl">Avg Score</div>
            </div>
        </div>
    @endif

    {{-- Actions Bar --}}
    <div class="actions-bar">
        <form method="GET" action="{{ route('admin.exam-results', $exam->id) }}" class="search-box">
            <input type="text" class="field-input" style="margin-bottom:0;" name="search" value="{{ $searchData['search'] ?? '' }}" placeholder="Search ID or Index...">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-ghost"><i class="fas fa-times"></i></a>
            @endif
        </form>

        <div class="action-buttons">
            @if($totalCount > 0)
                <a href="{{ route('admin.download-results', $exam->id) }}" class="btn btn-outline"><i class="fas fa-file-excel"></i> Excel</a>
                <a href="{{ route('admin.export-results-csv', $exam->id) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> CSV</a>
                <a href="{{ route('admin.print-results', $exam->id) }}" target="_blank" class="btn btn-outline"><i class="fas fa-print"></i> Print</a>
                @if($exam->questions->where('question_type', 'file_upload')->count() > 0)
                    <a href="{{ route('admin.grade-submissions', $exam->id) }}" class="btn btn-primary" style="background:var(--color-warning); border-color:var(--color-warning); color:#fff;"><i class="fas fa-edit"></i> Grade Writing</a>
                @endif
            @endif
        </div>
    </div>

    {{-- Ranking --}}
    <div class="table-card" style="margin-bottom:1.5rem;">
        <div class="table-card__header">
            <div>
                <h3 class="table-card__title">Scheduled Exam Ranking</h3>
                <p style="margin:.25rem 0 0; font-size:.8125rem; color:var(--text-muted);">{{ $scheduledSubmissionCount }} on-time submission(s). Latest fully graded attempt per student is ranked.</p>
            </div>
            <span style="font-size:0.8125rem; color:var(--text-muted);">{{ $rankedResults->count() }} ranked</span>
        </div>
        @if($rankedResults->isNotEmpty())
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Performance</th>
                            <th>Result</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rankedResults as $rankedResult)
                            <?php $rankSections = $rankedResult->evaluateSections(); ?>
                            <tr>
                                <td><strong>#{{ $rankedResult->rank }}</strong></td>
                                <td><div class="student-cell"><strong>{{ $rankedResult->student_id }}</strong><small>Index {{ $rankedResult->index_no }}</small></div></td>
                                <td><div class="score-stack"><strong>{{ number_format($rankSections['obtained'], 2) }} / {{ number_format($rankSections['total'], 2) }}</strong><small>{{ $rankSections['correct_count'] }} correct · {{ max(0, $rankSections['mcq_count'] + $rankSections['writing_count'] - $rankSections['correct_count']) }} incorrect / skipped</small></div></td>
                                <td><span class="badge {{ $rankSections['overall_passed'] ? 'badge-success' : 'badge-danger' }}">{{ $rankSections['overall_passed'] ? 'Passed' : 'Failed' }}</span><small style="display:block; margin-top:.25rem; color:var(--text-muted);">{{ $rankSections['overall_percentage'] }}%</small></td>
                                <td style="color:var(--text-secondary); font-size:.8125rem;">{{ $rankedResult->submitted_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state" style="margin:1rem;">No eligible scheduled-time results are available for ranking yet.</div>
        @endif
    </div>

    {{-- Table --}}
    @if($results->count() > 0)
        <div class="table-card">
            <div class="table-card__header">
                <h3 class="table-card__title">Submissions</h3>
                <span style="font-size:0.8125rem; color:var(--text-muted);">
                    Showing {{ $results->firstItem() }} – {{ $results->lastItem() }} of {{ $results->total() }}
                </span>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Attempt</th>
                            <th>Correctness</th>
                            <th>MCQ</th>
                            <th>Writing</th>
                            <th>Overall</th>
                            <th>Schedule</th>
                            <th>Time Taken</th>
                            <th>Integrity</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <?php
                                $s = $result->evaluateSections();
                            ?>
                            <tr>
                                @php($rank = $rankByResultId->get($result->id))
                                <td><div class="student-cell"><strong>{{ $result->student_id }}</strong><small>Index {{ $result->index_no }}</small></div></td>
                                <td><div class="score-stack"><strong>#{{ $result->id }}</strong><small>{{ $rank ? 'Rank '.$rank : ($result->isRankEligible() ? 'On-time, pending rank' : 'Not rank eligible') }}</small></div></td>
                                <td><div class="score-stack"><strong>{{ $s['correct_count'] }} / {{ $s['mcq_count'] + $s['writing_count'] }}</strong><small>{{ max(0, $s['mcq_count'] + $s['writing_count'] - $s['correct_count']) }} wrong / skipped</small></div></td>
                                <td>
                                    @if($s['has_mcq'])
                                        {{ number_format($s['mcq_obtained'], 1) }}/{{ number_format($s['mcq_total'], 1) }}
                                        <span class="badge {{ $s['mcq_passed'] ? 'badge-success' : 'badge-danger' }}">{{ $s['mcq_passed'] ? 'Pass' : 'Fail' }}</span>
                                    @else
                                        <span style="color:var(--text-muted);">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$s['has_writing'])
                                        <span style="color:var(--text-muted);">—</span>
                                    @elseif(!$s['writing_fully_graded'])
                                        <span class="badge badge-warning">Pending {{ $s['writing_graded'] }}/{{ $s['writing_count'] }}</span>
                                    @else
                                        {{ number_format($s['writing_obtained'], 1) }}/{{ number_format($s['writing_total'], 1) }}
                                        <span class="badge {{ $s['writing_passed'] ? 'badge-success' : 'badge-danger' }}">{{ $s['writing_passed'] ? 'Pass' : 'Fail' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$s['overall_ready'])
                                        <span class="badge badge-neutral">Waiting</span>
                                    @else
                                        <span class="badge {{ $s['overall_passed'] ? 'badge-success' : 'badge-danger' }}">
                                            {{ $s['overall_passed'] ? 'Passed' : 'Failed' }}
                                        </span>
                                        <span style="font-size:0.75rem; color:var(--text-muted);">{{ $s['overall_percentage'] }}%</span>
                                    @endif
                                </td>
                                <td>
                                    @if($result->isRankEligible())
                                        <span class="badge badge-success">On time</span>
                                    @elseif($exam->scheduleState() === 'unscheduled')
                                        <span class="badge badge-neutral">No rank</span>
                                    @else
                                        <span class="badge badge-warning">Late · No rank</span>
                                    @endif
                                </td>
                                <td>
                                    @if($result->time_taken_seconds)
                                        {{ floor($result->time_taken_seconds / 60) }}m {{ $result->time_taken_seconds % 60 }}s
                                    @else
                                        <span style="color:var(--text-muted);">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($result->tab_switch_count > 0)
                                        <span class="badge" style="background:var(--color-danger-bg); color:var(--color-danger);">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $result->tab_switch_count }}x
                                        </span>
                                    @else
                                        <span class="badge" style="background:var(--color-success-bg); color:var(--color-success);">
                                            <i class="fas fa-shield-alt"></i> Clean
                                        </span>
                                    @endif
                                </td>
                                <td style="color:var(--text-secondary); font-size:0.8125rem;">{{ $result->submitted_at->format('M d, H:i') }}</td>
                                <td>
                                    <div class="tbl-actions">
                                        <button class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;" onclick="openDetailModal('{{ $result->id }}')">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                        @if($exam->questions->where('question_type', 'file_upload')->count() > 0)
                                        <a href="{{ route('admin.grade-desk', [$exam->id, $result->id]) }}" class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                                            <i class="fas fa-edit"></i> Grade
                                        </a>
                                        @endif
                                        <a href="{{ route('admin.print-marksheet', $result->id) }}" target="_blank" class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem;">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button class="btn btn-outline" style="padding:0.25rem 0.5rem; font-size:0.75rem; color:var(--color-danger); border-color:var(--color-danger-bg);" onclick="openRetakeModal('{{ $result->id }}', '{{ $result->student_id }}')">
                                            <i class="fas fa-redo"></i> Retake
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($results->hasPages())
                <div style="padding:1.25rem 1.5rem; border-top:1px solid var(--border); display:flex; justify-content:center;">
                    {{ $results->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    @else
        <div style="text-align:center; padding:4rem 1rem; background:var(--surface); border:1px dashed var(--border-strong); border-radius:var(--radius-xl);">
            <i class="fas fa-inbox" style="font-size:3rem; color:var(--border-strong); margin-bottom:1.5rem;"></i>
            <h2 style="font-size:1.25rem; font-weight:700; color:var(--text); margin:0 0 0.5rem;">
                {{ request()->filled('search') ? 'No Results Found' : 'No Submissions Yet' }}
            </h2>
            <p style="font-size:0.9375rem; color:var(--text-muted); margin:0;">
                {{ request()->filled('search') ? 'Try adjusting your search criteria.' : 'Students haven\'t completed this exam.' }}
            </p>
            @if(request()->filled('search'))
                <a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-secondary" style="margin-top:1.5rem;">Clear Search</a>
            @endif
        </div>
    @endif

</div>

{{-- Retake Modal --}}
<div class="modal-overlay" id="retakeModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal__header">
            <h3 class="modal__title" style="color:var(--color-warning); display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-redo"></i> Allow Retake?
            </h3>
            <button type="button" class="modal__close" onclick="closeRetakeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body">
            <p style="margin:0 0 1rem; font-size:0.9375rem; color:var(--text);">This will permanently delete the result for student <strong id="retakeStudentId"></strong> and allow them to take the exam again.</p>
            <div class="notice notice--warning">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>All their answers and scores will be erased.</span>
            </div>
        </div>
        <div class="modal__footer" style="display:flex; justify-content:flex-end; gap:0.75rem; padding:1.25rem;">
            <button type="button" class="btn btn-secondary" onclick="closeRetakeModal()">Cancel</button>
            <form id="retakeForm" method="POST" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--color-warning); border-color:var(--color-warning);">Yes, Allow Retake</button>
            </form>
        </div>
    </div>
</div>

{{-- Detail Modals --}}
@foreach($results as $result)
    @include('admin.result-detail-modal', ['result' => $result])
@endforeach

@endsection

@section('scripts')
@if($totalCount > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#64748b';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Score Dist
        const sdCtx = document.getElementById('scoreDistributionChart');
        if (sdCtx) {
            new Chart(sdCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($scoreDistributionLabels) !!},
                    datasets: [{
                        data: {!! json_encode($scoreDistributionData) !!},
                        backgroundColor: '#4f46e5',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });
        }

        // Pass/Fail
        const pfCtx = document.getElementById('passFailChart');
        if (pfCtx) {
            new Chart(pfCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Passed', 'Failed', 'Pending writing'],
                    datasets: [{
                        data: [{{ $passCount }}, {{ $failCount }}, {{ $pendingCount ?? 0 }}],
                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Timeline
        const stCtx = document.getElementById('submissionsTimeChart');
        if (stCtx) {
            const timeData = {!! json_encode($submissionsOverTime) !!};
            new Chart(stCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(timeData),
                    datasets: [{
                        data: Object.values(timeData),
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#0ea5e9'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endif
<script>
    function openRetakeModal(id, studentId) {
        document.getElementById('retakeStudentId').textContent = studentId;
        document.getElementById('retakeForm').action = "{{ route('admin.delete-result', ['result' => '__RESULT_ID__']) }}".replace('__RESULT_ID__', id);
        document.getElementById('retakeModal').classList.add('is-active');
    }
    function closeRetakeModal() {
        document.getElementById('retakeModal').classList.remove('is-active');
    }

    function openDetailModal(id) {
        document.getElementById('detailModal-' + id).classList.add('is-active');
    }
    function closeDetailModal(id) {
        document.getElementById('detailModal-' + id).classList.remove('is-active');
    }
</script>
@endsection
