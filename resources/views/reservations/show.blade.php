@extends('layouts.app')
@section('title', 'Request details')
@section('content')
<div class="page-head"><div><div class="eyebrow">Request #{{ str_pad((string) $reservation->id, 5, '0', STR_PAD_LEFT) }}</div><h1>{{ $reservation->purpose }}</h1><p>Submitted {{ $reservation->created_at->format('M j, Y') }} · <span class="status {{ $reservation->status }}">{{ $reservation->status }}</span></p></div><a class="button neutral" href="{{ route('reservations.index') }}">Back to reservations</a></div>
<section class="surface" style="max-width:900px"><div class="form-grid">
    <div><div class="eyebrow">Facility</div><strong>{{ $reservation->facility?->name ?? 'No facility requested' }}</strong><div class="muted">{{ $reservation->facility?->location }}</div></div>
    <div><div class="eyebrow">Requested by</div><strong>{{ $reservation->user->name }}</strong><div class="muted">{{ $reservation->user->organization }} · {{ $reservation->user->position }}</div></div>
    <div><div class="eyebrow">Dates & times</div>@foreach($reservation->scheduleBlocks() as $schedule)<div><strong>{{ $schedule->starts_at->format('l, F j, Y') }}</strong><div class="muted">{{ $schedule->starts_at->format('g:i A') }} – {{ $schedule->ends_at->format('g:i A') }}</div></div>@endforeach</div>
    <div><div class="eyebrow">Estimated attendees</div><strong>{{ $reservation->pax ?: 'Not applicable' }}</strong></div>
    <div class="field full"><div class="eyebrow">Activity description</div><p>{{ $reservation->description }}</p></div>
    <div class="field full"><div class="eyebrow">Equipment requested</div>@forelse($reservation->equipment as $item)<div class="schedule-row"><strong>{{ $item->name }}</strong><span>Quantity {{ $item->pivot->quantity }}</span></div>@empty<p class="muted">No equipment requested.</p>@endforelse
        @if($reservation->return_due_at)<p class="muted">Return by {{ $reservation->return_due_at->format('M j, Y g:i A') }} (within 2 days of the reservation).</p>@endif
    </div>
</div>
@if($reservation->reviewer)<p class="muted" style="margin-top:20px">{{ ucfirst($reservation->status) }} by {{ $reservation->reviewer->name }} on {{ $reservation->reviewed_at?->format('M j, Y g:i A') }}.</p>@endif
<div class="actions" style="margin-top:18px">
    @if($reservation->status === 'pending' && auth()->user()->role === 'student')<a class="button light" href="{{ route('reservations.edit', $reservation) }}">Edit request</a><form method="post" action="{{ route('reservations.cancel', $reservation) }}" data-confirm-message="Cancel this reservation request?" data-confirm-label="Cancel request">@csrf @method('DELETE')<button class="button neutral" type="submit">Cancel request</button></form>@endif
    @if($reservation->status === 'pending' && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
        @foreach(['approved' => 'Approve', 'rejected' => 'Reject'] as $status => $label)<form method="post" action="{{ route('reservations.respond', $reservation) }}" data-confirm-message="{{ $label }} this reservation request?" data-confirm-label="{{ $label }}">@csrf @method('PUT')<input type="hidden" name="status" value="{{ $status }}"><button class="{{ $status === 'rejected' ? 'button neutral' : '' }}" type="submit">{{ $label }}</button></form>@endforeach
    @endif
</div></section>
@if(session('request_submitted'))
    <div class="modal-backdrop" role="presentation">
        <section class="modal request-sent-modal" role="dialog" aria-modal="true" aria-labelledby="submitted-title">
            <div class="request-sent-icon" aria-hidden="true">✓</div>
            <h2 id="submitted-title">Request sent</h2>
            <p>Your reservation request has been sent. Printed letters still need to be signed before your activity.</p>
            <div class="modal-actions"><a class="button" href="{{ route('reservations.index') }}">Done</a></div>
        </section>
    </div>
@endif
@endsection