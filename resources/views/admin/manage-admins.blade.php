@extends('layouts.shell')

@section('title', 'Manage Admins')

@section('nav-center')
<div style="min-width:0; display:flex; align-items:center; gap:0.75rem; overflow:hidden;">
    <span style="font-size:0.875rem; font-weight:600; color:var(--text); white-space:nowrap;">
        Manage Admins
    </span>
</div>
@endsection

@section('nav-actions')
<a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem; margin-right:0.5rem;" title="Back to Dashboard">
    <i class="fas fa-arrow-left"></i> <span class="nav-text-hidden">Dashboard</span>
</a>
<form action="{{ route('admin.logout') }}" method="POST" style="margin:0;">
    @csrf
    <button type="submit" class="btn btn-ghost" style="padding:0.4rem 0.75rem; color:var(--color-danger);" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </button>
</form>
@endsection

@section('head')
<style>
    .page-container {
        padding-top: 2.5rem;
        padding-bottom: 4rem;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text);
        margin: 0 0 0.25rem;
        letter-spacing: -0.02em;
    }
    
    .page-subtitle {
        font-size: 0.9375rem;
        color: var(--text-muted);
        margin: 0;
    }

    /* Layout grid */
    .admin-layout {
        display: grid;
        gap: 2rem;
    }
    @media (min-width: 992px) {
        .admin-layout { grid-template-columns: 3fr 2fr; align-items: start; }
    }

    /* Admin list */
    .admin-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .admin-item {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: var(--shadow-sm);
        transition: box-shadow var(--dur-std) var(--ease-out);
    }
    .admin-item:hover {
        box-shadow: var(--shadow-md);
    }

    .admin-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--color-accent-surface);
        color: var(--color-accent);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .admin-item.is-inactive .admin-avatar {
        background: var(--surface-2);
        color: var(--text-muted);
    }

    .admin-info {
        flex: 1;
        min-width: 0;
    }

    .admin-name {
        font-weight: 700;
        color: var(--text);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .admin-sub {
        font-size: 0.8125rem;
        color: var(--text-muted);
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
    }
    
    .admin-sub span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .admin-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Badges */
    .badge-you {
        font-size: 0.65rem;
        background: var(--color-accent-surface);
        color: var(--color-accent);
        padding: 0.15rem 0.45rem;
        border-radius: var(--radius-sm);
        font-weight: 700;
        letter-spacing: 0.05em;
    }
    .badge-inactive {
        font-size: 0.65rem;
        background: var(--surface-2);
        color: var(--text-muted);
        padding: 0.15rem 0.45rem;
        border-radius: var(--radius-sm);
        font-weight: 700;
        letter-spacing: 0.05em;
    }

    /* Create Card */
    .create-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        position: sticky;
        top: 80px;
    }
    
    .create-card__header {
        background: var(--surface-alt);
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    
    .create-card__header h5 {
        margin: 0 0 0.25rem;
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .create-card__body {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group:last-child {
        margin-bottom: 1.5rem;
    }

    @media (max-width: 640px) {
        .admin-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        .admin-actions {
            width: 100%;
        }
        .admin-actions .btn {
            flex: 1;
        }
        .nav-text-hidden { display: none; }
    }
</style>
@endsection

@section('content')
<div class="page-container page-fade-in">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-users-cog" style="color:var(--color-accent); margin-right:0.5rem;"></i> Manage Admins</h1>
            <p class="page-subtitle">Create and manage administrator accounts.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="notice notice--success" style="margin-bottom: 2rem;">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    
    @if(session('error'))
        <div class="notice notice--danger" style="margin-bottom: 2rem;">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="admin-layout">
        
        {{-- Left: Admin List --}}
        <div>
            <h3 style="font-size:0.875rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); font-weight:700; margin:0 0 1rem;">
                Existing Admins ({{ $admins->count() }})
            </h3>
            
            @if($admins->count() > 0)
                <div class="admin-list">
                    @foreach($admins as $admin)
                        @php
                            $isInactive = str_starts_with($admin->role, 'inactive_');
                            $isCurrentUser = $admin->id === auth()->id();
                        @endphp
                        <div class="admin-item {{ $isInactive ? 'is-inactive' : '' }}">
                            <div class="admin-avatar">
                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                            </div>
                            <div class="admin-info">
                                <div class="admin-name">
                                    {{ $admin->name }}
                                    @if($isCurrentUser)
                                        <span class="badge-you">YOU</span>
                                    @endif
                                    @if($isInactive)
                                        <span class="badge-inactive">INACTIVE</span>
                                    @endif
                                </div>
                                <div class="admin-sub">
                                    <span><i class="fas fa-at"></i> {{ $admin->username ?? '—' }}</span>
                                    <span><i class="fas fa-envelope"></i> {{ $admin->email }}</span>
                                    <span><i class="fas fa-calendar"></i> {{ $admin->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                            
                            @if(!$isCurrentUser)
                                <div class="admin-actions">
                                    <form action="{{ route('admin.toggle-admin-status', $admin->id) }}" method="POST" style="margin:0;">
                                        @csrf
                                        @if($isInactive)
                                            <button type="submit" class="btn btn-success" style="padding:0.4rem 0.75rem; font-size:0.8125rem;" title="Activate admin">
                                                <i class="fas fa-check-circle"></i> Activate
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-warning" style="padding:0.4rem 0.75rem; font-size:0.8125rem;" title="Deactivate admin">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        @endif
                                    </form>
                                    <button type="button" class="btn btn-danger" style="padding:0.4rem 0.75rem; font-size:0.8125rem;" 
                                            onclick="openDeleteModal('{{ $admin->id }}', '{{ addslashes($admin->name) }}')" title="Delete admin">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            @else
                                <div class="admin-actions">
                                    <span style="font-size:0.8125rem; color:var(--text-muted); font-style:italic;">Current user</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; padding:3rem; background:var(--surface); border:1px dashed var(--border-strong); border-radius:var(--radius-xl);">
                    <i class="fas fa-user-slash" style="font-size:2rem; color:var(--text-muted); margin-bottom:1rem;"></i>
                    <p style="margin:0; color:var(--text-secondary);">No admin accounts found.</p>
                </div>
            @endif
        </div>
        
        {{-- Right: Create Admin Form --}}
        <div>
            <div class="create-card">
                <div class="create-card__header">
                    <h5><i class="fas fa-user-plus" style="color:var(--color-accent);"></i> Create New Admin</h5>
                    <p style="margin:0; font-size:0.8125rem; color:var(--text-muted);">New admin gets full access to the exam system.</p>
                </div>
                <div class="create-card__body">
                    <form action="{{ route('admin.store-admin') }}" method="POST">
                        @csrf
                        <div class="form-group field">
                            <label class="field-label">Full Name <span style="color:var(--color-danger)">*</span></label>
                            <input type="text" name="name" class="field-input {{ $errors->has('name') ? 'is-error' : '' }}" value="{{ old('name') }}" placeholder="e.g. Ahmed Hassan" required>
                            @error('name')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="form-group field">
                            <label class="field-label">Username <span style="color:var(--color-danger)">*</span></label>
                            <input type="text" name="username" class="field-input {{ $errors->has('username') ? 'is-error' : '' }}" value="{{ old('username') }}" placeholder="e.g. ahmed.admin" required>
                            @error('username')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="form-group field">
                            <label class="field-label">Email Address <span style="color:var(--color-danger)">*</span></label>
                            <input type="email" name="email" class="field-input {{ $errors->has('email') ? 'is-error' : '' }}" value="{{ old('email') }}" placeholder="e.g. ahmed@school.edu" required>
                            @error('email')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="form-group field">
                            <label class="field-label">Password <span style="color:var(--color-danger)">*</span></label>
                            <input type="password" name="password" class="field-input {{ $errors->has('password') ? 'is-error' : '' }}" placeholder="Minimum 8 characters" required>
                            @error('password')<div class="field-error">{{ $message }}</div>@enderror
                        </div>
                        
                        <div class="form-group field">
                            <label class="field-label">Confirm Password <span style="color:var(--color-danger)">*</span></label>
                            <input type="password" name="password_confirmation" class="field-input" placeholder="Repeat password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <i class="fas fa-user-plus"></i> Create Admin Account
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="notice notice--warning" style="margin-top:1.5rem;">
                <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span><strong>Note:</strong> All admins have full access to create, edit, and delete exams. Deactivated admins cannot log in until re-activated.</span>
            </div>
        </div>
        
    </div>
</div>

<!-- Custom Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal__header">
            <h3 class="modal__title" style="color:var(--color-danger); display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-exclamation-triangle"></i> Delete Admin Account
            </h3>
            <button type="button" class="modal__close" onclick="closeDeleteModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal__body">
            <p style="margin:0 0 1rem; font-size:0.9375rem; color:var(--text);">Are you sure you want to permanently delete the account for <strong id="deleteAdminName"></strong>?</p>
            <p style="margin:0; font-size:0.875rem; color:var(--text-muted);">This action cannot be undone. Exams created by this admin will remain.</p>
        </div>
        <div class="modal__footer" style="display:flex; gap:0.75rem; justify-content:flex-end; padding:1.25rem;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-primary" style="background:var(--color-danger); border-color:var(--color-danger);">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openDeleteModal(adminId, adminName) {
        document.getElementById('deleteAdminName').textContent = adminName;
        document.getElementById('deleteForm').action = "{{ route('admin.delete-admin', ['user' => '__USER_ID__']) }}".replace('__USER_ID__', adminId);
        document.getElementById('deleteModal').classList.add('is-active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('is-active');
    }
</script>
@endsection
