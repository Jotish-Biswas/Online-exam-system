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

    {{-- Header --}}
    <div class="r-header">
        <div>
            <h1 class="r-title">{{ $exam->exam_name }}</h1>
            <div class="r-meta">
                <span class="badge badge-neutral">ID: {{ $exam->exam_id }}</span>
                @if($exam->pass_percentage)
                    <span class="badge badge-info">Pass: {{ $exam->pass_percentage }}%</span>
                @endif
                @if($exam->negative_marking > 0)
                    <span class="badge badge-warning">−{{ $exam->negative_marking }} penalty</span>
                @endif
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
                    <p class="chart-card__desc">Based on {{ $exam->pass_percentage ?? 40 }}% pass mark</p>
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
                <div class="stat-pill__val" style="color:var(--color-danger);">{{ $failCount }}</div>
                <div class="stat-pill__lbl">Failed</div>
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
                            <th>Student ID</th>
                            <th>Index No</th>
                            <th>Score</th>
                            <th>Correct / Total</th>
                            <th>Status</th>
                            <th>Time Taken</th>
                            <th>Integrity</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php
                                $tmx = $result->total_marks > 0 ? $result->total_marks : $result->total_questions;
                                $rPct = $tmx > 0 ? ($result->score / $tmx) * 100 : 0;
                                $rPassed = $rPct >= ($exam->pass_percentage ?? 40);
                            @endphp
                            <tr>
                                <td><strong>{{ $result->student_id }}</strong></td>
                                <td>{{ $result->index_no }}</td>
                                <td>
                                    <span class="badge badge-neutral">
                                        {{ number_format((float)$result->score, 2) }}
                                        @if($tmx > 0)/ {{ number_format((float)$tmx, 2) }}@endif
                                    </span>
                                </td>
                                <td>{{ $result->correct_answers }} / {{ $result->total_questions }}</td>
                                <td>
                                    <span class="badge {{ $rPassed ? 'badge-success' : 'badge-danger' }}" style="margin-right:0.2rem;">
                                        {{ $rPassed ? 'Passed' : 'Failed' }}
                                    </span>
                                    <span style="font-size:0.75rem; color:var(--text-muted);">{{ number_format($rPct, 0) }}%</span>
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
    <div class="modal-overlay" id="detailModal-{{ $result->id }}" style="z-index:1000;">
        <div class="modal" style="max-width:800px; max-height:90vh; display:flex; flex-direction:column;">
            <div class="modal__header" style="background:var(--color-accent-surface);">
                <h3 class="modal__title" style="color:var(--color-accent); display:flex; align-items:center; gap:0.5rem;">
                    <i class="fas fa-user-circle"></i> {{ $result->student_id }} Details
                </h3>
                <button type="button" class="modal__close" onclick="closeDetailModal('{{ $result->id }}')"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal__body" style="overflow-y:auto; padding:1.5rem;">
                @php
                    $tmx2 = $result->total_marks > 0 ? $result->total_marks : $result->total_questions;
                    $pct2  = $tmx2 > 0 ? round(($result->score / $tmx2) * 100, 1) : 0;
                    $pass2 = $pct2 >= ($exam->pass_percentage ?? 40);
                @endphp
                
                <div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.5rem;">
                    <div style="flex-shrink:0; width:90px; height:90px; border-radius:50%; background:{{ $pass2 ? 'var(--color-success)' : 'var(--color-danger)' }}; color:white; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow:0 0 0 4px {{ $pass2 ? 'var(--color-success-bg)' : 'var(--color-danger-bg)' }};">
                        <span style="font-size:1.25rem; font-weight:800; line-height:1;">{{ $pct2 }}%</span>
                        <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase;">{{ $pass2 ? 'Pass' : 'Fail' }}</span>
                    </div>
                    <div style="flex:1; min-width:200px; display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:center;">
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Student ID</div>
                            <div style="font-weight:600;">{{ $result->student_id }}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Index No</div>
                            <div style="font-weight:600;">{{ $result->index_no }}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Score</div>
                            <div style="font-weight:600;">{{ number_format((float)$result->score,2) }} / {{ number_format((float)$tmx2,2) }}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Time</div>
                            <div style="font-weight:600;">{{ $result->time_taken_seconds ? floor($result->time_taken_seconds/60).'m '.($result->time_taken_seconds%60).'s' : 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <h4 style="font-size:1rem; font-weight:700; margin:0 0 1rem;">Question Breakdown</h4>
                <div style="display:flex; flex-direction:column; gap:1rem;">
                    @foreach($result->studentAnswers as $index => $ans)
                        <div style="border:1px solid {{ $ans->isFileUpload() ? ($ans->is_graded ? 'var(--color-success)' : 'var(--color-warning)') : ($ans->answer && $ans->answer->is_correct ? 'var(--color-success)' : 'var(--color-danger)') }}; border-radius:var(--radius-md); padding:1rem; background:var(--surface);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                <strong style="font-size:0.875rem;">Q{{ $index + 1 }}.</strong>
                                
                                @if($ans->isFileUpload())
                                    <span class="badge {{ $ans->is_graded ? 'badge-success' : 'badge-warning' }}">{{ $ans->is_graded ? 'Graded' : 'Pending' }}</span>
                                @elseif($ans->answer)
                                    <span class="badge {{ $ans->answer->is_correct ? 'badge-success' : 'badge-danger' }}">{{ $ans->answer->is_correct ? 'Correct' : 'Incorrect' }}</span>
                                @else
                                    <span class="badge badge-neutral">No Answer</span>
                                @endif
                            </div>
                            <p style="font-size:0.875rem; margin:0 0 0.75rem;">{{ $ans->question->question_text }}</p>
                            
                            <div style="font-size:0.8125rem; background:var(--surface-alt); padding:0.75rem; border-radius:var(--radius-sm);">
                                @if($ans->isFileUpload())
                                    <div style="margin-bottom:0.25rem;"><strong>File:</strong> {{ $ans->original_filename ?? 'Submission' }}</div>
                                    @if($ans->is_graded)
                                        <div><strong>Score:</strong> {{ number_format((float)$ans->manual_score, 2) }}/{{ number_format((float)($ans->question->marks ?? 1), 2) }}</div>
                                    @endif
                                @elseif($ans->answer)
                                    <div style="margin-bottom:0.25rem;">
                                        <strong>Student:</strong> <span style="color:{{ $ans->answer->is_correct ? 'var(--color-success)' : 'var(--color-danger)' }}; font-weight:500;">{{ $ans->answer->answer_text }}</span>
                                    </div>
                                    @if(!$ans->answer->is_correct)
                                        @php $corr = $ans->question->answers->where('is_correct', true)->first(); @endphp
                                        @if($corr)
                                            <div><strong>Correct:</strong> <span style="color:var(--color-success); font-weight:500;">{{ $corr->answer_text }}</span></div>
                                        @endif
                                    @endif
                                @else
                                    <div style="color:var(--text-muted); font-style:italic;">Left blank.</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
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
                    labels: ['Passed', 'Failed'],
                    datasets: [{
                        data: [{{ $passCount }}, {{ $failCount }}],
                        backgroundColor: ['#10b981', '#ef4444'],
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
        document.getElementById('retakeForm').action = `/admin/result/${id}`;
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
