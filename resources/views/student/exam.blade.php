@extends('layouts.shell')

@section('title', $exam->exam_name)
@section('meta-description', 'Online exam — ' . $exam->exam_name)

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

<button type="button" id="fullscreenBtn" class="btn btn-secondary btn-icon" title="Toggle Fullscreen" aria-label="Toggle fullscreen">
    <i class="fas fa-expand" style="font-size:0.8rem;"></i>
</button>

<div class="exam-timer exam-timer--safe" id="timerBox" role="timer" aria-live="polite" aria-label="Time remaining">
    <i class="fas fa-stopwatch" style="font-size:0.8rem;"></i>
    <span id="timerDisplay" style="font-size:0.9375rem;">--:--</span>
</div>
@endsection

@section('head')
<style>
    /* Exam layout */
    .exam-root {
        padding: 1.5rem 1.25rem;
        max-width: 1120px;
        margin: 0 auto;
    }

    .exam-layout {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 1.25rem;
        align-items: start;
    }

    /* Instructions banner */
    .exam-instructions {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        background: var(--color-accent-surface);
        border: 1px solid rgba(10,102,194,.1);
        border-radius: var(--radius-md);
        margin-bottom: 1.25rem;
        font-size: 0.8125rem;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    /* Progress bar */
    .progress-bar-track {
        height: 3px;
        background: var(--surface-alt);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .progress-bar-fill {
        height: 100%;
        background: var(--color-accent);
        border-radius: 2px;
        transition: width 0.4s var(--ease-std);
        width: 0%;
    }

    /* Palette legend */
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 2px;
        flex-shrink: 0;
    }

    /* File upload zone */
    .upload-zone {
        border: 1.5px dashed var(--border-strong);
        border-radius: var(--radius-lg);
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        background: var(--surface-alt);
        transition: border-color var(--dur-std) var(--ease-out),
                    background var(--dur-std) var(--ease-out);
        position: relative;
    }
    .upload-zone:hover {
        border-color: var(--color-accent);
        background: var(--color-accent-surface);
    }
    .upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .file-chosen {
        display: none;
        align-items: center;
        gap: 0.625rem;
        padding: 0.75rem 0.875rem;
        background: var(--color-success-bg);
        border: 1px solid rgba(19,115,51,.2);
        border-radius: var(--radius-md);
        margin-top: 0.75rem;
    }

    /* Bottom submit card */
    .submit-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        text-align: center;
        margin-top: 0.5rem;
    }

    /* Stats row in palette */
    .palette-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-bottom: 0.75rem;
    }

    .palette-stat {
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.5rem 0.25rem;
        text-align: center;
    }

    .palette-stat__num {
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .palette-stat__label {
        font-size: 0.65rem;
        color: var(--text-muted);
        line-height: 1.3;
        margin-top: 0.1rem;
    }

    @media (max-width: 768px) {
        .exam-layout {
            grid-template-columns: 1fr;
        }
        .palette-sidebar {
            order: -1;
        }
        .exam-root {
            padding: 1rem;
        }
    }
</style>
@endsection

@section('content')

<form action="{{ route('student.submit-exam', $exam->id) }}" method="POST" id="examForm" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="tab_switch_count" id="tab_switch_count" value="0">

    <div class="exam-root">

        {{-- Progress bar --}}
        <div class="progress-bar-track" aria-hidden="true">
            <div class="progress-bar-fill" id="progressFill"></div>
        </div>

        <div class="exam-layout">

            {{-- ─── Main Questions Column ─── --}}
            <div>
                {{-- Instructions --}}
                <div class="exam-instructions">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:0.1rem; color:var(--color-accent);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>
                        Answer questions in any order. Use <strong>Mark for Review</strong> to flag questions you want to revisit. Your progress auto-saves.
                        @if($exam->enable_anti_cheating)
                            &nbsp;—&nbsp;<strong>Anti-cheating is active:</strong> tab switching and copy-paste are monitored.
                        @endif
                    </span>
                </div>

                {{-- Questions --}}
                @foreach($exam->questions as $index => $question)
                <div class="question-card" id="question_card_{{ $index + 1 }}" data-question-id="{{ $question->id }}" data-question-index="{{ $index + 1 }}" style="margin-bottom:1rem;">

                    {{-- Question header --}}
                    <div class="question-card__header">
                        <div style="display:flex; align-items:center; gap:0.625rem; min-width:0;">
                            <span class="question-number" aria-label="Question {{ $index + 1 }}">{{ $index + 1 }}</span>
                            <span style="font-size:0.75rem; color:var(--text-muted); display:flex; align-items:center; gap:0.25rem;">
                                @if($question->question_type === 'file_upload')
                                    <i class="fas fa-paperclip" style="font-size:0.7rem;"></i> File Upload
                                @elseif($question->question_type === 'multiple')
                                    <i class="fas fa-check-square" style="font-size:0.7rem;"></i> Multiple Choice
                                @else
                                    <i class="fas fa-circle" style="font-size:0.5rem;"></i> Single Choice
                                @endif
                            </span>
                            @if($question->marks)
                                <span class="badge badge-neutral" style="font-size:0.7rem;">{{ $question->marks }} {{ $question->marks == 1 ? 'mark' : 'marks' }}</span>
                            @endif
                        </div>

                        <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
                            {{-- Flag/review button --}}
                            <button
                                type="button"
                                class="btn btn-warning btn-icon mark-review-btn"
                                data-question-index="{{ $index + 1 }}"
                                aria-label="Mark question {{ $index + 1 }} for review"
                                style="font-size:0.75rem; min-width:auto; padding:0.4rem 0.65rem; gap:0.3rem;">
                                <i class="far fa-flag"></i>
                                <span class="review-text" style="font-size:0.75rem; font-weight:500;">Flag</span>
                            </button>

                            @if($question->question_type === 'single')
                            <button
                                type="button"
                                class="btn btn-ghost btn-icon clear-choice-btn"
                                data-question-id="{{ $question->id }}"
                                aria-label="Clear answer for question {{ $index + 1 }}"
                                title="Clear selection"
                                style="font-size:0.75rem; color:var(--text-faint); padding:0.4rem 0.5rem;">
                                <i class="fas fa-times" style="font-size:0.7rem;"></i>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Question body --}}
                    <div class="question-card__body">
                        <p class="question-text">{{ $question->question_text }}</p>

                        @if($question->question_type === 'file_upload')
                            {{-- ── File Upload ── --}}
                            <div style="font-size:0.8125rem; color:var(--text-muted); margin-bottom:0.875rem; display:flex; gap:1rem; flex-wrap:wrap;">
                                <span><strong>Formats:</strong> {{ strtoupper(implode(', ', $question->getAllowedExtensions())) }}</span>
                                <span><strong>Max size:</strong> {{ $question->getMaxFileSize() }} MB</span>
                            </div>

                            <div class="upload-zone" id="uploadZone_{{ $question->id }}" onclick="document.getElementById('file_upload_{{ $question->id }}').click()">
                                <input
                                    type="file"
                                    class="question-input file-input"
                                    id="file_upload_{{ $question->id }}"
                                    name="file_uploads[{{ $question->id }}]"
                                    accept=".{{ implode(',.', $question->getAllowedExtensions()) }}"
                                    data-max-size="{{ $question->getMaxFileSize() }}"
                                    data-question-id="{{ $question->id }}"
                                    data-question-index="{{ $index + 1 }}"
                                    onclick="event.stopPropagation()">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 0.625rem; display:block; color:var(--text-faint);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <p style="font-size:0.875rem; font-weight:500; color:var(--text-secondary); margin:0 0 0.25rem;">Click or tap to upload your answer</p>
                                <p style="font-size:0.75rem; color:var(--text-faint); margin:0;">{{ strtoupper(implode(', ', $question->getAllowedExtensions())) }} up to {{ $question->getMaxFileSize() }} MB</p>
                            </div>

                            <div class="file-chosen" id="file_preview_{{ $question->id }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-success); flex-shrink:0;"><polyline points="20,6 9,17 4,12"/></svg>
                                <div style="flex:1; min-width:0;">
                                    <p style="font-size:0.8125rem; font-weight:500; color:var(--color-success); margin:0 0 0.1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" class="filename"></p>
                                    <p style="font-size:0.75rem; color:var(--text-muted); margin:0;" class="filesize"></p>
                                </div>
                                <span class="badge badge-success">Ready</span>
                            </div>

                        @else
                            {{-- ── MCQ Options ── --}}
                            <div class="options-container" role="radiogroup" aria-label="Answer options for question {{ $index + 1 }}">
                                @foreach($question->answers as $answerIndex => $answer)
                                <label class="option-label" for="ans_{{ $question->id }}_{{ $answer->id }}">
                                    @if($question->question_type === 'single')
                                        <input
                                            type="radio"
                                            class="question-input"
                                            name="answers[{{ $question->id }}]"
                                            id="ans_{{ $question->id }}_{{ $answer->id }}"
                                            value="{{ $answer->id }}"
                                            data-question-id="{{ $question->id }}"
                                            data-question-index="{{ $index + 1 }}">
                                    @else
                                        <input
                                            type="checkbox"
                                            class="question-input"
                                            name="answers[{{ $question->id }}][]"
                                            id="ans_{{ $question->id }}_{{ $answer->id }}"
                                            value="{{ $answer->id }}"
                                            data-question-id="{{ $question->id }}"
                                            data-question-index="{{ $index + 1 }}">
                                    @endif
                                    <span class="option-key" aria-hidden="true">{{ chr(65 + $answerIndex) }}</span>
                                    <span class="option-text">{{ $answer->answer_text }}</span>
                                </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Submit panel --}}
                <div class="submit-panel">
                    <p style="font-size:0.875rem; font-weight:600; color:var(--text); margin:0 0 0.25rem;">Ready to submit?</p>
                    <p style="font-size:0.8125rem; color:var(--text-muted); margin:0 0 1rem;" id="bottomSummaryText">Review your answers then submit when you're ready.</p>
                    <button type="button" class="btn btn-success btn-lg" id="bottomSubmitBtn" onclick="openSubmitModal()">
                        <i class="fas fa-paper-plane"></i>
                        Review &amp; Submit Exam
                    </button>
                </div>
            </div>

            {{-- ─── Palette Sidebar ─── --}}
            <div class="palette-sidebar card" style="padding:0; overflow:visible;">

                {{-- Header --}}
                <div class="card__header" style="padding:0.875rem 1rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                        <h2 style="font-size:0.8125rem; font-weight:600; color:var(--text); margin:0;">Questions</h2>
                        <span id="answeredBadge" style="font-size:0.75rem; font-weight:600; color:var(--text-muted);">0 / {{ $exam->questions->count() }}</span>
                    </div>
                </div>

                <div class="card__body" style="padding:0.875rem 1rem;">
                    {{-- Stats --}}
                    <div class="palette-stats">
                        <div class="palette-stat">
                            <div class="palette-stat__num text-success" id="countAnswered">0</div>
                            <div class="palette-stat__label">Done</div>
                        </div>
                        <div class="palette-stat">
                            <div class="palette-stat__num text-warning" id="countReview">0</div>
                            <div class="palette-stat__label">Flagged</div>
                        </div>
                        <div class="palette-stat">
                            <div class="palette-stat__num" style="color:var(--text-faint);" id="countUnanswered">{{ $exam->questions->count() }}</div>
                            <div class="palette-stat__label">Skipped</div>
                        </div>
                    </div>

                    {{-- Grid --}}
                    <div class="palette-grid" id="paletteGrid" style="margin-bottom:0.875rem;">
                        @foreach($exam->questions as $index => $question)
                        <a
                            href="#question_card_{{ $index + 1 }}"
                            class="palette-btn"
                            id="palette_btn_{{ $index + 1 }}"
                            data-index="{{ $index + 1 }}"
                            aria-label="Go to question {{ $index + 1 }}">
                            {{ $index + 1 }}
                        </a>
                        @endforeach
                    </div>

                    {{-- Legend --}}
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.375rem; padding-top:0.625rem; border-top:1px solid var(--border);">
                        <div class="legend-item">
                            <span class="legend-dot" style="background:var(--color-success);"></span>
                            Answered
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:var(--color-warning);"></span>
                            Flagged
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:var(--border-strong);"></span>
                            Skipped
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background:var(--color-accent); border-radius:50%;"></span>
                            Current
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="card__footer" style="padding:0.875rem 1rem;">
                    <button type="button" class="btn btn-success" style="width:100%;" id="sidebarSubmitBtn" onclick="openSubmitModal()">
                        <i class="fas fa-check"></i>
                        Submit Exam
                    </button>
                </div>

            </div>{{-- /palette-sidebar --}}

        </div>{{-- /exam-layout --}}
    </div>{{-- /exam-root --}}
