<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Report — {{ $exam->exam_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }
        .print-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 24px 32px; }
        .print-header h1 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .print-header p  { font-size: 12px; opacity: 0.85; }
        .meta-row { display: flex; gap: 32px; padding: 16px 32px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 11px; }
        .meta-row span { display: flex; gap: 6px; align-items: center; }
        .meta-row strong { color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin: 0; }
        thead th { background: #4f46e5; color: white; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: 0.4px; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #ede9fe; }
        td { padding: 9px 14px; font-size: 11px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-pass  { background: #dcfce7; color: #166534; }
        .badge-fail  { background: #fee2e2; color: #991b1b; }
        .badge-score { background: #dbeafe; color: #1d4ed8; }
        .badge-integrity { background: #fef9c3; color: #92400e; }
        .footer { padding: 16px 32px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; margin-top: auto; display: flex; justify-content: space-between; }
        .no-print { margin: 16px 32px; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            thead th { background: #4f46e5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print" style="display: flex; gap: 12px; align-items: center;">
    <button onclick="window.print()" style="background:#4f46e5;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
        🖨️ Print / Save as PDF
    </button>
    <a href="{{ route('admin.exam-results', $exam->id) }}" style="text-decoration:none;color:#64748b;font-size:13px;">
        ← Back to Results
    </a>
</div>

<div class="print-header">
    <h1>📋 Results Report</h1>
    <p>{{ $exam->exam_name }} &nbsp;|&nbsp; Exam ID: {{ $exam->exam_id }}</p>
</div>

<div class="meta-row">
    <span>Total Students: <strong>{{ $results->count() }}</strong></span>
    <span>Total Questions: <strong>{{ $exam->questions->count() }}</strong></span>
    @if($exam->pass_percentage)
        <span>Pass Mark: <strong>{{ $exam->pass_percentage }}%</strong></span>
    @endif
    @if($exam->negative_marking > 0)
        <span>Negative Marking: <strong>{{ $exam->negative_marking }} per wrong answer</strong></span>
    @endif
    <span>Generated: <strong>{{ now()->format('M d, Y H:i') }}</strong></span>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student ID</th>
            <th>Index No</th>
            <th>Score (Marks)</th>
            <th>Correct / Total</th>
            <th>Status</th>
            <th>Time Taken</th>
            <th>Integrity</th>
            <th>Submitted At</th>
        </tr>
    </thead>
    <tbody>
        @foreach($results as $i => $result)
            @php
                $tmx    = $result->total_marks > 0 ? $result->total_marks : $result->total_questions;
                $pct    = $tmx > 0 ? round(($result->score / $tmx) * 100, 1) : 0;
                $passed = $pct >= ($exam->pass_percentage ?? 40);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $result->student_id }}</strong></td>
                <td>{{ $result->index_no }}</td>
                <td>
                    <span class="badge badge-score">
                        {{ number_format((float)$result->score, 2) }} / {{ number_format((float)$tmx, 2) }}
                    </span>
                    <small>({{ $pct }}%)</small>
                </td>
                <td>{{ $result->correct_answers }} / {{ $result->total_questions }}</td>
                <td>
                    <span class="badge {{ $passed ? 'badge-pass' : 'badge-fail' }}">
                        {{ $passed ? '✓ Passed' : '✗ Failed' }}
                    </span>
                </td>
                <td>
                    @if($result->time_taken_seconds)
                        {{ floor($result->time_taken_seconds/60) }}m {{ $result->time_taken_seconds%60 }}s
                    @else N/A @endif
                </td>
                <td>
                    @if($result->tab_switch_count > 0)
                        <span class="badge badge-integrity">⚠ {{ $result->tab_switch_count }}x switch</span>
                    @else
                        <span class="badge badge-pass">✓ Clean</span>
                    @endif
                </td>
                <td>{{ $result->submitted_at->format('M d, Y H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>{{ $exam->exam_name }} — Results Report</span>
    <span>Generated by SITC Exam System on {{ now()->format('M d, Y H:i:s') }}</span>
</div>

</body>
</html>
