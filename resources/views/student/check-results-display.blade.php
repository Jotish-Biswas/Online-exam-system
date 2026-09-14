@extends('layouts.shell')

@section('title', 'Exam Breakdown — ' . $exam->exam_name)
@section('meta-description', 'Detailed review of your exam submission.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;">
        {{ $exam->exam_name }}
    </span>
    <span class="badge badge-neutral" style="flex-shrink:0;">Breakdown</span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('student.login') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-home"></i> Home
</a>
@endsection

@section('head')
<style>
    .display-root {
        padding: 2.5rem 1.25rem;
        max-width: 800px;
        margin: 0 auto;
    }

    /* Top stats hero */
    .hero-stats {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
    }
    @media (min-width: 640px) {
        .hero-stats { flex-direction: row; align-items: center; justify-content: space-between; }
    }

    .hero-stats__info { flex: 1; }
    
    .hero-stats__title {
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.5rem;
        color: var(--text);
    }

    .hero-stats__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }
    .hero-stats__meta span { display: flex; align-items: center; gap: 0.35rem; }

    /* Accordion overrides */
    .accordion-item--skipped { border-color: rgba(123,75,0,.2); }
    
    .answer-box {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        margin-bottom: 0.5rem;
        background: var(--surface-alt);
    }
    .answer-box--correct {
        background: var(--color-success-bg);
        border-color: rgba(19,115,51,.25);
    }
    .answer-box--wrong {
        background: var(--color-danger-bg);
        border-color: rgba(183,28,28,.2);
    }
</style>
@endsection

@section('content')
@php
    $sections = $sections ?? $examResult->evaluateSections();
    $writingPending = $sections['has_writing'] && !$sections['writing_fully_graded'];
    $passed = $sections['overall_passed'];
    $pct = $sections['overall_percentage'] ?? ($sections['mcq_percentage'] ?? 0);
    
    $radius = 48;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($pct / 100) * $circumference;
@endphp

