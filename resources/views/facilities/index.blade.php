@extends('layouts.app')
@section('title', 'Facilities')
@section('content')
<div class="page-head"><div><div class="eyebrow">Space directory</div><h1>Facilities</h1><p>Browse capacity and upcoming holds before choosing a time.</p></div><a class="button" href="{{ route('reservations.create') }}">＋ New request</a></div>
<div class="grid-2">
    <section class="surface"><h2 class="section-title">Facility schedule</h2>
        @forelse($facilities as $facility)<div style="border-bottom:1px solid var(--line);padding:13px 0">
            <div class="surface-head" style="margin-bottom:8px"><div><strong>{{ $facility->name }}</strong><div class="muted">{{ $facility->location }} · Up to {{ $facility->capacity }} people</div>@if($facility->staff_in_charge)<div class="muted">Staff in charge: {{ $facility->staff_in_charge }}</div>@endif</div><div class="actions">@if(!$facility->is_available)<span class="status rejected">Unavailable</span>@endif @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))<a class="button neutral small" href="{{ route('facilities.index', ['edit' => $facility->id]) }}">Edit</a>@endif @if(auth()->user()->role === 'super_admin')<form method="post" action="{{ route('facilities.destroy', $facility) }}" data-confirm-message="Delete this facility? Facilities with reservation history cannot be deleted." data-confirm-label="Delete facility">@csrf @method('DELETE')<button class="button neutral small" type="submit">Delete</button></form>@endif</div></div>
            @if($facility->description)<div class="muted" style="margin-bottom:8px">{{ $facility->description }}</div>@endif
            @forelse($facility->reservations as $reservation)@foreach($reservation->scheduleBlocks() as $schedule)<div class="schedule-row"><span>{{ $schedule->starts_at->format('D, M j') }} · {{ $schedule->starts_at->format('g:i A') }}–{{ $schedule->ends_at->format('g:i A') }}</span><span class="status {{ $reservation->status }}">{{ $reservation->status }}</span></div>@endforeach @empty<div class="tagline">No upcoming bookings. Schedule is open.</div>@endforelse
        </div>@empty<div class="empty"><strong>No facilities listed</strong>Campus facility information will appear here.</div>@endforelse
    </section>
    @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))
    <section class="surface"><h2 class="section-title">{{ $editing->exists ? 'Edit facility' : 'Add facility' }}</h2>
        @if($errors->any())<div class="alert errors">Please correct the highlighted fields.</div>@endif
        <form method="post" action="{{ $editing->exists ? route('facilities.update', $editing) : route('facilities.store') }}">@csrf @if($editing->exists) @method('PUT') @endif
            <div class="form-grid"><div class="field full"><label for="name">Facility name</label><input id="name" name="name" value="{{ old('name', $editing->name) }}" required maxlength="120"><div class="error">@error('name'){{ $message }}@enderror</div></div>
            <div class="field full"><label for="location">Building / location</label><input id="location" name="location" value="{{ old('location', $editing->location) }}" required maxlength="160"><div class="error">@error('location'){{ $message }}@enderror</div></div>
            <div class="field full"><label for="staff_in_charge">Staff in charge</label><input id="staff_in_charge" name="staff_in_charge" value="{{ old('staff_in_charge', $editing->staff_in_charge) }}" required maxlength="160"><div class="error">@error('staff_in_charge'){{ $message }}@enderror</div></div>
            <div class="field"><label for="capacity">Capacity</label><input id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $editing->capacity) }}" required><div class="error">@error('capacity'){{ $message }}@enderror</div></div>
            <div class="field"><label for="is_available">Availability</label><select id="is_available" name="is_available"><option value="1" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '1')>Available</option><option value="0" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '0')>Unavailable</option></select></div>
            <div class="field full"><label for="description">Notes</label><textarea id="description" name="description">{{ old('description', $editing->description) }}</textarea></div></div>
            <div class="actions" style="margin-top:16px"><button type="submit">{{ $editing->exists ? 'Save facility' : 'Add facility' }}</button>@if($editing->exists)<a class="button neutral" href="{{ route('facilities.index') }}">Cancel edit</a>@endif</div>
        </form>
    </section>
    @endif
</div>
@endsection