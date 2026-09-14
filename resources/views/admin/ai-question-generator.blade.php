@extends('layouts.shell')

@section('title', 'AI Question Workspace')
@section('meta-description', 'Chat with your learning sources and create exam questions.')

@section('nav-center')
<span style="font-size:0.875rem; font-weight:600; color:var(--text);">AI Question Workspace</span>
@endsection

@section('nav-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;"><i class="fas fa-arrow-left"></i> Dashboard</a>
@endsection

@section('head')
<style>
    .workspace { max-width: 1180px; margin: 0 auto; padding: 2rem 1.25rem; }
    .workspace-header { margin-bottom: 1.25rem; }
    .workspace-header h1 { margin: 0 0 .35rem; font-size: 1.8rem; font-weight: 800; color: var(--text); }
    .workspace-header p { margin: 0; color: var(--text-muted); }
    .workspace-grid { display:grid; grid-template-columns: 330px minmax(0, 1fr); gap: 1rem; align-items: start; }
    .workspace-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); overflow: hidden; }
    .workspace-panel__head { padding: 1rem 1.25rem; background: var(--surface-alt); border-bottom: 1px solid var(--border); font-weight: 700; color:var(--text); }
    .workspace-panel__body { padding: 1.25rem; }
    .source-status { padding:.75rem; margin-top:.75rem; background:var(--color-accent-surface); border-radius:var(--radius-md); color:var(--text-secondary); font-size:.8rem; line-height:1.45; }
    .chat-shell { min-height: 590px; display:flex; flex-direction:column; }
    .chat-messages { flex:1; min-height:390px; max-height:620px; overflow:auto; padding:1.25rem; background:var(--surface-alt); }
    .chat-empty { height:100%; min-height:350px; display:grid; place-items:center; text-align:center; color:var(--text-muted); padding:2rem; }
    .chat-empty i { display:block; font-size:2rem; color:var(--color-accent); margin-bottom:.75rem; }
    .chat-message { max-width:86%; padding:.75rem .9rem; border-radius:var(--radius-md); margin-bottom:.75rem; white-space:pre-wrap; line-height:1.5; font-size:.9rem; }
    .chat-message--user { margin-left:auto; background:var(--color-accent); color:white; }
    .chat-message--assistant { background:var(--surface); border:1px solid var(--border); color:var(--text); }
    .question-preview { margin-top:.75rem; padding:.75rem; border:1px solid var(--border); background:var(--surface); border-radius:var(--radius-md); font-size:.82rem; }
    .question-preview strong { color:var(--text); }
    .chat-compose { padding:1rem; border-top:1px solid var(--border); background:var(--surface); }
    .chat-compose textarea { min-height:76px; resize:vertical; }
    .compose-actions { display:flex; justify-content:space-between; gap:.75rem; align-items:center; margin-top:.75rem; }
    .compose-hint { color:var(--text-muted); font-size:.76rem; }
    @media (max-width: 800px) { .workspace-grid { grid-template-columns:1fr; } .chat-shell { min-height:520px; } }
</style>
@endsection

