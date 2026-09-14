@extends('layouts.shell')

@section('title', 'Grade desk — ' . $exam->exam_name)

@section('nav-center')
<div style="min-width:0; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap;">
        Grade desk · {{ $exam->exam_name }}
    </span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.grade-submissions', $exam->id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;">
    <i class="fas fa-list"></i> All students
</a>
<a href="{{ route('admin.exam-results', $exam->id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-arrow-left"></i> Results
</a>
@endsection

@section('head')
<style>
    .desk { display:grid; grid-template-columns: 240px 1fr 340px; gap:1rem; align-items:stretch; min-height:calc(100vh - 110px); }
    @media (max-width: 1100px) { .desk { grid-template-columns: 1fr; } }
    .desk-pane { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; display:flex; flex-direction:column; min-height:0; }
    .desk-pane__h { padding:0.9rem 1rem; border-bottom:1px solid var(--border); font-weight:700; font-size:0.8125rem; }
    .desk-pane__b { padding:1rem; overflow:auto; flex:1; }
    .stu { display:block; padding:0.7rem 0.9rem; border-bottom:1px solid var(--border); color:inherit; text-decoration:none; }
    .stu.is-active { background:var(--color-accent-surface); }
    .stu:hover { background:var(--surface-alt); }
    .qtab { display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:1rem; }
    .viewer { background:#1a1d23; border-radius:var(--radius-md); min-height:420px; display:flex; align-items:center; justify-content:center; position:relative; overflow:auto; }
    .anno-wrap { position:relative; display:inline-block; max-width:100%; }
    .anno-wrap img, .anno-wrap canvas { display:block; max-width:100%; height:auto; }
    .anno-wrap canvas { position:absolute; inset:0; width:100%; height:100%; cursor:crosshair; }
    .tools { display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:0.75rem; align-items:center; }
</style>
@endsection

@section('content')
@php
    $maxMarks = (float) ($activeQuestion->marks ?? 1);
    $gradeAction = $studentAnswer
        ? route('admin.grade-file-submission', $studentAnswer->id)
        : route('admin.grade-writing-question', [$examResult->id, $activeQuestion->id]);
    $fileUrl = $studentAnswer?->getFileUrl();
    $fileName = $studentAnswer->original_filename ?? '';
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
    $isPdf = $ext === 'pdf';
@endphp

<div class="desk page-fade-in" style="padding:1rem 0 2rem;">
    <aside class="desk-pane">
        <div class="desk-pane__h">Students ({{ $submissions->count() }})</div>
        <div class="desk-pane__b" style="padding:0;">
            @foreach($submissions as $sub)
                @php
                    $g = $fileUploadQuestions->filter(function ($q) use ($sub) {
                        $sa = $sub->studentAnswers->firstWhere('question_id', $q->id);
                        return $sa && $sa->is_graded;
                    })->count();
                @endphp
                <a class="stu {{ $sub->id === $examResult->id ? 'is-active' : '' }}"
                   href="{{ route('admin.grade-desk', [$exam->id, $sub->id, 'question' => $activeQuestion->id]) }}">
                    <div style="font-weight:700; font-size:0.875rem;">{{ $sub->student_id }}</div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">{{ $sub->index_no }} · {{ $g }}/{{ $fileUploadQuestions->count() }} graded</div>
                </a>
            @endforeach
        </div>
    </aside>

    <section class="desk-pane">
        <div class="desk-pane__h" style="display:flex; justify-content:space-between; gap:0.75rem; align-items:center;">
            <span>{{ $examResult->student_id }} · {{ $examResult->index_no }}</span>
            <span style="font-weight:500; color:var(--text-muted); font-size:0.75rem;">
                {{ $examResult->submitted_at->format('M d, Y h:i A') }}
            </span>
        </div>
        <div class="desk-pane__b">
            <div class="qtab">
                @foreach($fileUploadQuestions as $q)
                    @php $saQ = $examResult->studentAnswers->firstWhere('question_id', $q->id); @endphp
                    <a class="btn {{ $q->id === $activeQuestion->id ? 'btn-primary' : 'btn-secondary' }}"
                       style="min-height:34px; padding:0.3rem 0.7rem;"
                       href="{{ route('admin.grade-desk', [$exam->id, $examResult->id, 'question' => $q->id]) }}">
                        Q{{ $loop->iteration }} · {{ rtrim(rtrim(number_format((float)$q->marks, 2), '0'), '.') }} mk
                        @if($saQ && $saQ->is_graded)<i class="fas fa-check" style="margin-left:0.25rem;"></i>@endif
                    </a>
                @endforeach
            </div>

            <p style="font-size:0.9375rem; line-height:1.55; margin:0 0 1rem;">{{ $activeQuestion->question_text }}</p>

            @if($fileUrl && $isImage)
                <div class="tools">
                    <button type="button" class="btn btn-secondary" style="min-height:32px;" onclick="setTool('pen')"><i class="fas fa-pen"></i> Write</button>
                    <button type="button" class="btn btn-secondary" style="min-height:32px;" onclick="setTool('erase')"><i class="fas fa-eraser"></i> Erase</button>
                    <input type="color" id="penColor" value="#e11d48" title="Ink color" style="width:36px; height:32px; border:none; background:transparent;">
                    <label style="font-size:0.75rem; color:var(--text-muted);">Size
                        <input type="range" id="penSize" min="2" max="18" value="4" style="vertical-align:middle;">
                    </label>
                    <button type="button" class="btn btn-ghost" style="min-height:32px;" onclick="undoStroke()">Undo</button>
                    <button type="button" class="btn btn-ghost" style="min-height:32px;" onclick="clearInk()">Clear</button>
                </div>
                <div class="viewer" style="background:#111;">
                    <div class="anno-wrap" id="annoWrap">
                        <img id="scriptImg" src="{{ $fileUrl }}" alt="Student script" crossorigin="anonymous">
                        <canvas id="annoCanvas"></canvas>
                    </div>
                </div>
            @elseif($fileUrl && $isPdf)
                <div class="viewer" style="padding:0; min-height:520px;">
                    <iframe src="{{ $fileUrl }}" style="width:100%; height:520px; border:none;"></iframe>
                </div>
            @elseif($fileUrl)
                <div class="viewer" style="flex-direction:column; gap:0.75rem; color:#fff; padding:2rem;">
                    <i class="fas fa-file-alt" style="font-size:2.5rem;"></i>
                    <div>{{ $fileName }}</div>
                    <a class="btn btn-primary" href="{{ $fileUrl }}" target="_blank">Open / download</a>
                </div>
            @else
                <div class="notice notice--warning">No file uploaded. You can still award marks and write feedback.</div>
            @endif
        </div>
    </section>

    <aside class="desk-pane">
        <div class="desk-pane__h">Marks & send-back</div>
        <div class="desk-pane__b">
            <form action="{{ $gradeAction }}" method="POST" enctype="multipart/form-data" id="gradeForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="annotated_image" id="annotatedImage">

                <div class="field">
                    <label class="field-label">Marks (0–{{ rtrim(rtrim(number_format($maxMarks, 2), '0'), '.') }})</label>
                    <input class="field-input" type="number" name="manual_score" min="0" max="{{ $maxMarks }}" step="0.25"
                           value="{{ $studentAnswer->manual_score ?? '' }}" required>
                    <div style="display:flex; gap:0.35rem; margin-top:0.4rem;">
                        <button type="button" class="btn btn-ghost" style="padding:0.15rem 0.5rem;" onclick="document.querySelector('[name=manual_score]').value='{{ $maxMarks }}'">Full</button>
                        <button type="button" class="btn btn-ghost" style="padding:0.15rem 0.5rem;" onclick="document.querySelector('[name=manual_score]').value='{{ $maxMarks / 2 }}'">Half</button>
                        <button type="button" class="btn btn-ghost" style="padding:0.15rem 0.5rem;" onclick="document.querySelector('[name=manual_score]').value='0'">Zero</button>
                    </div>
                </div>

                <div class="field" style="margin-top:1rem;">
                    <label class="field-label">Feedback for student</label>
                    <textarea class="field-input" name="admin_feedback" rows="6" placeholder="e.g. Introduction is weak — start with the main idea, then give two examples…">{{ $studentAnswer->admin_feedback ?? '' }}</textarea>
                    <p class="field-hint">Student sees this after you send it back. Writing marks stay hidden until you submit.</p>
                </div>

                @if($fileUrl && !$isImage)
                <div class="field" style="margin-top:1rem;">
                    <label class="field-label">Upload marked copy (optional)</label>
                    <input class="field-input" type="file" name="annotated_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <p class="field-hint">If you marked the PDF on your computer, upload it here.</p>
                </div>
                @endif

                @if($studentAnswer && $studentAnswer->annotated_file_path)
                    <p style="font-size:0.8125rem; margin:0.5rem 0 0;">
                        <a href="{{ $studentAnswer->getAnnotatedFileUrl() }}" target="_blank">Current marked file</a>
                    </p>
                @endif

                <button type="submit" class="btn btn-success" style="width:100%; margin-top:1.25rem;" onclick="captureInk()">
                    <i class="fas fa-paper-plane"></i> Save & send back
                </button>
            </form>

            <div style="display:flex; justify-content:space-between; margin-top:1rem; gap:0.5rem;">
                @if($prevSubmission)
                    <a class="btn btn-secondary" href="{{ route('admin.grade-desk', [$exam->id, $prevSubmission->id, 'question' => $activeQuestion->id]) }}">← Prev</a>
                @else
                    <span></span>
                @endif
                @if($nextSubmission)
                    <a class="btn btn-secondary" href="{{ route('admin.grade-desk', [$exam->id, $nextSubmission->id, 'question' => $activeQuestion->id]) }}">Next →</a>
                @endif
            </div>
        </div>
    </aside>
</div>
@endsection

@section('scripts')
<script>
    let tool = 'pen';
    let drawing = false;
    let strokes = [];
    let current = [];
    const canvas = document.getElementById('annoCanvas');
    const img = document.getElementById('scriptImg');

    function setTool(t) { tool = t; }

    function fitCanvas() {
        if (!canvas || !img) return;
        const w = img.clientWidth || img.naturalWidth;
        const h = img.clientHeight || img.naturalHeight;
        canvas.width = w;
        canvas.height = h;
        redraw();
    }

    function redraw() {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        strokes.forEach(drawStroke);
    }

    function drawStroke(s) {
        const ctx = canvas.getContext('2d');
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = s.size;
        ctx.strokeStyle = s.color;
        ctx.globalCompositeOperation = s.erase ? 'destination-out' : 'source-over';
        ctx.beginPath();
        s.points.forEach((p, i) => i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y));
        ctx.stroke();
        ctx.globalCompositeOperation = 'source-over';
    }

    function pos(e) {
        const r = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - r.left, y: src.clientY - r.top };
    }

    function startDraw(e) {
        if (!canvas) return;
        drawing = true;
        current = {
            color: document.getElementById('penColor').value,
            size: parseInt(document.getElementById('penSize').value, 10),
            erase: tool === 'erase',
            points: [pos(e)]
        };
        e.preventDefault();
    }
    function moveDraw(e) {
        if (!drawing) return;
        current.points.push(pos(e));
        redraw();
        drawStroke(current);
        e.preventDefault();
    }
    function endDraw() {
        if (!drawing) return;
        drawing = false;
        if (current.points.length > 1) strokes.push(current);
        current = [];
        redraw();
    }

    function undoStroke() { strokes.pop(); redraw(); }
    function clearInk() { strokes = []; redraw(); }

    function captureInk() {
        if (!canvas || strokes.length === 0 || !img) return;
        const out = document.createElement('canvas');
        out.width = img.naturalWidth;
        out.height = img.naturalHeight;
        const ctx = out.getContext('2d');
        ctx.drawImage(img, 0, 0);
        ctx.drawImage(canvas, 0, 0, out.width, out.height);
        document.getElementById('annotatedImage').value = out.toDataURL('image/png');
    }

    if (img) {
        img.addEventListener('load', fitCanvas);
        if (img.complete) fitCanvas();
        window.addEventListener('resize', fitCanvas);
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', moveDraw);
        window.addEventListener('mouseup', endDraw);
        canvas.addEventListener('touchstart', startDraw, {passive:false});
        canvas.addEventListener('touchmove', moveDraw, {passive:false});
        canvas.addEventListener('touchend', endDraw);
    }
</script>
@endsection
