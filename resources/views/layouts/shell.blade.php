<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description', 'SITC Online Exam System — secure, modern online examination platform.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SITC Exam') — SITC Exam System</title>

    {{-- Preconnect for Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Inter (Latin) + Noto Sans Bengali --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @vite(['resources/css/app.css'])
    @yield('head')
</head>
<body class="bg-page page-fade-in">

    {{-- ── Navigation ── --}}
    <nav class="shell-nav" role="navigation" aria-label="Main navigation">
        <div class="shell-nav__inner">
            {{-- Brand --}}
            <a href="/" class="shell-nav__brand">
                <span class="shell-nav__logo" aria-hidden="true">
                    <i class="fas fa-graduation-cap" style="font-size:0.85rem;"></i>
                </span>
                <span>SITC Exam</span>
            </a>

            {{-- Nav centre slot (page title, breadcrumb, etc.) --}}
            <div style="flex:1; min-width:0;">
                @yield('nav-center')
            </div>

            {{-- Nav actions slot --}}
            <div class="shell-nav__actions">
                @yield('nav-actions')

                {{-- Dark mode toggle --}}
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark/light mode">
                    {{-- Sun (shown in dark mode) --}}
                    <svg class="icon-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                    {{-- Moon (shown in light mode) --}}
                    <svg class="icon-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </div>
        </div>
    </nav>

    {{-- ── Flash Messages ── --}}
    @if(session('success') || session('error') || $errors->any())
    <div style="max-width:1120px; margin:0.75rem auto; padding:0 1.25rem;" id="flashMessages">
        @if(session('success'))
        <div class="notice notice--success" role="alert">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>
            <span>{{ session('success') }}</span>
            <button onclick="this.closest('.notice').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; padding:0; line-height:1; opacity:0.6; font-size:1rem;">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="notice notice--danger" role="alert">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ session('error') }}</span>
            <button onclick="this.closest('.notice').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; padding:0; line-height:1; opacity:0.6; font-size:1rem;">&times;</button>
        </div>
        @endif
        @if($errors->any())
        <div class="notice notice--danger" role="alert">
            <svg class="notice__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ $errors->first() }}</span>
            <button onclick="this.closest('.notice').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; color:inherit; padding:0; line-height:1; opacity:0.6; font-size:1rem;">&times;</button>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Main Content ── --}}
    <main id="main-content">
        @yield('content')
    </main>

    {{-- ── Theme Toggle Script ── --}}
    <script>
    (function() {
        const html = document.documentElement;
        const stored = localStorage.getItem('sitc-theme');
        if (stored) html.setAttribute('data-theme', stored);

        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', function() {
                const current = html.getAttribute('data-theme');
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                let next;
                if (current === 'dark') next = 'light';
                else if (current === 'light') next = 'dark';
                else next = systemDark ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('sitc-theme', next);
            });
        }
    })();
    </script>

    @yield('scripts')
</body>
</html>
