<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta-description', config('brand.name') . ' — secure online examination platform.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('brand.short')) — {{ config('brand.name') }}</title>

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
            <a href="{{ Auth::check() && Auth::user()->isStudent() ? route('student.dashboard') : route('home') }}" class="shell-nav__brand">
                <span class="shell-nav__logo" aria-hidden="true">
                    <i class="fas fa-graduation-cap" style="font-size:0.85rem;"></i>
                </span>
                <span>{{ config('brand.short') }}</span>
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

    <script>
    (function() {
        const icons = ['fa-flask', 'fa-book-open', 'fa-leaf'];
        const selects = Array.from(document.querySelectorAll('select.field-input:not([multiple])'));
        let openPicker = null;

        const closePicker = () => {
            if (!openPicker) return;
            openPicker.classList.remove('is-open');
            openPicker.querySelector('.glass-picker__trigger')?.setAttribute('aria-expanded', 'false');
            openPicker = null;
        };

        selects.forEach(select => {
            if (select.dataset.glassPicker) return;
            select.dataset.glassPicker = 'true';

            const wrapper = document.createElement('div');
            wrapper.className = 'glass-picker';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('glass-picker__native');

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'glass-picker__trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.innerHTML = '<i class="glass-picker__icon fas fa-layer-group" aria-hidden="true"></i><span class="glass-picker__label"></span><i class="glass-picker__chevron fas fa-chevron-down" aria-hidden="true"></i>';
            wrapper.appendChild(trigger);

            const menu = document.createElement('div');
            menu.className = 'glass-picker__menu';
            menu.setAttribute('role', 'listbox');
            wrapper.appendChild(menu);

            const sync = () => {
                const selected = select.options[select.selectedIndex];
                trigger.querySelector('.glass-picker__label').textContent = selected?.textContent || '';
                trigger.disabled = select.disabled;
                wrapper.classList.toggle('is-disabled', select.disabled);
                menu.innerHTML = '';

                Array.from(select.options).forEach((option, index) => {
                    if (!option.value && select.options.length > 1) return;
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'glass-picker__option' + (option.selected ? ' is-selected' : '');
                    item.setAttribute('role', 'option');
                    item.innerHTML = `<i class="fas ${icons[index % icons.length]}" aria-hidden="true"></i><span>${option.textContent}</span>${option.selected ? '<i class="fas fa-check" aria-hidden="true"></i>' : ''}`;
                    item.addEventListener('click', () => {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                        closePicker();
                    });
                    menu.appendChild(item);
                });
            };

            trigger.addEventListener('click', () => {
                if (select.disabled) return;
                if (openPicker && openPicker !== wrapper) closePicker();
                const isOpen = wrapper.classList.toggle('is-open');
                openPicker = isOpen ? wrapper : null;
                trigger.setAttribute('aria-expanded', String(isOpen));
                if (isOpen && window.matchMedia('(max-width: 700px)').matches) {
                    const rect = trigger.getBoundingClientRect();
                    menu.style.setProperty('--picker-left', `${Math.max(12, rect.left)}px`);
                    menu.style.setProperty('--picker-top', `${Math.min(window.innerHeight - 16, rect.bottom + 8)}px`);
                    menu.style.setProperty('--picker-width', `${rect.width}px`);
                }
            });
            select.addEventListener('change', sync);
            new MutationObserver(sync).observe(select, { childList: true, subtree: true, attributes: true });
            sync();
        });

        document.addEventListener('click', event => {
            if (openPicker && !openPicker.contains(event.target)) closePicker();
        });
    })();

    // Give navigation and form submissions immediate visual feedback before the browser leaves the page.
    (function() {
        document.addEventListener('click', event => {
            const action = event.target.closest('a.btn, button[type="submit"]');
            if (!action || action.target === '_blank' || action.classList.contains('is-busy')) return;
            action.classList.add('is-busy');
            action.setAttribute('aria-busy', 'true');
        });

        document.addEventListener('submit', event => {
            const form = event.target;
            const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitter) {
                submitter.classList.add('is-busy');
                submitter.setAttribute('aria-busy', 'true');
            }
        });
    })();
    </script>

    @yield('scripts')
</body>
</html>
