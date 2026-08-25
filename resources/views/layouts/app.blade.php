<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Fleetdesk' }} — Fleetdesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <div class="mobile-backdrop" data-nav-backdrop aria-hidden="true"></div>
    <aside id="primary-navigation" class="sidebar" data-sidebar aria-label="Navigasi utama">
        <div class="brand-lockup">
            <span class="brand-mark" aria-hidden="true">F</span>
            <span><strong>FLEET</strong><small>DESK / ADMIN</small></span>
        </div>
        <div class="sidebar-section-label">Workspace</div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->is('dashboard') ? 'is-active' : '' }}"><span class="nav-icon" aria-hidden="true"></span>Ringkasan</a>
            <a href="{{ url('/vehicles') }}" class="nav-item {{ request()->is('vehicles*') ? 'is-active' : '' }}"><span class="nav-icon" aria-hidden="true"></span>Unit kendaraan</a>
            <a href="{{ url('/customers') }}" class="nav-item {{ request()->is('customers*') ? 'is-active' : '' }}"><span class="nav-icon" aria-hidden="true"></span>Pelanggan</a>
            <a href="{{ route('rentals.index') }}" class="nav-item {{ request()->is('rentals*') ? 'is-active' : '' }}"><span class="nav-icon" aria-hidden="true"></span>Rental</a>
        </nav>
        <div class="sidebar-footer">
            <div class="wib-indicator"><span></span><div><b>Sistem aktif</b><small>WIB · Jakarta</small></div></div>
            <div class="admin-card"><span class="avatar">A</span><div><b>Administrator</b><small>Akses internal</small></div></div>
            <form method="POST" action="{{ url('/logout') }}">@csrf<button class="logout-button" type="submit">Keluar <span>→</span></button></form>
        </div>
    </aside>
    <div class="page-frame">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Buka navigasi" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle><span></span><span></span><span></span></button>
            <div class="breadcrumb"><span>FLEETDESK</span><i>/</i><strong>{{ $eyebrow ?? 'ADMIN' }}</strong></div>
            <div class="topbar-meta"><span class="date-stamp">{{ now()->timezone('Asia/Jakarta')->translatedFormat('l, d M Y') }}</span><span class="topbar-divider"></span><span class="top-avatar">A</span></div>
        </header>
        <main id="main-content" class="main-content page-reveal">
            @if(session('success') || session('status'))<div class="flash flash-success" role="status">{{ session('success') ?? session('status') }}<button type="button" aria-label="Tutup" data-dismiss>×</button></div>@endif
            @if($errors->any())<div class="error-summary" role="alert" tabindex="-1"><strong>Periksa kembali data Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</body>
</html>
