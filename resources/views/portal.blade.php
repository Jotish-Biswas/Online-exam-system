@extends('layouts.shell')

@section('title', 'Sign In')
@section('meta-description', 'SITC Online Exam System — student exam access and teacher login portal.')

@section('nav-actions')
{{-- no extra nav items on login --}}
@endsection

@section('head')
<style>
    .portal-root {
        min-height: calc(100vh - 56px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
    }

    .portal-wrap {
        width: 100%;
        max-width: 440px;
    }

    .portal-eyebrow {
        text-align: center;
        margin-bottom: 2rem;
    }

    .portal-eyebrow__mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        background: var(--color-accent-surface);
        border: 1px solid rgba(10,102,194,.12);
        border-radius: var(--radius-lg);
        margin-bottom: 1rem;
        color: var(--color-accent);
        font-size: 1.4rem;
    }

    .portal-eyebrow__title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.03em;
        line-height: 1.2;
        margin: 0 0 0.35rem;
    }

    .portal-eyebrow__sub {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin: 0;
    }

    /* Role tabs */
    .role-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 3px;
        margin-bottom: 1.75rem;
    }

    .role-tab {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.6rem 0.75rem;
        border-radius: calc(var(--radius-md) - 2px);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        border: none;
        background: transparent;
        transition: background var(--dur-fast) var(--ease-out),
                    color var(--dur-fast) var(--ease-out),
                    box-shadow var(--dur-fast) var(--ease-out);
        user-select: none;
        font-family: var(--font-sans);
    }

    .role-tab.is-active {
        background: var(--surface);
        color: var(--text);
        box-shadow: var(--shadow-sm);
    }

    /* Form sections */
    .form-section {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-section--hidden {
        display: none;
    }

    /* Form footer */
    .form-footer {
        margin-top: 0.25rem;
        padding-top: 1.125rem;
        border-top: 1px solid var(--border);
        text-align: center;
        font-size: 0.8125rem;
        color: var(--text-muted);
    }

    .form-footer a {
        color: var(--color-accent);
        text-decoration: none;
        font-weight: 500;
    }
    .form-footer a:hover { text-decoration: underline; }
</style>
@endsection

@section('content')
<div class="portal-root">
    <div class="portal-wrap page-fade-in">

        {{-- ── Eyebrow / Branding ── --}}
        <div class="portal-eyebrow">
            <div class="portal-eyebrow__mark" aria-hidden="true">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="portal-eyebrow__title">SITC Exam Portal</h1>
            <p class="portal-eyebrow__sub">Welcome — select your role to continue</p>
        </div>

        {{-- ── Card ── --}}
        <div class="card" style="box-shadow: var(--shadow-md);">

            @php
                $showTeacher = request('role') === 'teacher'
                    || old('username') !== null
                    || $errors->has('username') || $errors->has('password');
            @endphp

            <div class="card__body">
                {{-- Role Toggle --}}
                <div class="role-tabs" role="tablist" aria-label="Select role">
                    <button
                        role="tab"
                        aria-selected="{{ !$showTeacher ? 'true' : 'false' }}"
                        aria-controls="panel-student"
                        id="tab-student"
                        class="role-tab {{ !$showTeacher ? 'is-active' : '' }}"
                        onclick="switchTab('student')"
                        type="button">
                        <i class="fas fa-user-graduate" style="font-size:0.85rem;"></i>
                        As Student
                    </button>
                    <button
                        role="tab"
                        aria-selected="{{ $showTeacher ? 'true' : 'false' }}"
                        aria-controls="panel-teacher"
                        id="tab-teacher"
                        class="role-tab {{ $showTeacher ? 'is-active' : '' }}"
                        onclick="switchTab('teacher')"
                        type="button">
                        <i class="fas fa-chalkboard-teacher" style="font-size:0.85rem;"></i>
                        As Teacher
                    </button>
                </div>

                {{-- ── Student Panel ── --}}
                <div id="panel-student"
                     role="tabpanel"
                     aria-labelledby="tab-student"
                     class="{{ $showTeacher ? 'form-section form-section--hidden' : 'form-section' }}">

                    <div style="margin-bottom:0.25rem;">
                        <p style="font-size:0.8125rem; color:var(--text-muted); margin:0; line-height:1.5;">
                            Enter the details your teacher provided to access the exam.
                        </p>
                    </div>

                    <form action="{{ route('student.authenticate') }}" method="POST" novalidate>
                        @csrf

                        <div class="form-section">
                            <div class="field">
                                <label class="field-label" for="exam_id">Exam ID / Access Key</label>
                                <input
                                    class="field-input {{ $errors->has('exam_id') ? 'is-error' : '' }}"
                                    type="text"
                                    id="exam_id"
                                    name="exam_id"
                                    value="{{ old('exam_id') }}"
                                    placeholder="e.g. PHYS-2024-A"
                                    autocomplete="off"
                                    required>
                                @error('exam_id')
                                    <p class="field-error">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="field">
                                <label class="field-label" for="student_id">Student Name / ID</label>
                                <input
                                    class="field-input {{ $errors->has('student_id') ? 'is-error' : '' }}"
                                    type="text"
                                    id="student_id"
                                    name="student_id"
                                    value="{{ old('student_id') }}"
                                    placeholder="Your full name or student ID"
                                    autocomplete="name"
                                    required>
                                @error('student_id')
                                    <p class="field-error">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="field">
                                <label class="field-label" for="index_no">Roll / Index Number</label>
                                <input
                                    class="field-input {{ $errors->has('index_no') ? 'is-error' : '' }}"
                                    type="text"
                                    id="index_no"
                                    name="index_no"
                                    value="{{ old('index_no') }}"
                                    placeholder="Your roll or index number"
                                    autocomplete="off"
                                    required>
                                @error('index_no')
                                    <p class="field-error">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:0.25rem;">
                                <i class="fas fa-arrow-right"></i>
                                Enter Exam
                            </button>
                        </div>
                    </form>

                    <div class="form-footer">
                        <a href="{{ route('student.checkResultsForm') }}">
                            <i class="fas fa-chart-bar" style="margin-right:0.3rem;"></i>Check my exam results
                        </a>
                    </div>
                </div>

                {{-- ── Teacher Panel ── --}}
                <div id="panel-teacher"
                     role="tabpanel"
                     aria-labelledby="tab-teacher"
                     class="{{ !$showTeacher ? 'form-section form-section--hidden' : 'form-section' }}">

                    <div style="margin-bottom:0.25rem;">
                        <p style="font-size:0.8125rem; color:var(--text-muted); margin:0; line-height:1.5;">
                            Sign in to manage exams, grade answers, and view results.
                        </p>
                    </div>

                    <form action="{{ route('admin.authenticate') }}" method="POST" novalidate>
                        @csrf

                        <div class="form-section">
                            <div class="field">
                                <label class="field-label" for="username">Username</label>
                                <input
                                    class="field-input {{ $errors->has('username') ? 'is-error' : '' }}"
                                    type="text"
                                    id="username"
                                    name="username"
                                    value="{{ old('username') }}"
                                    placeholder="Your username"
                                    autocomplete="username"
                                    required>
                                @error('username')
                                    <p class="field-error">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="field">
                                <label class="field-label" for="password">Password</label>
                                <div style="position:relative;">
                                    <input
                                        class="field-input {{ $errors->has('password') ? 'is-error' : '' }}"
                                        type="password"
                                        id="password"
                                        name="password"
                                        placeholder="••••••••"
                                        autocomplete="current-password"
                                        style="padding-right:2.75rem;"
                                        required>
                                    <button
                                        type="button"
                                        id="togglePwd"
                                        aria-label="Show/hide password"
                                        style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-muted); padding:0.2rem; line-height:1;">
                                        <i class="far fa-eye" id="pwdIcon" style="font-size:0.9rem;"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="field-error">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:0.25rem;">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In to Dashboard
                            </button>
                        </div>
                    </form>
                </div>

            </div>{{-- /card__body --}}
        </div>{{-- /card --}}

        {{-- Footer note --}}
        <p style="text-align:center; font-size:0.75rem; color:var(--text-faint); margin-top:1.25rem; line-height:1.5;">
            SITC Online Exam System &mdash; Secure &amp; Monitored
        </p>

    </div>
</div>
@endsection

@section('scripts')
<script>
function switchTab(role) {
    const studentPanel  = document.getElementById('panel-student');
    const teacherPanel  = document.getElementById('panel-teacher');
    const tabStudent    = document.getElementById('tab-student');
    const tabTeacher    = document.getElementById('tab-teacher');

    if (role === 'teacher') {
        studentPanel.classList.add('form-section--hidden');
        teacherPanel.classList.remove('form-section--hidden');
        tabTeacher.classList.add('is-active');
        tabStudent.classList.remove('is-active');
        tabTeacher.setAttribute('aria-selected', 'true');
        tabStudent.setAttribute('aria-selected', 'false');
    } else {
        teacherPanel.classList.add('form-section--hidden');
        studentPanel.classList.remove('form-section--hidden');
        tabStudent.classList.add('is-active');
        tabTeacher.classList.remove('is-active');
        tabStudent.setAttribute('aria-selected', 'true');
        tabTeacher.setAttribute('aria-selected', 'false');
    }
}

// Password visibility toggle
const toggleBtn = document.getElementById('togglePwd');
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('pwdIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'far fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'far fa-eye';
        }
    });
}
</script>
@endsection
