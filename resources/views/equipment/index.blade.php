@extends('layouts.app')
@section('title', 'Equipment')
@section('content')
<div class="page-head"><div><div class="eyebrow">Borrowable inventory</div><h1>Equipment</h1><p>Check stock and scheduled borrowing windows before submitting a request.</p></div><a class="button" href="{{ route('reservations.create') }}">＋ New request</a></div>
<div class="grid-2">
    <section class="surface"><h2 class="section-title">Equipment availability</h2>
        <div class="table-wrap"><table><thead><tr><th>Item</th><th>Stock</th><th>Upcoming schedule</th><th></th></tr></thead><tbody>
            @forelse($equipment as $item)<tr><td><strong>{{ $item->name }}</strong><br><span class="muted">{{ $item->category }}</span>@if($item->description)<br><span class="muted">{{ $item->description }}</span>@endif</td><td>{{ $item->quantity_available }}<br><span class="muted">total units</span></td><td>@forelse($item->reservations->take(3) as $reservation)<div class="schedule-row"><span>{{ $reservation->starts_at->format('M j, g:i A') }}</span><strong>{{ $reservation->pivot->quantity }} units</strong></div>@empty<span class="muted">No upcoming holds</span>@endforelse</td><td>@if(!$item->is_available)<span class="status rejected">Unavailable</span>@endif @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))<a class="button neutral small" href="{{ route('equipment.index', ['edit' => $item->id]) }}">Edit</a>@endif</td></tr>@empty<tr><td colspan="4" class="empty"><strong>No equipment listed</strong>Inventory will appear here when added.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))
    <section class="surface"><h2 class="section-title">{{ $editing->exists ? 'Edit equipment' : 'Add equipment' }}</h2>
        @if($errors->any())<div class="alert errors">Please correct the highlighted fields.</div>@endif
        <form method="post" action="{{ $editing->exists ? route('equipment.update', $editing) : route('equipment.store') }}">@csrf @if($editing->exists) @method('PUT') @endif
            <div class="form-grid"><div class="field full"><label for="name">Equipment name</label><input id="name" name="name" value="{{ old('name', $editing->name) }}" required maxlength="120"><div class="error">@error('name'){{ $message }}@enderror</div></div>
            <div class="field"><label for="category">Category</label><input id="category" name="category" value="{{ old('category', $editing->category) }}" required maxlength="80"><div class="error">@error('category'){{ $message }}@enderror</div></div>
            <div class="field"><label for="quantity_available">Quantity in inventory</label><input id="quantity_available" name="quantity_available" type="number" min="0" value="{{ old('quantity_available', $editing->quantity_available) }}" required><div class="error">@error('quantity_available'){{ $message }}@enderror</div></div>
            <div class="field"><label for="availability">Availability</label><select id="availability" name="is_available"><option value="1" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '1')>Available</option><option value="0" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '0')>Unavailable</option></select></div>
            <div class="field full"><label for="description">Notes</label><textarea id="description" name="description">{{ old('description', $editing->description) }}</textarea></div></div>
            <div class="actions" style="margin-top:16px"><button type="submit">{{ $editing->exists ? 'Save equipment' : 'Add equipment' }}</button>@if($editing->exists)<a class="button neutral" href="{{ route('equipment.index') }}">Cancel edit</a>@endif</div>
        </form>
    </section>
    @endif
</div>
@endsection