@section('content')
<div class="workspace page-fade-in">
    <div class="workspace-header">
        <h1><i class="fas fa-comments" style="color:var(--color-accent);"></i> AI Question Workspace</h1>
        <p>Add up to 5 PDF/image files and 5 YouTube sources, then describe and refine the question set through chat.</p>
    </div>

    <div class="workspace-grid">
        <aside class="workspace-panel">
            <div class="workspace-panel__head"><i class="fas fa-book-open"></i> Source setup</div>
            <div class="workspace-panel__body">
                <form id="chatForm" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label class="field-label" for="exam_id">Target exam</label>
                        <select class="field-input" id="exam_id" name="exam_id" required>
                            <option value="">Select an exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->exam_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label" for="sources">PDF or image (up to 5, 10 MB each)</label>
                        <input class="field-input" type="file" id="sources" name="sources[]" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple style="padding:.45rem;">
                    </div>
                    <div class="field">
                        <label class="field-label">YouTube URLs (up to 5)</label>
                        <div id="youtubeUrls">
                            <input class="field-input youtube-url" type="url" name="youtube_urls[]" placeholder="https://youtube.com/watch?v=...">
                        </div>
                        <button type="button" class="btn btn-ghost" id="addYoutubeUrl" style="margin-top:.5rem; font-size:.8rem;"><i class="fas fa-plus"></i> Add another URL</button>
                    </div>
                    <div class="source-status"><i class="fas fa-shield-halved"></i> The AI is instructed to use only your source and the fixed MCQ schema.</div>
                </form>
            </div>
        </aside>

        <section class="workspace-panel chat-shell">
            <div class="workspace-panel__head"><i class="fas fa-message"></i> Question chat</div>
            <div class="chat-messages" id="chatMessages">
                <div class="chat-empty" id="chatEmpty"><div><i class="fas fa-sparkles"></i><strong>What should we create?</strong><br>For example: “এই অধ্যায় থেকে ২৫টি মাঝারি মানের MCQ তৈরি করো, বাংলায়, প্রতিটিতে ৪টি অপশন ও সংক্ষিপ্ত ব্যাখ্যা দাও।”</div></div>
            </div>
            <div class="chat-compose">
                <textarea class="field-input" id="prompt" placeholder="Tell the AI what to create or change..." required></textarea>
                <div class="compose-actions">
                    <span class="compose-hint">You can ask for revisions after the first result.</span>
                    <div style="display:flex; gap:.5rem;"><button type="button" class="btn btn-ghost" id="downloadButton" hidden><i class="fas fa-download"></i> Excel</button><button type="button" class="btn btn-primary" id="sendButton"><i class="fas fa-paper-plane"></i> Send</button></div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const form = document.getElementById('chatForm');
    const messages = document.getElementById('chatMessages');
    const empty = document.getElementById('chatEmpty');
    const prompt = document.getElementById('prompt');
    const send = document.getElementById('sendButton');
    const download = document.getElementById('downloadButton');
    const sources = document.getElementById('sources');
    const youtubeUrls = document.getElementById('youtubeUrls');
    const addYoutubeUrl = document.getElementById('addYoutubeUrl');
    const history = [];
    let workbookBase64 = null;

    sources.addEventListener('change', function () {
        const files = Array.from(sources.files);
        if (files.length > 5) {
            sources.value = '';
            addMessage('assistant', 'You can select up to 5 PDF/image files.');
            return;
        }
        const oversized = files.find(function (file) { return file.size > 10 * 1024 * 1024; });
        if (oversized) {
            sources.value = '';
            addMessage('assistant', oversized.name + ' is larger than 10 MB. Each file must be 10 MB or smaller.');
        }
    });

    addYoutubeUrl.addEventListener('click', function () {
        if (youtubeUrls.querySelectorAll('.youtube-url').length >= 5) return;
        const input = document.createElement('input');
        input.className = 'field-input youtube-url';
        input.type = 'url';
        input.name = 'youtube_urls[]';
        input.placeholder = 'https://youtube.com/watch?v=...';
        input.style.marginTop = '.5rem';
        youtubeUrls.appendChild(input);
        if (youtubeUrls.querySelectorAll('.youtube-url').length >= 5) addYoutubeUrl.hidden = true;
    });

    function addMessage(role, text, questions) {
        empty.hidden = true;
        const bubble = document.createElement('div');
        bubble.className = 'chat-message chat-message--' + role;
        bubble.textContent = text;
        if (questions) {
            const preview = document.createElement('div');
            preview.className = 'question-preview';
            questions.slice(0, 3).forEach(function (question, index) {
                const row = document.createElement('div');
                row.style.marginBottom = index === Math.min(questions.length, 3) - 1 ? '0' : '.65rem';
                row.innerHTML = '<strong>' + (index + 1) + '. ' + question.question + '</strong><br>' + question.options.map(function (option, optionIndex) { return String.fromCharCode(65 + optionIndex) + '. ' + option; }).join('<br>');
                preview.appendChild(row);
            });
            if (questions.length > 3) preview.innerHTML += '<br><span>+' + (questions.length - 3) + ' more questions in Excel</span>';
            bubble.appendChild(preview);
        }
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    send.addEventListener('click', async function () {
        const text = prompt.value.trim();
        if (!text || !form.exam_id.value) { addMessage('assistant', 'Select a target exam and write your instruction first.'); return; }
        const hasYoutubeUrl = Array.from(youtubeUrls.querySelectorAll('.youtube-url')).some(function (input) { return input.value.trim(); });
        if (!sources.files.length && !hasYoutubeUrl && !history.length) { addMessage('assistant', 'Add a PDF/image or YouTube URL before starting the chat.'); return; }
        addMessage('user', text);
        prompt.value = '';
        send.disabled = true;
        send.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Thinking';
        const data = new FormData(form);
        data.append('prompt', text);
        data.append('history', JSON.stringify(history));
        try {
            const response = await fetch('{{ route('admin.chat-ai-questions') }}', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || result.detail || 'AI generation failed.');
            addMessage('assistant', result.message, result.questions);
            history.push({ role: 'user', text: text }, { role: 'assistant', text: result.message });
            workbookBase64 = result.xlsx_base64;
            download.hidden = false;
        } catch (error) { addMessage('assistant', error.message); }
        send.disabled = false;
        send.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    });

    download.addEventListener('click', function () {
        if (!workbookBase64) return;
        const binary = atob(workbookBase64);
        const bytes = Uint8Array.from(binary, function (character) { return character.charCodeAt(0); });
        const url = URL.createObjectURL(new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
        const link = document.createElement('a'); link.href = url; link.download = 'ai-generated-questions.xlsx'; link.click(); URL.revokeObjectURL(url);
    });
})();
</script>
@endsection
