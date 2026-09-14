<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ config('brand.short') }}</title>
    <!-- Fonts: Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Tailwind v4 via app.css -->
    @vite(['resources/css/app.css'])

    <!-- Dark Mode Init Script -->
    <script>
        // Check local storage or system preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body class="page-fade-in" style="display:flex; flex-direction:column; min-height:100vh;">

    <!-- Optional minimal nav -->
    <nav class="shell-nav">
        <div class="shell-nav__inner">
            <a href="{{ url('/') }}" class="shell-nav__brand">
                <div class="shell-nav__logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                {{ config('brand.short') }}
            </a>
            <div class="shell-nav__actions">
                <button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
                    <i class="fas fa-sun icon-sun"></i>
                    <i class="fas fa-moon icon-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main style="flex:1; display:flex; align-items:center; justify-content:center; padding:2rem 1.25rem;">
        
        <div class="card card--raised" style="width: 100%; max-width: 440px;">
            <div class="card__header" style="text-align: center; padding-bottom: 0.5rem;">
                <h1 style="font-size: 1.5rem; font-weight: 800; margin: 0 0 0.25rem; color: var(--text);">Admin Portal</h1>
                <p style="margin: 0; font-size: 0.9375rem; color: var(--text-muted);">Sign in to manage exams and students</p>
            </div>
            
            <div class="card__body">
                @if(session('success'))
                    <div class="notice notice--success" style="margin-bottom: 1.5rem;">
                        <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="notice notice--danger" style="margin-bottom: 1.5rem;">
                        <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="notice notice--danger" style="margin-bottom: 1.5rem;">
                        <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.authenticate') }}" method="POST" style="display:flex; flex-direction:column; gap:1.25rem;">
                    @csrf
                    
                    <div class="field">
                        <label for="username" class="field-label">Username</label>
                        <input type="text" id="username" name="username" class="field-input" value="{{ old('username') }}" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password" class="field-label">Password</label>
                        <input type="password" id="password" name="password" class="field-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 0.5rem;">
                        Sign In <i class="fas fa-arrow-right" style="margin-left: 0.25rem; font-size: 0.85em;"></i>
                    </button>
                </form>
            </div>
            
            <div class="card__footer" style="text-align: center; background: var(--surface-alt);">
                <a href="{{ url('/') }}" style="color: var(--color-accent); text-decoration: none; font-size: 0.875rem; font-weight: 500;">
                    &larr; Back to Portal
                </a>
            </div>
        </div>

    </main>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('theme', target);
        }
    </script>
</body>
</html>
