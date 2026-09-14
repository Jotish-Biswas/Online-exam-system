@extends('layouts.shell')

@section('title', 'Student Profile')
@section('nav-center')<span style="font-size:.875rem;font-weight:600;color:var(--text);">Student Profile</span>@endsection
@section('nav-actions')
<a href="{{ route('student.dashboard') }}" class="btn btn-ghost" title="Dashboard"><i class="fas fa-house"></i></a>
<form action="{{ route('student.logout') }}" method="POST" style="margin:0;">
    @csrf
    <button type="submit" class="btn btn-ghost" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
</form>
@endsection

@section('head')
<style>
.student-profile{max-width:760px;margin:0 auto;padding:2.5rem 1.25rem 4rem}.profile-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);padding:1.5rem;box-shadow:var(--shadow-sm)}.profile-heading{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}.profile-avatar{width:58px;height:58px;border-radius:50%;display:grid;place-items:center;background:var(--color-accent-surface);color:var(--color-accent);font-weight:800;font-size:1.4rem}.profile-heading h1{font-size:1.5rem;color:var(--text);margin:0}.profile-heading p{color:var(--text-muted);margin:.2rem 0 0}.profile-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.profile-detail{padding:.9rem 1rem;background:var(--surface-alt);border:1px solid var(--border);border-radius:var(--radius-lg)}.profile-detail span{display:block;color:var(--text-muted);font-size:.75rem;margin-bottom:.25rem}.profile-detail strong{color:var(--text);font-size:.95rem}@media(max-width:600px){.profile-details{grid-template-columns:1fr}}
</style>
@endsection

@section('content')
<div class="student-profile page-fade-in">
    <div class="profile-card">
        <div class="profile-heading">
            <div class="profile-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</div>
            <div><h1>{{ $student->name }}</h1><p>Student profile</p></div>
        </div>
        <div class="profile-details">
            <div class="profile-detail"><span>Student ID</span><strong>{{ $student->username }}</strong></div>
            <div class="profile-detail"><span>Index number</span><strong>{{ $student->index_no ?: 'Not added' }}</strong></div>
            <div class="profile-detail"><span>Email</span><strong>{{ $student->email ?: 'Not added' }}</strong></div>
            <div class="profile-detail"><span>WhatsApp</span><strong>{{ $student->whatsapp ?: 'Not added' }}</strong></div>
            <div class="profile-detail"><span>College</span><strong>{{ $student->college ?: 'Not added' }}</strong></div>
            <div class="profile-detail"><span>Group</span><strong>{{ $student->student_group ?: 'Not added' }}</strong></div>
            <div class="profile-detail" style="grid-column:1/-1"><span>Address</span><strong>{{ $student->address ?: 'Not added' }}</strong></div>
        </div>
    </div>
</div>
@endsection