<div class="display-root page-fade-in">

    {{-- Top Hero --}}
    <div class="hero-stats">
        <div class="hero-stats__info">
            <h1 class="hero-stats__title">{{ $exam->exam_name }}</h1>
            <div class="hero-stats__meta">
                <span><i class="fas fa-user"></i> {{ $examResult->student_id }}</span>
                <span><i class="fas fa-hashtag"></i> {{ $examResult->index_no }}</span>
                @if($examResult->submitted_at)
                    <span><i class="fas fa-calendar"></i> {{ $examResult->submitted_at->format('M d, Y') }}</span>
                @endif
            </div>

            @if($writingPending)
                <div class="notice notice--info" style="margin-top:1.25rem;">
                    <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>MCQ is ready. Writing and overall result stay hidden until your teacher sends marks back.</span>
                </div>
            @elseif($sections['has_mcq'] && $sections['has_writing'])
                <div style="margin-top:1rem; display:flex; flex-wrap:wrap; gap:0.5rem;">
                    <span class="badge {{ $sections['mcq_passed'] ? 'badge-success' : 'badge-danger' }}">MCQ {{ $sections['mcq_passed'] ? 'Pass' : 'Fail' }} · {{ $sections['mcq_percentage'] }}%</span>
                    <span class="badge {{ $sections['writing_passed'] ? 'badge-success' : 'badge-danger' }}">Writing {{ $sections['writing_passed'] ? 'Pass' : 'Fail' }} · {{ $sections['writing_percentage'] }}%</span>
                    <span class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}">Overall {{ $passed ? 'Pass' : 'Fail' }} (both required)</span>
                </div>
            @endif
        </div>

        @if(!$writingPending)
        <div style="flex-shrink:0; display:flex; gap:1.5rem; align-items:center;">
            <div style="text-align:right;">
                <div style="font-size:1.75rem; font-weight:800; color:{{ $passed ? 'var(--color-success)' : 'var(--color-danger)' }}; line-height:1; margin-bottom:0.25rem;">
                    {{ $pct }}%
                </div>
                <div class="badge {{ $passed ? 'badge-success' : 'badge-danger' }}">{{ $passed ? 'PASSED' : 'FAILED' }}</div>
            </div>
            
            <div class="score-ring" style="width:110px; height:110px;">
                <svg viewBox="0 0 110 110">
                    <circle class="score-ring__track" cx="55" cy="55" r="{{ $radius }}"></circle>
                    <circle class="score-ring__fill" cx="55" cy="55" r="{{ $radius }}" 
                            stroke="{{ $passed ? 'var(--color-success)' : 'var(--color-danger)' }}"
                            stroke-dasharray="{{ $circumference }}" 
                            stroke-dashoffset="{{ $offset }}"></circle>
                </svg>
                <div class="score-ring__label" style="font-size:0.8125rem; font-weight:600; color:var(--text-muted);">
                    {{ number_format($sections['obtained'], 1) }} / {{ $sections['total'] }}
                </div>
            </div>
        </div>
        @else
        <div style="flex-shrink:0; text-align:right;">
            <div style="font-size:1.5rem; font-weight:800; color:var(--color-accent);">{{ $sections['mcq_percentage'] ?? '—' }}%</div>
            <div style="font-size:0.8125rem; color:var(--text-muted);">MCQ score now</div>
        </div>
        @endif
    </div>

    {{-- Questions List --}}
    <h2 style="font-size:1.125rem; font-weight:700; color:var(--text); margin:0 0 1rem; letter-spacing:-0.01em;">
        Question Breakdown
    </h2>

    <div class="accordion">
        @foreach($exam->questions as $index => $question)
            @php
                $studentAnswers = $examResult->studentAnswers->where('question_id', $question->id);
                $studentAnswer = $studentAnswers->first();
                $selectedAnswerIds = $studentAnswers->pluck('answer_id')->filter()->toArray();

                $isCorrect = false;
                if ($question->question_type === 'multiple') {
                    $correctAnswerIds = $question->answers->where('is_correct', true)->pluck('id')->toArray();
                    sort($selectedAnswerIds);
                    sort($correctAnswerIds);
                    $isCorrect = $selectedAnswerIds === $correctAnswerIds;
                } else {
                    $isCorrect = $studentAnswer && $studentAnswer->answer && $studentAnswer->answer->is_correct;
                }

                $isGraded  = $studentAnswer && $studentAnswer->is_graded;
                $isFileUpload = $question->isFileUpload();
                $qMarks = (float)($question->marks ?? 1.00);
                $answered = $studentAnswer !== null;

                $cardClass = 'accordion-item--pending'; // default/skipped
                if ($isCorrect) $cardClass = 'accordion-item--correct';
                elseif ($answered && !$isFileUpload) $cardClass = 'accordion-item--wrong';
                if ($isFileUpload && $isGraded) {
                    $cardClass = $isCorrect ? 'accordion-item--correct' : 'accordion-item--wrong';
                }
            @endphp

            <div class="accordion-item {{ $cardClass }}">
                <button class="accordion-trigger" type="button" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" onclick="toggleAccordion(this)">
                    <span class="badge {{ $isCorrect ? 'badge-success' : ($answered && !$isFileUpload ? 'badge-danger' : 'badge-warning') }}" style="min-width:32px; justify-content:center;">
                        Q{{ $index + 1 }}
                    </span>
                    <span style="flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ Str::limit($question->question_text, 65) }}
                    </span>
                    <span style="font-size:0.75rem; color:var(--text-muted); margin-right:0.5rem; flex-shrink:0;">
                        {{ $qMarks }} mark(s)
                    </span>
                    <i class="fas fa-chevron-down accordion-chevron"></i>
                </button>

                <div class="accordion-content {{ $index == 0 ? 'is-open' : '' }}">
                    <div class="accordion-body">
                        
                        {{-- Question text --}}
                        <p style="font-size:0.9375rem; color:var(--text); margin:0 0 1.25rem; line-height:1.6;">
                            {{ $question->question_text }}
                        </p>

                        @if($isFileUpload)
                            {{-- File Upload Review --}}
                            <div style="background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-md); padding:1.25rem;">
                                <div style="font-size:0.8125rem; font-weight:600; color:var(--text-secondary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Your Submission</div>
                                
                                @if($studentAnswer && $studentAnswer->file_path)
                                    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem; font-size:0.875rem;">
                                        <i class="fas fa-file-alt" style="color:var(--text-muted);"></i>
                                        <a href="{{ $studentAnswer->getFileUrl() }}" target="_blank" style="font-weight:500; color:var(--color-accent);">{{ $studentAnswer->original_filename ?? basename($studentAnswer->file_path) }}</a>
                                    </div>
                                @else
                                    <p style="font-size:0.875rem; color:var(--text-muted); margin:0; font-style:italic;">No file submitted.</p>
                                @endif

                                @if($isGraded)
                                    <div style="display:inline-flex; align-items:center; gap:0.5rem; background:var(--color-accent-surface); color:var(--color-accent); padding:0.4rem 0.75rem; border-radius:var(--radius-xs); font-size:0.8125rem; font-weight:600;">
                                        Score: {{ number_format((float)$studentAnswer->manual_score, 2) }} / {{ $qMarks }}
                                    </div>
                                    @if($studentAnswer->admin_feedback)
                                        <div style="margin-top:0.75rem; font-size:0.875rem; color:var(--text); background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-md); padding:0.85rem 1rem;">
                                            <strong style="display:block; margin-bottom:0.35rem;">Teacher feedback</strong>
                                            {!! nl2br(e($studentAnswer->admin_feedback)) !!}
                                        </div>
                                    @endif
                                    @if($studentAnswer->annotated_file_path)
                                        <div style="margin-top:0.75rem;">
                                            <a class="btn btn-secondary" href="{{ $studentAnswer->getAnnotatedFileUrl() }}" target="_blank">
                                                <i class="fas fa-highlighter"></i> Open marked script
                                            </a>
                                        </div>
                                    @endif
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Teacher has not sent writing marks yet</span>
                                @endif
                            </div>

                        @else
                            {{-- MCQ Review --}}
                            <div style="font-size:0.8125rem; font-weight:600; color:var(--text-secondary); margin-bottom:0.75rem; text-transform:uppercase; letter-spacing:0.04em;">Options</div>
                            
                            @foreach($question->answers as $answer)
                                @php
                                    $isStudentAnswer = false;
                                    if ($question->question_type === 'multiple') {
                                        $isStudentAnswer = in_array($answer->id, $selectedAnswerIds);
                                    } else {
                                        $isStudentAnswer = $studentAnswer && $studentAnswer->answer_id == $answer->id;
                                    }

                                    $boxClass = '';
                                    if ($answer->is_correct) $boxClass = 'answer-box--correct';
                                    elseif ($isStudentAnswer && !$answer->is_correct) $boxClass = 'answer-box--wrong';
                                @endphp

                                <div class="answer-box {{ $boxClass }}">
                                    @if($question->question_type === 'multiple')
                                        <input type="checkbox" disabled @if($isStudentAnswer) checked @endif style="margin-top:0.25rem;">
                                    @else
                                        <input type="radio" disabled @if($isStudentAnswer) checked @endif style="margin-top:0.25rem;">
                                    @endif
                                    
                                    <span style="flex:1; font-size:0.9375rem; color:var(--text); line-height:1.5;">{{ $answer->answer_text }}</span>
                                    
                                    @if($answer->is_correct)
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Correct</span>
                                    @elseif($isStudentAnswer && !$answer->is_correct)
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Yours</span>
                                    @endif
                                </div>
                            @endforeach

                            @if(!$answered)
                                <div class="notice notice--warning" style="margin-top:1rem;">
                                    <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span>You skipped this question.</span>
                                </div>
                            @endif

                            {{-- Explanation --}}
                            @if($question->explanation)
                                <div style="margin-top:1.25rem; background:var(--color-accent-surface); border:1px solid rgba(10,102,194,.15); border-radius:var(--radius-md); padding:1.25rem;">
                                    <h4 style="font-size:0.875rem; font-weight:700; color:var(--color-accent); margin:0 0 0.5rem; display:flex; align-items:center; gap:0.4rem;">
                                        <i class="fas fa-lightbulb"></i> Explanation
                                    </h4>
                                    <p style="font-size:0.875rem; color:var(--text); margin:0; line-height:1.6;">{{ $question->explanation }}</p>
                                </div>
                            @endif

                            <div class="ai-explanation" style="margin-top:1.25rem;">
                                <button
                                    type="button"
                                    class="btn btn-secondary ai-explain-button"
                                    data-url="{{ route('student.ai-explanation', [$examResult->id, $question->id]) }}"
                                    data-exam-id="{{ $exam->exam_id }}"
                                    data-student-id="{{ $examResult->student_id }}"
                                    data-index-no="{{ $examResult->index_no }}"
                                >
                                    <i class="fas fa-wand-magic-sparkles"></i> Explain by অনুরণন
                                </button>
                                <div class="ai-explanation-output" hidden style="margin-top:0.75rem; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-md); padding:1rem;">
                                    <div style="font-size:0.8125rem; font-weight:700; color:var(--color-accent); margin-bottom:0.4rem;">
                                        <i class="fas fa-language"></i> সহজ বাংলা ব্যাখ্যা
                                    </div>
                                    <div class="ai-explanation-text" style="font-size:0.875rem; color:var(--text); line-height:1.65; white-space:pre-line;"></div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Actions --}}
    <div style="display:flex; justify-content:center; gap:1rem; margin-top:2.5rem; flex-wrap:wrap;">
        <a href="{{ route('student.checkResultsForm') }}" class="btn btn-secondary">
            <i class="fas fa-search"></i> Check Another
        </a>
        <a href="{{ route('student.login') }}" class="btn btn-primary">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script>
    function toggleAccordion(btn) {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', !expanded);
        const content = btn.nextElementSibling;
        if (!expanded) {
            content.classList.add('is-open');
        } else {
            content.classList.remove('is-open');
        }
    }

    document.querySelectorAll('.ai-explain-button').forEach(function (button) {
        button.addEventListener('click', async function () {
            const output = button.nextElementSibling;
            const text = output.querySelector('.ai-explanation-text');
            const originalLabel = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating explanation...';
            output.hidden = false;
            text.textContent = 'Please wait...';

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        exam_id: button.dataset.examId,
                        student_id: button.dataset.studentId,
                        index_no: button.dataset.indexNo,
                    }),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to generate explanation.');
                }

                text.textContent = data.explanation;
                button.innerHTML = '<i class="fas fa-check"></i> Explanation ready';
            } catch (error) {
                output.hidden = true;
                alert(error.message);
                button.innerHTML = originalLabel;
            } finally {
                button.disabled = false;
            }
        });
    });
</script>
@endsection