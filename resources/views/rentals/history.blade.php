@extends('layouts.app')
@section('content')
<div class="page-heading"><div><p class="eyebrow">RENTAL / AUDIT TRAIL</p><h1>Lifecycle history</h1><p class="lede">Catatan perubahan status rental #{{ $rental ?? '—' }}.</p></div><a class="button button-secondary" href="{{ route('rentals.index') }}">← Semua rental</a></div>
<section class="table-card"><div class="timeline timeline-large">@forelse(($history ?? []) as $event)<div><span class="timeline-dot"></span><strong>{{ ucfirst(str_replace('_', ' ', $event->event_type ?? 'event')) }}</strong><small>{{ $event->occurred_at ?? '—' }} · status {{ $event->state ?? '—' }} @if($event->reason) · {{ $event->reason }} @endif</small></div>@empty<p class="empty-state">Belum ada catatan lifecycle.</p>@endforelse</div></section>
@endsection
