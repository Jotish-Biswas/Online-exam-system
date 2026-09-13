<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marksheet — {{ $examResult->student_id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1e293b; background: #fff; max-width: 800px; margin: 0 auto; }
        /* Print button */
        .no-print { padding: 16px; display: flex; gap: 12px; align-items: center; border-bottom: 1px solid #e2e8f0; }
        /* Header */
        .marksheet-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 28px 32px; text-align: center; }
        .marksheet-header h1 { font-size: 22px; font-weight: 800; letter-spacing: 2px; margin-bottom: 4px; }
        .marksheet-header p { opacity: 0.85; font-size: 13px; }
        /* Institution name */
        .institution { text-align: center; padding: 16px; border-bottom: 3px double #4f46e5; }
        .institution h2 { font-size: 18px; font-weight: 700; color: #4f46e5; }
        .institution p { font-size: 11px; color: #64748b; }
        /* Info table */
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 7px 16px; border: 1px solid #e2e8f0; font-size: 11px; }
        .info-table td:first-child { font-weight: 600; background: #f8fafc; color: #4f46e5; width: 35%; }
        /* Score summary */
        .score-section { display: flex; gap: 0; border: 2px solid #4f46e5; border-radius: 12px; overflow: hidden; margin: 20px 16px; }
        .score-box { flex: 1; text-align: center; padding: 16px 8px; }
        .score-box:not(:last-child) { border-right: 1px solid #e2e8f0; }
        .score-box .val { font-size: 22px; font-weight: 800; color: #4f46e5; }
        .score-box .lbl { font-size: 10px; color: #64748b; margin-top: 2px; }
        .pass-banner { text-align: center; padding: 12px; font-size: 18px; font-weight: 800; letter-spacing: 2px; }
        .pass-banner.pass { background: #dcfce7; color: #166534; border: 2px solid #22c55e; border-radius: 8px; margin: 0 16px 16px; }
        .pass-banner.fail { background: #fee2e2; color: #991b1b; border: 2px solid #ef4444; border-radius: 8px; margin: 0 16px 16px; }
        /* Q breakdown */
        .q-table { width: 100%; border-collapse: collapse; margin: 0 0 20px; font-size: 11px; }
        .q-table th { background: #4f46e5; color: white; padding: 8px 12px; text-align: left; }
        .q-table td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; }
        .q-table tr:nth-child(even) td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 700; }
        .badge-correct { background: #dcfce7; color: #166534; }
        .badge-wrong   { background: #fee2e2; color: #991b1b; }
        .badge-skip    { background: #fef9c3; color: #92400e; }
        /* Footer */
        .footer { border-top: 2px solid #4f46e5; padding: 12px 16px; font-size: 10px; color: #64748b; display: flex; justify-content: space-between; }
        .signature-line { display: flex; justify-content: space-around; margin: 24px 16px 12px; gap: 20px; }
        .sig { text-align: center; flex: 1; }
        .sig .line { border-top: 1px solid #475569; margin-bottom: 4px; }
        .sig .label { font-size: 10px; color: #64748b; }
        @media print {
            .no-print { display: none !important; }
            body { max-width: 100%; }
            .marksheet-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .pass-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .q-table th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .score-section { border: 2px solid #4f46e5 !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="background:#4f46e5;color:white;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
        🖨️ Print / Save as PDF
    </button>
    <a href="{{ route('admin.exam-results', $exam->id) }}" style="text-decoration:none;color:#64748b;font-size:13px;">
        ← Back to Results
    </a>
</div>

<div class="institution">
    <h2>SITC Examination System</h2>
    <p>Official Result Marksheet</p>
</div>

<div class="marksheet-header">
    <h1>RESULT MARKSHEET</h1>
    <p>{{ $exam->exam_name }} &nbsp;&bull;&nbsp; Exam ID: {{ $exam->exam_id }}</p>
</div>

{{-- Student Info --}}
<table class="info-table" style="margin: 16px;">
    <tr>
        <td>Student ID</td>
        <td>{{ $examResult->student_id }}</td>
        <td>Index Number</td>
        <td>{{ $examResult->index_no }}</td>
    </tr>
    <tr>
        <td>Exam Name</td>
        <td>{{ $exam->exam_name }}</td>
        <td>Exam ID</td>
        <td>{{ $exam->exam_id }}</td>
    </tr>
    <tr>
        <td>Date of Submission</td>
        <td>{{ $examResult->submitted_at->format('M d, Y H:i:s') }}</td>
        <td>Time Taken</td>
        <td>
            @if($examResult->time_taken_seconds)
                {{ floor($examResult->time_taken_seconds/60) }}m {{ $examResult->time_taken_seconds%60 }}s
            @else N/A @endif
        </td>
    </tr>
    @if($exam->negative_marking > 0)
        <tr>
            <td>Negative Marking</td>
            <td>{{ $exam->negative_marking }} per wrong answer</td>
            <td>Pass Mark</td>
            <td>{{ $exam->pass_percentage ?? 40 }}%</td>
        </tr>
    @endif
</table>

{{-- Score Summary --}}
@php
    $tmx    = $examResult->total_marks > 0 ? $examResult->total_marks : $exam->questions->count();
    $pct    = $tmx > 0 ? round(($examResult->score / $tmx) * 100, 1) : 0;
    $passed = $examResult->isPassed();
@endphp
<div class="score-section">
    <div class="score-box">
        <div class="val">{{ number_format((float)$examResult->score, 2) }}</div>
        <div class="lbl">Marks Obtained</div>
    </div>
    <div class="score-box">
        <div class="val">{{ number_format((float)$tmx, 2) }}</div>
        <div class="lbl">Total Marks</div>
    </div>
    <div class="score-box">
        <div class="val">{{ $pct }}%</div>
        <div class="lbl">Percentage</div>
    </div>
    <div class="score-box">
        <div class="val">{{ $examResult->correct_answers }}</div>
        <div class="lbl">Correct Answers</div>
    </div>
    <div class="score-box">
        <div class="val">{{ $examResult->total_questions }}</div>
        <div class="lbl">Total Questions</div>
    </div>
</div>

<div class="pass-banner {{ $passed ? 'pass' : 'fail' }}">
    @if($passed) ✅ PASSED @else ❌ FAILED @endif
    &nbsp;&nbsp;|&nbsp;&nbsp; {{ $pct }}%
    @if($exam->pass_percentage)
        &nbsp;&nbsp;(Pass: {{ $exam->pass_percentage }}%)
    @endif
</div>

{{-- Question Breakdown --}}
<div style="padding: 0 16px 8px; font-size:12px; font-weight:600; color:#4f46e5;">
    Question-wise Performance
</div>
<table class="q-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Question</th>
            <th>Your Answer</th>
            <th>Status</th>
            <th>Marks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($exam->questions as $qIdx => $question)
            @php
                $sa = $examResult->studentAnswers->where('question_id', $question->id)->first();
                $qMarks = (float)($question->marks ?? 1.0);
                $answered = $sa !== null;
                if ($question->question_type === 'file_upload') {
                    $isCorrect = $sa && $sa->is_graded && (float)$sa->manual_score >= $qMarks;
                    $earnedMarks = ($sa && $sa->is_graded) ? min($qMarks, max(0, (float)$sa->manual_score)) : 0;
                } elseif ($question->question_type === 'multiple') {
                    $selectedIds = $examResult->studentAnswers->where('question_id', $question->id)->pluck('answer_id')->filter()->toArray();
                    $correctIds  = $question->answers->where('is_correct', true)->pluck('id')->toArray();
                    sort($selectedIds); sort($correctIds);
                    $isCorrect = $selectedIds === $correctIds && !empty($selectedIds);
                    $earnedMarks = $isCorrect ? $qMarks : ($answered && $exam->negative_marking > 0 ? -$exam->negative_marking : 0);
                } else {
                    $isCorrect = $sa && $sa->answer && $sa->answer->is_correct;
                    $earnedMarks = $isCorrect ? $qMarks : ($answered && $exam->negative_marking > 0 ? -$exam->negative_marking : 0);
                }
            @endphp
            <tr>
                <td>{{ $qIdx + 1 }}</td>
                <td style="max-width:260px;">{{ Str::limit($question->question_text, 80) }}</td>
                <td>
                    @if(!$answered)
                        <em style="color:#94a3b8;">Skipped</em>
                    @elseif($sa && $sa->file_path)
                        {{ Str::limit($sa->original_filename ?? 'File submitted', 40) }}
                    @elseif($sa && $sa->answer)
                        {{ Str::limit($sa->answer->answer_text, 40) }}
                    @else
                        <em style="color:#94a3b8;">—</em>
                    @endif
                </td>
                <td>
                    @if(!$answered)
                        <span class="badge badge-skip">Skipped</span>
                    @elseif($question->question_type === 'file_upload' && $sa && $sa->is_graded)
                        <span class="badge badge-correct">Graded</span>
                    @elseif($question->question_type === 'file_upload')
                        <span class="badge badge-skip">Pending</span>
                    @elseif($isCorrect)
                        <span class="badge badge-correct">✓ Correct</span>
                    @else
                        <span class="badge badge-wrong">✗ Wrong</span>
                    @endif
                </td>
                <td>
                    {{ $earnedMarks >= 0 ? '+' : '' }}{{ number_format($earnedMarks, 2) }}
                    / {{ number_format($qMarks, 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@if($examResult->tab_switch_count > 0)
<div style="margin: 0 16px 16px; padding: 8px 12px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; font-size: 11px; color: #c2410c;">
    ⚠️ <strong>Integrity Note:</strong> {{ $examResult->tab_switch_count }} tab switch(es) recorded during this exam session.
</div>
@endif

{{-- Signature section --}}
<div class="signature-line">
    <div class="sig">
        <div class="line">&nbsp;</div>
        <div class="label">Examiner's Signature</div>
    </div>
    <div class="sig">
        <div class="line">&nbsp;</div>
        <div class="label">Controller of Examinations</div>
    </div>
    <div class="sig">
        <div class="line">&nbsp;</div>
        <div class="label">Institution Stamp</div>
    </div>
</div>

<div class="footer">
    <span>{{ $exam->exam_name }} — Official Marksheet</span>
    <span>Printed: {{ now()->format('M d, Y H:i:s') }}</span>
</div>

</body>
</html>
