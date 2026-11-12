@extends('layouts.app')
@section('title', 'Reservations')
@section('content')
<div class="page-head"><div><div class="eyebrow">Requests & history</div><h1>{{ auth()->user()->role === 'student' ? 'My reservations' : 'Reservations' }}</h1><p>{{ auth()->user()->role === 'student' ? 'Track decisions and manage your upcoming requests.' : 'Review pending requests and see the reservation record.' }}</p></div>@if(! $history && auth()->user()->role === 'student')<a class="button" href="{{ route('reservations.create') }}">＋ New request</a>@endif</div>
<section class="surface">
    <nav class="tabs"><a class="{{ !$history ? 'active' : '' }}" href="{{ route('reservations.index') }}">Upcoming & pending</a><a class="{{ $history ? 'active' : '' }}" href="{{ route('reservations.index', ['view' => 'history']) }}">History</a></nav>
    @if($reservations->isEmpty())<div class="empty"><strong>{{ $history ? 'No past reservations' : 'No upcoming requests' }}</strong>{{ $history ? 'Completed and closed requests will appear here.' : 'Your active requests will appear here once submitted.' }}</div>
    @else<div class="table-wrap"><table><thead><tr>@if(auth()->user()->role !== 'student')<th>Requested by</th>@endif<th>Resource</th><th>Activity</th><th>Schedule</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @foreach($reservations as $reservation)<tr>
            @if(auth()->user()->role !== 'student')<td><strong>{{ $reservation->user->name }}</strong><br><span class="muted">{{ $reservation->user->organization }}</span></td>@endif
            <td><strong>{{ $reservation->facility?->name ?? 'Equipment only' }}</strong>@if($reservation->equipment->isNotEmpty())<br><span class="muted">{{ $reservation->equipment->map(fn($item) => $item->name.' × '.$item->pivot->quantity)->join(', ') }}</span>@endif</td>
            <td>{{ $reservation->purpose }}<br><span class="muted">{{ $reservation->pax ? $reservation->pax.' attendees' : 'Equipment borrowing' }}</span></td>
            <td>@foreach($reservation->scheduleBlocks() as $schedule)<div>{{ $schedule->starts_at->format('M j, Y') }}<br><span class="muted">{{ $schedule->starts_at->format('g:i A') }} – {{ $schedule->ends_at->format('g:i A') }}</span></div>@endforeach</td>
            <td><span class="status {{ $reservation->status }}">{{ $reservation->status }}</span></td>
            <td><div class="actions"><a class="button neutral small" href="{{ route('reservations.show', $reservation) }}">Details</a>
                @if($reservation->status === 'pending' && auth()->user()->role === 'student')<a class="button light small" href="{{ route('reservations.edit', $reservation) }}">Edit</a><form class="inline-form" method="post" action="{{ route('reservations.cancel', $reservation) }}" data-confirm-message="Cancel this reservation request?" data-confirm-label="Cancel request">@csrf @method('DELETE')<button class="button neutral small" type="submit">Cancel</button></form>@endif
                @if($reservation->status === 'pending' && in_array(auth()->user()->role, ['admin', 'super_admin'], true))
                    @foreach(['approved' => 'Approve', 'rejected' => 'Reject'] as $status => $label)<form class="inline-form" method="post" action="{{ route('reservations.respond', $reservation) }}" data-confirm-message="{{ $label }} this reservation request?" data-confirm-label="{{ $label }}">@csrf @method('PUT')<input type="hidden" name="status" value="{{ $status }}"><button class="button {{ $status === 'rejected' ? 'neutral' : '' }} small" type="submit">{{ $label }}</button></form>@endforeach
                @endif
                @if(in_array(auth()->user()->role, ['admin', 'super_admin'], true))<form class="inline-form" method="post" action="{{ route('reservations.destroy', $reservation) }}" data-confirm-message="Permanently delete this request?" data-confirm-label="Delete request">@csrf @method('DELETE')<button class="button neutral small" type="submit">Delete</button></form>@endif
            </div></td>
        </tr>@endforeach
    </tbody></table></div>{{ $reservations->links() }}@endif
</section>
@endsection