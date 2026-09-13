@extends('layouts.shell')

@section('title', 'Check Your Results')
@section('meta-description', 'Check your exam results and review your answers.')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap;">
        Check Results
    </span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('student.login') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;">
    <i class="fas fa-home"></i> Home
</a>
@endsection

@section('head')
<style>
    .check-root {
        min-height: calc(100vh - 56px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
    }

    .check-wrap {
        width: 100%;
        max-width: 440px;
    }

    .check-eyebrow {
        text-align: center;
        margin-bottom: 2rem;
    }

    .check-eyebrow__mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        margin-bottom: 1rem;
        color: var(--text-muted);
        font-size: 1.4rem;
    }

    .check-eyebrow__title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        letter-spacing: -0.03em;
        line-height: 1.2;
        margin: 0 0 0.35rem;
    }

    .check-eyebrow__sub {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin: 0;
    }

    .form-section {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
</style>
@endsection

@section('content')
<div class="check-root">
    <div class="check-wrap page-fade-in">

        <div class="check-eyebrow">
            <div class="check-eyebrow__mark" aria-hidden="true">
                <i class="fas fa-search"></i>
            </div>
            <h1 class="check-eyebrow__title">Find Your Result</h1>
            <p class="check-eyebrow__sub">Enter the credentials you used during the exam.</p>
        </div>

        <div class="card" style="box-shadow: var(--shadow-md);">
            <div class="card__body">
                <form action="{{ route('student.checkResults') }}" method="POST" novalidate>
                    @csrf
                    
                    <div class="form-section">
                        <div class="field">
                            <label class="field-label" for="exam_id">Exam ID</label>
                            <input 
                                class="field-input {{ $errors->has('exam_id') ? 'is-error' : '' }}" 
                                type="text" 
                                id="exam_id" 
                                name="exam_id" 
                                value="{{ old('exam_id') }}" 
                                placeholder="e.g. PHYS-2024-A"
                                required>
                            @error('exam_id')
                                <p class="field-error">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="field">
                            <label class="field-label" for="student_id">Student ID</label>
                            <input 
                                class="field-input {{ $errors->has('student_id') ? 'is-error' : '' }}" 
                                type="text" 
                                id="student_id" 
                                name="student_id" 
                                value="{{ old('student_id') }}" 
                                placeholder="Your student ID"
                                required>
                            @error('student_id')
                                <p class="field-error">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="field">
                            <label class="field-label" for="index_no">Index Number</label>
                            <input 
                                class="field-input {{ $errors->has('index_no') ? 'is-error' : '' }}" 
                                type="text" 
                                id="index_no" 
                                name="index_no" 
                                value="{{ old('index_no') }}" 
                                placeholder="Your roll or index number"
                                required>
                            @error('index_no')
                                <p class="field-error">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:0.5rem;">
                            <i class="fas fa-arrow-right"></i>
                            View Results
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection