@extends('layouts.app')
@section('content')
<div class="page-heading"><div><p class="eyebrow">OPERATIONS / TODAY</p><h1>Ringkasan <span class="heading-mark">/</span></h1><p class="lede">Satu pandangan tenang untuk armada dan pemesanan hari ini.</p></div><a class="button button-primary" href="{{ route('rentals.index') }}#rental-form">+ Buat rental</a></div>
<section class="metric-grid" aria-label="Metrik inti dashboard">
    @foreach([['total_active_vehicles','Unit aktif','Armada yang siap dioperasikan'],['currently_rented','Sedang disewa','Menutup hari ini'],['available_today','Tersedia hari ini','Siap menerima pesanan'],['upcoming_bookings','Booking mendatang','Pemesanan terjadwal'],['today_rental_total','Nilai rental hari ini','Total tersimpan']] as [$key,$label,$note])
    <article class="metric-card {{ $key === 'today_rental_total' ? 'metric-card-accent' : '' }}"><div class="metric-icon" aria-hidden="true"></div><p>{{ $label }}</p><strong>{{ isset($$key) && $$key !== null ? ($key === 'today_rental_total' ? '$'.number_format($$key / 100, 2) : number_format($$key)) : '—' }}</strong><small>{{ isset($$key) && $$key !== null ? $note : 'Belum didefinisikan' }}</small></article>
    @endforeach
</section>
<section class="dashboard-lower"><div class="insight-card"><p class="eyebrow">CONTROL ROOM / 01</p><h2>Operasi tetap bergerak.</h2><p>Gunakan preview tanggal pada form rental untuk melihat unit yang tersedia sebelum menyimpan pemesanan.</p><a class="text-button" href="{{ route('rentals.index') }}">Buka daftar rental ↗</a></div><div class="today-card"><span class="today-kicker">WIB / {{ now()->timezone('Asia/Jakarta')->format('d M Y') }}</span><strong>Kelola<br><em>lebih tajam.</em></strong><span class="today-rule"></span><small>FLEETDESK ADMIN CONSOLE</small></div></section>
@endsection
