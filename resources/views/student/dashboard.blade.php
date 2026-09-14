@extends('layouts.shell')

@section('title', 'Student Dashboard')
@section('nav-center')<span style="font-size:.875rem;font-weight:600;color:var(--text);">Student Dashboard</span>@endsection
@section('nav-actions')
<a href="{{ route('student.profile') }}" class="btn btn-ghost" title="Profile"><i class="fas fa-user"></i></a>
<form action="{{ route('student.logout') }}" method="POST" style="margin:0;">
    @csrf
    <button type="submit" class="btn btn-ghost" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
</form>
@endsection

@section('head')
<style>
.student-dashboard{max-width:1120px;margin:0 auto;padding:2.5rem 1.25rem 4rem}.student-hero{display:flex;justify-content:space-between;gap:1.5rem;align-items:flex-end;margin-bottom:2rem}.student-title{font-size:2rem;font-weight:800;color:var(--text);margin:0 0 .35rem}.student-muted{color:var(--text-muted);margin:0}.profile-strip{display:flex;align-items:center;gap:1rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);padding:1rem 1.25rem;box-shadow:var(--shadow-sm)}.profile-avatar{width:48px;height:48px;border-radius:50%;display:grid;place-items:center;background:var(--color-accent-surface);color:var(--color-accent);font-weight:800;font-size:1.2rem}.profile-data{display:flex;flex-wrap:wrap;gap:.25rem 1rem;font-size:.8rem;color:var(--text-muted)}.profile-data strong{color:var(--text);font-size:.95rem;display:block;width:100%}.student-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:2rem}.student-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.9rem 1rem}.student-stat strong{display:block;font-size:1.35rem;color:var(--text)}.student-stat span{font-size:.78rem;color:var(--text-muted)}.section-heading{font-size:1.05rem;color:var(--text);margin:2rem 0 .85rem}.section-intro{color:var(--text-muted);font-size:.9rem;margin:-.45rem 0 1rem}.exam-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem}.exam-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);padding:1.25rem;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:.9rem}.exam-card h3{font-size:1.05rem;color:var(--text);margin:0}.exam-id{font-size:.75rem;color:var(--color-accent);font-weight:700}.exam-meta{display:flex;gap:1rem;flex-wrap:wrap;color:var(--text-muted);font-size:.8rem}.academic-group{margin-top:1rem;padding:1rem 1.1rem;background:var(--color-accent-surface);border:1px solid var(--border);border-radius:var(--radius-xl)}.academic-group h3{margin:0;color:var(--text);font-size:1rem}.academic-subject{margin-top:.75rem;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--surface);overflow:hidden}.academic-subject summary{cursor:pointer;padding:1rem;font-weight:700;color:var(--text);list-style:none}.academic-subject summary::-webkit-details-marker{display:none}.academic-subject summary:before{content:'▸';color:var(--color-accent);margin-right:.5rem}.academic-subject[open] summary:before{content:'▾'}.academic-chapter{padding:.75rem 1rem 1rem;border-top:1px solid var(--border)}.academic-chapter__title{display:flex;align-items:center;justify-content:space-between;gap:.5rem;color:var(--text);font-size:.9rem;margin-bottom:.65rem}.result-list{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);overflow:hidden}.result-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border)}.result-row:last-child{border-bottom:0}.result-row strong{color:var(--text);display:block}.result-row small{color:var(--text-muted)}.empty-state{color:var(--text-muted);padding:1.5rem;border:1px dashed var(--border-strong);border-radius:var(--radius-xl)}@media(max-width:700px){.student-hero{align-items:flex-start;flex-direction:column}.student-stats{grid-template-columns:1fr}.result-row{align-items:flex-start;flex-direction:column}}
.academic-filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;padding:1rem;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);margin:1rem 0}.academic-filter-result{margin-top:1rem}@media(max-width:700px){.academic-filters{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="student-dashboard page-fade-in">
    <div class="student-hero">
        <div><p class="student-muted">Welcome back</p><h1 class="student-title">{{ $student->name }}</h1><p class="student-muted">Your exams, results, and learning resources in one place.</p></div>
        <div class="profile-strip"><div class="profile-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</div><div class="profile-data"><strong>{{ $student->username }}</strong><span>{{ $student->college ?: 'College not added' }}</span><span>{{ $student->student_group ?: 'Group not added' }}</span></div></div>
    </div>

    <div class="student-stats">
        <div class="student-stat"><strong>{{ $runningExams->count() }}</strong><span>Running now</span></div>
        <div class="student-stat"><strong>{{ $manualExams->count() }}</strong><span>Manual exams</span></div>
        <div class="student-stat"><strong>{{ $results->count() }}</strong><span>Completed exams</span></div>
    </div>

    <h2 class="section-heading"><i class="fas fa-bolt" style="color:var(--color-warning);margin-right:.4rem"></i>Running exams</h2>
    <p class="section-intro">These exams are open right now. Start from here for the fastest access.</p>
    @if($runningExams->isNotEmpty())
    <div class="exam-grid">@foreach($runningExams as $exam)<article class="exam-card"><div><div class="exam-id">{{ $exam->exam_id }}</div><h3>{{ $exam->exam_name }}</h3></div><div class="exam-meta"><span><i class="fas fa-list"></i> {{ $exam->questions_count }} questions</span><span><i class="fas fa-clock"></i> {{ $exam->duration_minutes }} min</span></div><a href="{{ route('student.begin-exam', $exam->id) }}" class="btn btn-primary"><i class="fas fa-play"></i> Start exam</a></article>@endforeach</div>
    @else<div class="empty-state">There are no running exams for you right now. Check back when your teacher publishes one.</div>@endif

    <h2 class="section-heading"><i class="fas fa-sitemap" style="color:var(--color-accent);margin-right:.4rem"></i>My academic exams</h2>
    <p class="section-intro">Your teacher's exams are organized as Group → Subject / Paper → Chapter.</p>
    <div class="academic-filters">
        <div class="field"><label class="field-label" for="student-academic-group">Group</label><select class="field-input" id="student-academic-group"><option value="">Select group</option>@foreach($structuredGroups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
        <div class="field"><label class="field-label" for="student-academic-subject">Subject / Paper</label><select class="field-input" id="student-academic-subject" disabled><option value="">Select subject</option></select></div>
        <div class="field"><label class="field-label" for="student-academic-chapter">Chapter</label><select class="field-input" id="student-academic-chapter" disabled><option value="">Select chapter</option></select></div>
    </div>
    <div id="student-academic-results" class="academic-filter-result empty-state">Select group, subject and chapter to view available exams.</div>
    @php($hasAcademicExams = $structuredGroups->contains(fn ($group) => $group->subjects->contains(fn ($subject) => $subject->chapters->contains(fn ($chapter) => $chapter->exams->isNotEmpty()))))
    <div style="display:none">
    @if($hasAcademicExams)
    @foreach($structuredGroups as $group)
        @if($group->subjects->contains(fn ($subject) => $subject->chapters->contains(fn ($chapter) => $chapter->exams->isNotEmpty())))
        <div class="academic-group">
            <h3><i class="fas fa-layer-group" style="margin-right:.4rem;color:var(--color-accent)"></i>{{ $group->name }}</h3>
            @foreach($group->subjects as $subject)
                @php($subjectHasExams = $subject->chapters->contains(fn ($chapter) => $chapter->exams->isNotEmpty()))
                @if($subjectHasExams)
                <details class="academic-subject">
                    <summary>{{ $subject->name }} <span style="float:right;font-size:.75rem;color:var(--text-muted);">{{ $subject->chapters->filter(fn ($chapter) => $chapter->exams->isNotEmpty())->count() }} chapter(s)</span></summary>
                    @foreach($subject->chapters as $chapter)
                        @if($chapter->exams->isNotEmpty())
                        <div class="academic-chapter">
                            <div class="academic-chapter__title"><span><i class="fas fa-book-open" style="color:var(--color-accent);margin-right:.35rem"></i>{{ $chapter->title }}</span><span style="font-size:.75rem;color:var(--text-muted);">{{ $chapter->exams->count() }} exam(s)</span></div>
                            <div class="exam-grid">
                                @foreach($chapter->exams as $exam)
                                <article class="exam-card">
                                    <div><div class="exam-id">{{ $exam->exam_id }}</div><h3>{{ $exam->exam_name }}</h3></div>
                                    <div class="exam-meta"><span>{{ $exam->questions_count }} questions</span><span>{{ $exam->duration_minutes }} min</span></div>
                                    <a href="{{ route('student.begin-exam', $exam->id) }}" class="btn btn-primary"><i class="fas fa-play"></i> Start exam</a>
                                </article>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        </div>
                    @endforeach
                </details>
                @endif
            @endforeach
        </div>
        @endif
    @endforeach
    @else
        <div class="empty-state" style="margin-top:.75rem;">No academic exams are available for your group yet.</div>
    @endif

    <h2 class="section-heading"><i class="fas fa-random" style="color:var(--color-warning);margin-right:.4rem"></i>Manual exams</h2>
    @if($manualExams->isNotEmpty())
    <div class="exam-grid">@foreach($manualExams as $exam)<article class="exam-card"><div><div class="exam-id">{{ $exam->exam_id }}</div><h3>{{ $exam->exam_name }}</h3></div><div class="exam-meta"><span>{{ $exam->questions_count }} questions</span><span>{{ $exam->duration_minutes }} min</span></div><a href="{{ route('student.begin-exam', $exam->id) }}" class="btn btn-primary"><i class="fas fa-play"></i> Start exam</a></article>@endforeach</div>
    @else<div class="empty-state">No manual exams are currently available.</div>@endif

    <h2 class="section-heading"><i class="fas fa-history" style="color:var(--color-accent);margin-right:.4rem"></i>Past exams</h2>
    @if($pastExams->isNotEmpty())<div class="exam-grid">@foreach($pastExams as $exam)<article class="exam-card"><div><div class="exam-id">{{ $exam->exam_id }}</div><h3>{{ $exam->exam_name }}</h3></div><div class="exam-meta"><span><i class="fas fa-calendar"></i> {{ optional($exam->end_time)->format('M d, Y') ?: 'Closed' }}</span><span><i class="fas fa-list"></i> {{ $exam->questions_count }}</span></div><span class="student-muted" style="font-size:.8rem;">{{ $results->where('exam_id', $exam->id)->isNotEmpty() ? 'Result available below' : 'Not attempted' }}</span></article>@endforeach</div>@else<div class="empty-state">Your past exams will appear here.</div>@endif

    <h2 class="section-heading"><i class="fas fa-chart-line" style="color:var(--color-success);margin-right:.4rem"></i>My results</h2>
    @if($results->isNotEmpty())<div class="result-list">@foreach($results as $result)<div class="result-row"><div><strong>{{ $result->exam->exam_name ?? 'Exam' }}</strong><small>{{ optional($result->submitted_at)->format('M d, Y · h:i A') }}</small></div><a href="{{ route('student.history-result', $result->id) }}" class="btn btn-ghost"><i class="fas fa-arrow-right"></i> View result</a></div>@endforeach</div>@else<div class="empty-state">Completed exam results will appear here.</div>@endif

    <h2 class="section-heading"><i class="fas fa-book-open" style="color:var(--color-accent);margin-right:.4rem"></i>Resources</h2>
    <div class="empty-state">Study resources and notices from your teacher will appear here soon.</div>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const groups = @json($structuredGroups);
    const group = document.getElementById('student-academic-group');
    const subject = document.getElementById('student-academic-subject');
    const chapter = document.getElementById('student-academic-chapter');
    const results = document.getElementById('student-academic-results');
    if (!group || !subject || !chapter || !results) return;

    const reset = (select, label) => {
        select.innerHTML = `<option value="">${label}</option>`;
        select.disabled = true;
    };
    const render = () => {
        const selectedGroup = groups.find(item => String(item.id) === group.value);
        const selectedSubject = selectedGroup?.subjects.find(item => String(item.id) === subject.value);
        const selectedChapter = selectedSubject?.chapters.find(item => String(item.id) === chapter.value);
        const exams = selectedChapter?.exams || [];
        if (!selectedChapter) {
            results.className = 'academic-filter-result empty-state';
            results.textContent = 'Select group, subject and chapter to view available exams.';
            return;
        }
        if (!exams.length) {
            results.className = 'academic-filter-result empty-state';
            results.textContent = 'No active exam is available for this chapter right now.';
            return;
        }
        results.className = 'academic-filter-result exam-grid';
        results.innerHTML = exams.map(exam => {
            const now = new Date();
            const starts = exam.start_time ? new Date(exam.start_time) : null;
            const ends = exam.end_time ? new Date(exam.end_time) : null;
            const isScheduledFuture = starts && starts > now;
            const isLateAttempt = ends && ends < now;
            const action = isScheduledFuture
                ? `<span class="student-muted">Starts ${starts.toLocaleString()}</span>`
                : `<div style="display:flex; flex-direction:column; gap:.45rem; align-items:flex-start;">
                    ${isLateAttempt ? '<span class="badge badge-warning">Late attempt · no rank</span>' : ''}
                    <a href="/student/exam/${exam.id}/begin" class="btn btn-primary"><i class="fas fa-play"></i> Start exam</a>
                </div>`;
            return `<article class="exam-card">
            <div><div class="exam-id">${exam.exam_id}</div><h3>${exam.exam_name}</h3></div>
            <div class="exam-meta"><span><i class="fas fa-list"></i> ${exam.questions_count} questions</span><span><i class="fas fa-clock"></i> ${exam.duration_minutes} min</span></div>
            ${action}
        </article>`;
        }).join('');
    };
    group.addEventListener('change', () => {
        reset(subject, 'Select subject');
        reset(chapter, 'Select chapter');
        const selected = groups.find(item => String(item.id) === group.value);
        (selected?.subjects || []).forEach(item => subject.add(new Option(item.name, item.id)));
        subject.disabled = !selected;
        render();
    });
    subject.addEventListener('change', () => {
        reset(chapter, 'Select chapter');
        const selected = groups.find(item => String(item.id) === group.value)?.subjects.find(item => String(item.id) === subject.value);
        (selected?.chapters || []).forEach(item => chapter.add(new Option(item.title, item.id)));
        chapter.disabled = !selected;
        render();
    });
    chapter.addEventListener('change', render);
})();
</script>
@endsection
