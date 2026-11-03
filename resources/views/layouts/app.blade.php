<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Pantau ketersediaan, waktu respons, dan riwayat gangguan website dalam satu tempat.">
    <meta name="theme-color" content="#F4F5F0">
    <title>
        @yield('title', 'Pantau Website') — Website Monitoring</title>
    @vite('resources/js/app.js')
</head>

<body>
    <a class="skip-link" href="#main">Langsung ke konten</a>
    <div class="shell">
        <header class="site-header">
            <a class="wordmark" href="{{ route('websites.index') }}" aria-label="Website Monitoring, beranda">website
                monitoring<span class="logo-dot">.</span>
            </a>
            <nav aria-label="Navigasi utama">
                <a class="nav-link {{ request()->routeIs('websites.index', 'websites.show') ? 'active' : '' }}"
                    href="{{ route('websites.index') }}">Monitoring</a>
                <a class="nav-link {{ request()->routeIs('websites.create') ? 'active' : '' }}"
                    href="{{ route('websites.create') }}">Tambah Website <span aria-hidden="true">↗</span>
                </a>
            </nav>
        </header>
        <main id="main">
            @if (session('notice'))
                <div class="notice" role="status">{{ session('notice') }}</div>
            @endif
            @yield('content')
        </main>
        <footer class="site-footer">
            <p>Website Monitoring — Pemantauan Ketersediaan Website.</p>
            <p class="footer-note">Waktu Indonesia Barat <span aria-hidden="true">/</span> Demo lokal</p>
        </footer>
    </div>
</body>

</html>
