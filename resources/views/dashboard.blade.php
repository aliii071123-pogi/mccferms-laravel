@extends('layouts.app')
@section('title', 'Overview')
@section('content')
<div class="page-head"><div><div class="eyebrow">{{ now()->format('l, F j, Y') }}</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}</h1><p>Plan your next campus activity with MCC resources.</p></div><a class="button" href="{{ route('reservations.create') }}">＋ New request</a></div>
<div class="stats">
    <div class="stat accent"><span>{{ auth()->user()->role === 'student' ? 'My pending requests' : 'Requests to review' }}</span><strong>{{ $pendingCount }}</strong></div>
    <div class="stat"><span>Approved upcoming</span><strong>{{ $activeCount }}</strong></div>
    <div class="stat"><span>Facilities available</span><strong>{{ $facilities->count() }}</strong></div>
</div>
<div class="grid-2">
    <section class="surface">
        <div class="surface-head"><h2 class="section-title">{{ auth()->user()->role === 'student' ? 'My next reservations' : 'Upcoming reservations' }}</h2><a class="button neutral small" href="{{ route('reservations.index') }}">All requests</a></div>
        @if($upcoming->isEmpty())<div class="empty"><strong>No upcoming reservations</strong>Make a request when your organization is ready.</div>
        @else<div class="table-wrap"><table><thead><tr>@if(auth()->user()->role !== 'student')<th>Requester</th>@endif<th>Resource</th><th>Date & time</th><th>Status</th></tr></thead><tbody>
            @foreach($upcoming as $reservation)<tr>@if(auth()->user()->role !== 'student')<td>{{ $reservation->user->name }}</td>@endif<td><a href="{{ route('reservations.show', $reservation) }}"><strong>{{ $reservation->facility?->name ?? $reservation->equipment->first()?->name ?? 'Equipment request' }}</strong></a><br><span class="muted">{{ $reservation->equipment->count() ? $reservation->equipment->count().' equipment item(s)' : ($reservation->facility?->location ?? '') }}</span></td><td>@foreach($reservation->scheduleBlocks() as $schedule){{ $schedule->starts_at->format('M j') }}<br><span class="muted">{{ $schedule->starts_at->format('g:i A') }} – {{ $schedule->ends_at->format('g:i A') }}</span>@endforeach</td><td><span class="status {{ $reservation->status }}">{{ $reservation->status }}</span></td></tr>@endforeach
        </tbody></table></div>@endif
    </section>
    <section class="surface">
        <div class="surface-head"><h2 class="section-title">Facility availability</h2><a class="button neutral small" href="{{ route('facilities.index') }}">Schedules</a></div>
        @forelse($facilities->take(5) as $facility)<div class="schedule-row"><div><strong>{{ $facility->name }}</strong><br><span>{{ $facility->location }}</span></div><span>Up to {{ $facility->capacity }} pax</span></div>@empty<div class="empty"><strong>No facilities listed</strong>Check back with campus staff.</div>@endforelse
        <div class="surface-head" style="margin:22px 0 8px"><h2 class="section-title">Equipment inventory</h2><a class="button neutral small" href="{{ route('equipment.index') }}">View all</a></div>
        @forelse($equipment->take(4) as $item)<div class="schedule-row"><strong>{{ $item->name }}</strong><span>{{ $item->quantity_available }} available</span></div>@empty<div class="muted">No equipment listed.</div>@endforelse
    </section>
</div>
@endsection