</form>

{{-- ─── Submit Confirmation Modal ─── --}}
<div class="modal-overlay" id="submitModal" role="dialog" aria-modal="true" aria-labelledby="submitModalTitle">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="submitModalTitle" style="font-size:1rem; font-weight:600; color:var(--text); margin:0;">Submit Exam</h3>
            <button type="button" class="btn btn-ghost btn-icon" onclick="closeSubmitModal()" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:0.75rem; margin-bottom:1rem;">
                <div style="text-align:center; padding:0.875rem 0.5rem; background:var(--color-success-bg); border-radius:var(--radius-md);">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--color-success);" id="modalAnsweredCount">0</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.2rem;">Answered</div>
                </div>
                <div style="text-align:center; padding:0.875rem 0.5rem; background:var(--color-warning-bg); border-radius:var(--radius-md);">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--color-warning);" id="modalReviewCount">0</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.2rem;">Flagged</div>
                </div>
                <div style="text-align:center; padding:0.875rem 0.5rem; background:var(--surface-alt); border-radius:var(--radius-md);">
                    <div style="font-size:1.5rem; font-weight:700; color:var(--text-faint);" id="modalUnansweredCount">0</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.2rem;">Skipped</div>
                </div>
            </div>

            <div id="modalWarningAlert" class="notice notice--warning" style="display:none; margin-bottom:0.875rem;">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>You have unanswered or flagged questions. You can go back to review them, or submit now.</span>
            </div>

            <p style="font-size:0.8125rem; color:var(--text-muted); margin:0; text-align:center;">
                Once submitted, your responses are final and cannot be changed.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeSubmitModal()">
                <i class="fas fa-arrow-left" style="font-size:0.8rem;"></i>
                Go Back
            </button>
            <button type="button" class="btn btn-success" id="finalSubmitBtn">
                <i class="fas fa-check"></i>
                Yes, Submit Now
            </button>
        </div>
    </div>
