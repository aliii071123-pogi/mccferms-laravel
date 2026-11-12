@extends('layouts.app')
@section('title', 'Equipment')
@section('content')
<div class="page-head"><div><div class="eyebrow">Borrowable inventory</div><h1>Equipment</h1><p>Check stock and scheduled borrowing windows before submitting a request.</p></div><a class="button" href="{{ route('reservations.create') }}">＋ New request</a></div>
<div class="grid-2">
    <section class="surface"><h2 class="section-title">Equipment availability</h2>
        <div class="equipment-filters">
            <div class="field equipment-search"><label for="equipment-search">Search equipment</label><input id="equipment-search" type="search" placeholder="Search name, notes, or staff…" autocomplete="off"></div>
            <div class="field equipment-category"><label for="equipment-category">Category</label><select id="equipment-category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach<option value="__uncategorized__">Uncategorized</option></select></div>
        </div>
        <p id="equipment-result-count" class="muted equipment-result-count" role="status" aria-live="polite"></p>
        <div class="table-wrap"><table><thead><tr><th>Item</th><th>Stock</th><th>Upcoming schedule</th><th></th></tr></thead><tbody>
            @forelse($equipment as $item)<tr data-equipment-row data-category="{{ $item->category ?: '__uncategorized__' }}"><td><strong>{{ $item->name }}</strong><br><span class="muted">{{ $item->category ?: 'Uncategorized' }}</span>@if($item->staff_in_charge)<br><span class="muted">Staff in charge: {{ $item->staff_in_charge }}</span>@endif @if($item->description)<br><span class="muted">{{ $item->description }}</span>@endif</td><td>{{ $item->quantity_available }}<br><span class="muted">total units</span></td><td>@forelse($item->reservations->take(3) as $reservation)@foreach($reservation->scheduleBlocks() as $schedule)<div class="schedule-row"><span>{{ $schedule->starts_at->format('M j, g:i A') }}–{{ $schedule->ends_at->format('g:i A') }}</span><strong>{{ $reservation->pivot->quantity }} units</strong></div>@endforeach @empty<span class="muted">No upcoming holds</span>@endforelse</td><td>@if(!$item->is_available)<span class="status rejected">Unavailable</span>@endif @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))<a class="button neutral small" href="{{ route('equipment.index', ['edit' => $item->id]) }}">Edit</a>@endif</td></tr>@empty<tr><td colspan="4" class="empty" id="equipment-empty"><strong>No equipment listed</strong>Inventory will appear here when added.</td></tr>@endforelse
            @if($equipment->isNotEmpty())<tr id="equipment-no-matches" hidden><td colspan="4" class="empty"><strong>No matching equipment</strong>Try a different search or category.</td></tr>@endif
        </tbody></table></div>
    </section>
    @if(in_array(auth()->user()->role, ['staff','admin','super_admin'], true))
    <section class="surface"><h2 class="section-title">{{ $editing->exists ? 'Edit equipment' : 'Add equipment' }}</h2>
        @if($errors->any())<div class="alert errors">Please correct the highlighted fields.</div>@endif
        <form method="post" action="{{ $editing->exists ? route('equipment.update', $editing) : route('equipment.store') }}">@csrf @if($editing->exists) @method('PUT') @endif
            <div class="form-grid"><div class="field full"><label for="name">Equipment name</label><input id="name" name="name" value="{{ old('name', $editing->name) }}" required maxlength="120"><div class="error">@error('name'){{ $message }}@enderror</div></div>
            <div class="field"><label for="category">Category (optional)</label><input id="category" name="category" value="{{ old('category', $editing->category) }}" maxlength="80"><div class="error">@error('category'){{ $message }}@enderror</div></div>
            <div class="field"><label for="staff_in_charge">Staff in charge</label><input id="staff_in_charge" name="staff_in_charge" value="{{ old('staff_in_charge', $editing->staff_in_charge) }}" required maxlength="160"><div class="error">@error('staff_in_charge'){{ $message }}@enderror</div></div>
            <div class="field"><label for="quantity_available">Quantity in inventory</label><input id="quantity_available" name="quantity_available" type="number" min="0" value="{{ old('quantity_available', $editing->quantity_available) }}" required><div class="error">@error('quantity_available'){{ $message }}@enderror</div></div>
            <div class="field"><label for="availability">Availability</label><select id="availability" name="is_available"><option value="1" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '1')>Available</option><option value="0" @selected((string) old('is_available', $editing->exists ? (int) $editing->is_available : 1) === '0')>Unavailable</option></select></div>
            <div class="field full"><label for="description">Notes</label><textarea id="description" name="description">{{ old('description', $editing->description) }}</textarea></div></div>
            <div class="actions" style="margin-top:16px"><button type="submit">{{ $editing->exists ? 'Save equipment' : 'Add equipment' }}</button>@if($editing->exists)<a class="button neutral" href="{{ route('equipment.index') }}">Cancel edit</a>@endif</div>
        </form>
    </section>
    @endif
</div>
@endsection
@push('scripts')
<script>
    const equipmentSearch = document.getElementById('equipment-search');
    const equipmentCategory = document.getElementById('equipment-category');
    const equipmentRows = Array.from(document.querySelectorAll('[data-equipment-row]'));
    const equipmentCount = document.getElementById('equipment-result-count');
    const equipmentNoMatches = document.getElementById('equipment-no-matches');

    function filterEquipment() {
        const searchTerm = equipmentSearch.value.trim().toLocaleLowerCase();
        const selectedCategory = equipmentCategory.value;
        let visibleCount = 0;

        equipmentRows.forEach(row => {
            const matchesSearch = row.textContent.toLocaleLowerCase().includes(searchTerm);
            const matchesCategory = !selectedCategory || row.dataset.category === selectedCategory;
            const isVisible = matchesSearch && matchesCategory;
            row.hidden = !isVisible;
            visibleCount += Number(isVisible);
        });

        equipmentCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'item' : 'items'}`;
        if (equipmentNoMatches) {
            equipmentNoMatches.hidden = visibleCount > 0;
        }
    }

    equipmentSearch.addEventListener('input', filterEquipment);
    equipmentCategory.addEventListener('change', filterEquipment);
    filterEquipment();
</script>
@endpush