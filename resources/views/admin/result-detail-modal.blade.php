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
                $sections = $result->evaluateSections();
                $passed = $sections['overall_passed'];
                $percentage = $sections['overall_ready'] ? ($sections['overall_percentage'] ?? 0) : ($sections['mcq_percentage'] ?? 0);
                $score = $sections['overall_ready'] ? $sections['obtained'] : $sections['mcq_obtained'];
                $total = $sections['overall_ready'] ? $sections['total'] : $sections['mcq_total'];
            @endphp

            <div style="display:flex; flex-wrap:wrap; gap:1.5rem; margin-bottom:2rem; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.5rem;">
                <div style="flex-shrink:0; width:90px; height:90px; border-radius:50%; background:{{ !$sections['overall_ready'] ? 'var(--color-warning)' : ($passed ? 'var(--color-success)' : 'var(--color-danger)') }}; color:white; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <span style="font-size:1.25rem; font-weight:800; line-height:1;">{{ $sections['overall_ready'] ? $percentage.'%' : '...' }}</span>
                    <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase;">{{ !$sections['overall_ready'] ? 'Wait' : ($passed ? 'Pass' : 'Fail') }}</span>
                </div>
                <div style="flex:1; min-width:200px; display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:center;">
                    <div><div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Student ID</div><div style="font-weight:600;">{{ $result->student_id }}</div></div>
                    <div><div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Index No</div><div style="font-weight:600;">{{ $result->index_no }}</div></div>
                    <div><div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Score</div><div style="font-weight:600;">{{ number_format((float)$score, 2) }} / {{ number_format((float)$total, 2) }}</div></div>
                    <div><div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase;">Correct</div><div style="font-weight:600;">{{ $sections['correct_count'] }} / {{ $sections['mcq_count'] + $sections['writing_count'] }}</div></div>
                </div>
            </div>

            <h4 style="font-size:1rem; font-weight:700; margin:0 0 1rem;">Question Breakdown</h4>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                @foreach($result->studentAnswers as $index => $answer)
                    <div style="border:1px solid {{ $answer->isFileUpload() ? ($answer->is_graded ? 'var(--color-success)' : 'var(--color-warning)') : ($answer->answer && $answer->answer->is_correct ? 'var(--color-success)' : 'var(--color-danger)') }}; border-radius:var(--radius-md); padding:1rem; background:var(--surface);">
                        <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                            <strong style="font-size:0.875rem;">Q{{ $index + 1 }}. {{ $answer->question->question_text }}</strong>
                            @if($answer->isFileUpload())
                                <span class="badge {{ $answer->is_graded ? 'badge-success' : 'badge-warning' }}">{{ $answer->is_graded ? 'Graded' : 'Pending' }}</span>
                            @elseif($answer->answer)
                                <span class="badge {{ $answer->answer->is_correct ? 'badge-success' : 'badge-danger' }}">{{ $answer->answer->is_correct ? 'Correct' : 'Incorrect' }}</span>
                            @else
                                <span class="badge badge-neutral">No Answer</span>
                            @endif
                        </div>
                        <div style="font-size:0.8125rem; background:var(--surface-alt); padding:0.75rem; border-radius:var(--radius-sm);">
                            @if($answer->isFileUpload())
                                <strong>File:</strong> {{ $answer->original_filename ?? 'Submission' }}
                                @if($answer->is_graded)<div><strong>Score:</strong> {{ number_format((float)$answer->manual_score, 2) }}/{{ number_format((float)($answer->question->marks ?? 1), 2) }}</div>@endif
                            @elseif($answer->answer)
                                <strong>Student:</strong> <span style="color:{{ $answer->answer->is_correct ? 'var(--color-success)' : 'var(--color-danger)' }};">{{ $answer->answer->answer_text }}</span>
                                @if(!$answer->answer->is_correct)
                                    @php($correctAnswer = $answer->question->answers->where('is_correct', true)->first())
                                    @if($correctAnswer)<div><strong>Correct:</strong> <span style="color:var(--color-success);">{{ $correctAnswer->answer_text }}</span></div>@endif
                                @endif
                            @else
                                <span style="color:var(--text-muted); font-style:italic;">Left blank.</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