</div>

{{-- ─── Anti-Cheating Warning Modal ─── --}}
<div class="modal-overlay" id="antiCheatingModal" role="alertdialog" aria-modal="true" aria-labelledby="cheatTitle">
    <div class="modal-box">
        <div class="modal-header" style="background:var(--color-danger-bg); border-color:rgba(183,28,28,.15);">
            <h3 id="cheatTitle" style="font-size:1rem; font-weight:600; color:var(--color-danger); margin:0; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-shield-alt"></i>
                <span id="cheatingWarningTitle">Warning 1 of 3</span>
            </h3>
        </div>
        <div class="modal-body" style="text-align:center; padding:1.5rem;">
            <div style="margin-bottom:1rem; color:var(--color-danger);">
                <i class="fas fa-exclamation-triangle" style="font-size:2rem;"></i>
            </div>
            <p id="cheatingWarningBody" style="font-size:0.9375rem; color:var(--text); margin:0 0 1rem; line-height:1.6;">
                You navigated away from the exam tab.
            </p>
            <div class="notice notice--warning" style="text-align:left;">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>After 3 violations, your exam will be automatically submitted.</span>
            </div>
        </div>
        <div class="modal-footer" style="justify-content:center;">
            <button type="button" class="btn btn-danger" id="ackCheatingBtn">
                I Understand — Resume Exam
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Config ──────────────────────────────────────────
    const examId             = {{ $exam->id }};
    const studentId          = "{{ session('student_id') }}";
    const totalQuestions     = {{ $exam->questions->count() }};
    const enableAntiCheating = {{ $exam->enable_anti_cheating ? 'true' : 'false' }};
    const draftStorageKey    = `exam_draft_${examId}_${studentId}`;
    let   remainingSeconds   = {{ $remainingSeconds ?? 1800 }};
    let   tabSwitchCount     = 0;
    const reviewedQuestions  = new Set();
    const form               = document.getElementById('examForm');

    // ── 1. TIMER ─────────────────────────────────────────
    const timerBox     = document.getElementById('timerBox');
    const timerDisplay = document.getElementById('timerDisplay');

    function formatTime(s) {
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;
        const pad = n => String(n).padStart(2, '0');
        return h > 0 ? `${h}:${pad(m)}:${pad(sec)}` : `${pad(m)}:${pad(sec)}`;
    }

    function updateTimer() {
        if (remainingSeconds <= 0) {
            timerDisplay.textContent = '00:00';
            timerBox.className = 'exam-timer exam-timer--danger';
            clearInterval(timerInterval);
            submitExamForm();
            return;
        }
        timerDisplay.textContent = formatTime(remainingSeconds);
        if      (remainingSeconds <= 60)  timerBox.className = 'exam-timer exam-timer--danger';
        else if (remainingSeconds <= 300) timerBox.className = 'exam-timer exam-timer--warning';
        else                              timerBox.className = 'exam-timer exam-timer--safe';
        remainingSeconds--;
    }

    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    // ── 2. PALETTE & PROGRESS ────────────────────────────
    const elCountAnswered   = document.getElementById('countAnswered');
    const elCountReview     = document.getElementById('countReview');
    const elCountUnanswered = document.getElementById('countUnanswered');
    const elAnsweredBadge   = document.getElementById('answeredBadge');
    const elProgressFill    = document.getElementById('progressFill');
    const elModalAnswered   = document.getElementById('modalAnsweredCount');
    const elModalReview     = document.getElementById('modalReviewCount');
    const elModalUnanswered = document.getElementById('modalUnansweredCount');
    const elModalWarning    = document.getElementById('modalWarningAlert');

    function updatePalette() {
        let answeredCount = 0;

        for (let i = 1; i <= totalQuestions; i++) {
            const card      = document.getElementById(`question_card_${i}`);
            const paletteBtn = document.getElementById(`palette_btn_${i}`);
            if (!card || !paletteBtn) continue;

            const hasCheckedMcq = card.querySelectorAll('.question-input:checked').length > 0;
            const fileInput     = card.querySelector('.file-input');
            const hasFile       = fileInput && fileInput.files && fileInput.files.length > 0;
            const isAnswered    = hasCheckedMcq || hasFile;
            const isReview      = reviewedQuestions.has(i);

            paletteBtn.className = 'palette-btn';
            if      (isReview)   paletteBtn.classList.add('status-review');
            else if (isAnswered) paletteBtn.classList.add('status-answered');

            if (isAnswered) answeredCount++;
        }

        // Update option-label highlight
        document.querySelectorAll('.option-label').forEach(label => {
            const inp = label.querySelector('input');
            if (inp && inp.checked) label.classList.add('is-selected');
            else label.classList.remove('is-selected');
        });

        const reviewCount    = reviewedQuestions.size;
        const unansweredCount = totalQuestions - answeredCount;
        const pct = totalQuestions > 0 ? (answeredCount / totalQuestions) * 100 : 0;

        elCountAnswered.textContent   = answeredCount;
        elCountReview.textContent     = reviewCount;
        elCountUnanswered.textContent = unansweredCount;
        elAnsweredBadge.textContent   = `${answeredCount} / ${totalQuestions}`;
        elProgressFill.style.width    = `${pct}%`;
        elModalAnswered.textContent   = answeredCount;
        elModalReview.textContent     = reviewCount;
        elModalUnanswered.textContent = unansweredCount;
        elModalWarning.style.display  = (unansweredCount > 0 || reviewCount > 0) ? 'flex' : 'none';

        const summaryText = answeredCount === totalQuestions
            ? 'All questions answered. Submit when ready.'
            : `${unansweredCount} question${unansweredCount !== 1 ? 's' : ''} remaining.`;
        document.getElementById('bottomSummaryText').textContent = summaryText;
    }

    // Mark for review
    document.querySelectorAll('.mark-review-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const idx  = parseInt(this.dataset.questionIndex);
            const card = document.getElementById(`question_card_${idx}`);
            const txt  = this.querySelector('.review-text');
            const icon = this.querySelector('i');

            if (reviewedQuestions.has(idx)) {
                reviewedQuestions.delete(idx);
                card.classList.remove('question-card--flagged');
                this.classList.remove('active');
                txt.textContent  = 'Flag';
                icon.className   = 'far fa-flag';
            } else {
                reviewedQuestions.add(idx);
                card.classList.add('question-card--flagged');
                this.classList.add('active');
                txt.textContent  = 'Flagged';
                icon.className   = 'fas fa-flag';
            }
            updatePalette();
            saveDraft();
        });
    });

    // Clear choice
    document.querySelectorAll('.clear-choice-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const qId = this.dataset.questionId;
            document.querySelectorAll(`input[name="answers[${qId}]"]`).forEach(inp => inp.checked = false);
            updatePalette();
            saveDraft();
        });
    });

    // File input change
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function() {
            const qId       = this.dataset.questionId;
            const preview   = document.getElementById(`file_preview_${qId}`);
            if (this.files && this.files.length > 0) {
                const file      = this.files[0];
                const maxSizeMb = parseFloat(this.dataset.maxSize || 10);
                if (file.size > maxSizeMb * 1024 * 1024) {
                    alert(`File too large — maximum size is ${maxSizeMb} MB.`);
                    this.value = '';
                    preview.style.display = 'none';
                    updatePalette();
                    return;
                }
                preview.style.display = 'flex';
                preview.querySelector('.filename').textContent = file.name;
                preview.querySelector('.filesize').textContent = (file.size / (1024*1024)).toFixed(2) + ' MB';
            } else {
                preview.style.display = 'none';
            }
            updatePalette();
        });
    });

    // MCQ input changes
    document.querySelectorAll('.question-input').forEach(input => {
        input.addEventListener('change', function() {
            updatePalette();
            saveDraft();
        });
    });

    // Palette smooth scroll
    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                document.querySelectorAll('.palette-btn').forEach(b => b.classList.remove('status-current'));
                this.classList.add('status-current');
            }
        });
    });

    // ── 3. DRAFT AUTO-SAVE ───────────────────────────────
    function saveDraft() {
        const draft = { answers: {}, reviews: Array.from(reviewedQuestions) };
        document.querySelectorAll('.question-input:checked').forEach(inp => {
            const name = inp.name;
            if (inp.type === 'checkbox') {
                if (!draft.answers[name]) draft.answers[name] = [];
                draft.answers[name].push(inp.value);
            } else {
                draft.answers[name] = inp.value;
            }
        });
        try { localStorage.setItem(draftStorageKey, JSON.stringify(draft)); } catch(e) {}
    }

    function restoreDraft() {
        try {
            const saved = localStorage.getItem(draftStorageKey);
            if (!saved) return;
            const draft = JSON.parse(saved);
            if (draft.answers) {
                for (const [name, val] of Object.entries(draft.answers)) {
                    if (Array.isArray(val)) {
                        val.forEach(v => {
                            const inp = document.querySelector(`input[name="${name}"][value="${v}"]`);
                            if (inp) inp.checked = true;
                        });
                    } else {
                        const inp = document.querySelector(`input[name="${name}"][value="${val}"]`);
                        if (inp) inp.checked = true;
                    }
                }
            }
            if (draft.reviews) {
                draft.reviews.forEach(idx => {
                    reviewedQuestions.add(idx);
                    const card = document.getElementById(`question_card_${idx}`);
                    if (card) {
                        card.classList.add('question-card--flagged');
                        const btn = card.querySelector('.mark-review-btn');
                        if (btn) {
                            btn.classList.add('active');
                            const txt = btn.querySelector('.review-text');
                            if (txt) txt.textContent = 'Flagged';
                            const icon = btn.querySelector('i');
                            if (icon) icon.className = 'fas fa-flag';
                        }
                    }
                });
            }
        } catch(e) {}
    }

    // ── 4. ANTI-CHEATING ─────────────────────────────────
    if (enableAntiCheating) {
        const modal       = document.getElementById('antiCheatingModal');
        const warnTitle   = document.getElementById('cheatingWarningTitle');
        const warnBody    = document.getElementById('cheatingWarningBody');
        const tabSwitchInput = document.getElementById('tab_switch_count');

        function triggerTabViolation() {
            tabSwitchCount++;
            tabSwitchInput.value = tabSwitchCount;
            if      (tabSwitchCount === 1) { warnTitle.textContent = 'Warning 1 of 3'; warnBody.textContent = 'Tab switch detected. Leaving the exam window is monitored.'; }
            else if (tabSwitchCount === 2) { warnTitle.textContent = 'Final Warning — 2 of 3'; warnBody.textContent = 'One more violation will automatically submit your exam!'; }
            else { warnTitle.textContent = 'Exam Terminated'; warnBody.textContent = 'Repeated violations detected. Submitting your exam now…'; }
            modal.classList.add('is-open');
            if (tabSwitchCount >= 3) setTimeout(submitExamForm, 2000);
        }

        document.addEventListener('visibilitychange', () => { if (document.hidden) triggerTabViolation(); });
        window.addEventListener('blur', () => { if (!document.hidden && tabSwitchCount < 3) triggerTabViolation(); });
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy',  e => e.preventDefault());
        document.addEventListener('cut',   e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && 'IJC'.includes(e.key)) || (e.ctrlKey && e.key === 'u'))
                e.preventDefault();
        });

        document.getElementById('ackCheatingBtn').addEventListener('click', () => {
            modal.classList.remove('is-open');
        });
    }

    // ── 5. FULLSCREEN ────────────────────────────────────
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen && document.exitFullscreen();
            }
        });
    }

    // ── 6. SUBMIT ────────────────────────────────────────
    function submitExamForm() {
        try { localStorage.removeItem(draftStorageKey); } catch(e) {}
        form.submit();
    }

    document.getElementById('finalSubmitBtn').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
        submitExamForm();
    });

    // ── MODAL HELPERS ─────────────────────────────────────
    window.openSubmitModal = function() {
        updatePalette();
        document.getElementById('submitModal').classList.add('is-open');
    };
    window.closeSubmitModal = function() {
        document.getElementById('submitModal').classList.remove('is-open');
    };

    // Close modal on overlay click
    document.getElementById('submitModal').addEventListener('click', function(e) {
        if (e.target === this) closeSubmitModal();
    });

    // ── INIT ──────────────────────────────────────────────
    restoreDraft();
    updatePalette();
});
</script>
@endsection
