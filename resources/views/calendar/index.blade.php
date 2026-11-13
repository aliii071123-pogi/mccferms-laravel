@extends('layouts.app')
@section('title', 'Facility calendar')
@section('content')
<div class="page-head"><div><div class="eyebrow">Facility availability</div><h1>Facility calendar</h1><p>Pending requests and booked facility times. Requester and activity details are private.</p></div></div>
<section class="surface">
    <div class="calendar-controls">
        <a class="button neutral" href="{{ route('calendar.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month">‹</a>
        <h2>{{ $month->format('F Y') }}</h2>
        <a class="button neutral" href="{{ route('calendar.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month">›</a>
    </div>
    <div class="calendar-scroll">
        <div class="calendar-grid" role="grid" aria-label="Facility calendar for {{ $month->format('F Y') }}">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)<div class="calendar-weekday" role="columnheader">{{ $weekday }}</div>@endforeach
            @foreach($days as $day)
                <section class="calendar-day {{ $day->month === $month->month ? '' : 'outside-month' }}" role="gridcell">
                    <strong class="calendar-date">{{ $day->format('j') }}</strong>
                    @foreach($eventsByDate->get($day->toDateString(), collect()) as $event)
                        <div class="calendar-event">
                            <strong>{{ $event['facility'] }}</strong>
                            <span>{{ $event['starts_at']->format('g:i A') }}–{{ $event['ends_at']->format('g:i A') }}</span>
                            <small class="status {{ $event['status'] }}">{{ $event['status'] === 'recommended' ? 'Recommended' : ($event['status'] === 'pending' ? 'Pending' : 'Booked') }}</small>
                        </div>
                    @endforeach
                </section>
            @endforeach
        </div>
    </div>
    @if($eventsByDate->isEmpty())<p class="muted" style="margin:16px 0 0">No pending requests or bookings this month.</p>@endif
</section>
@endsection
