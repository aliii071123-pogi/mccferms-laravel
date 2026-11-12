@extends('layouts.app')
@section('title', $reservation->exists ? 'Edit request' : 'New request')
@section('content')
@php
    $initialDays = old('days', $scheduleDays);
    $initialDays = is_array($initialDays) ? array_values($initialDays) : [];
    $dayCount = max(1, min(7, (int) old('duration_days', count($initialDays))));
@endphp
<div class="page-head"><div><div class="eyebrow">Reservation request</div><h1>{{ $reservation->exists ? 'Update your request' : 'Book an MCC resource' }}</h1><p>Campus use is available daily between 8:00 AM and 8:00 PM.</p></div><a class="button neutral" href="{{ route('reservations.index') }}">Back to requests</a></div>
<section class="surface" style="max-width:920px">
    @if($errors->any())<div class="alert errors" role="alert">Some details need attention. Correct the highlighted fields below.</div>@endif
    <form method="post" action="{{ $reservation->exists ? route('reservations.update', $reservation) : route('reservations.store') }}">
        @csrf @if($reservation->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field full"><label for="facility_id">Facility <span class="muted">(optional if borrowing equipment only)</span></label><select id="facility_id" name="facility_id" class="{{ $errors->has('facility_id') ? 'invalid' : '' }}"><option value="">No facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected(old('facility_id', $reservation->facility_id) == $facility->id)>{{ $facility->name }} · {{ $facility->location }} · {{ $facility->capacity }} pax</option>@endforeach</select><div class="error">@error('facility_id'){{ $message }}@enderror</div></div>
            <div class="field full"><label>Equipment <span class="muted">(optional if reserving a facility only)</span></label>
                <div class="equipment-options">@foreach($equipment as $item)<div class="equipment-choice"><label for="equipment_{{ $item->id }}">{{ $item->name }}<br><span class="muted">{{ $item->quantity_available }} in inventory</span></label><input id="equipment_{{ $item->id }}" name="equipment[{{ $item->id }}]" type="number" min="0" max="{{ $item->quantity_available }}" value="{{ old('equipment.'.$item->id, $reservation->equipment->firstWhere('id', $item->id)?->pivot->quantity ?? '') }}" class="{{ $errors->has('equipment.'.$item->id) ? 'invalid' : '' }}" placeholder="Qty"><div class="error" style="grid-column:1/-1">@error('equipment.'.$item->id){{ $message }}@enderror</div></div>@endforeach</div>
                @if($equipment->isEmpty())<div class="muted">No equipment is currently listed.</div>@endif
            </div>
            <div class="field"><label for="duration_days">Number of event days</label><input id="duration_days" name="duration_days" type="number" min="1" max="7" value="{{ old('duration_days', $dayCount) }}" required><div class="hint">Choose 1 to 7 days. Each day has its own date and time.</div><div class="error">@error('duration_days'){{ $message }}@enderror</div></div>
            <div class="field full"><div class="hint">Choose the date for the first day, then a date and time range for each following event day.</div><div id="schedule-days" class="schedule-days">
                @for($index = 0; $index < $dayCount; $index++)
                    @php $day = $initialDays[$index] ?? []; @endphp
                    <section class="schedule-day">
                        <h3>Day {{ $index + 1 }}</h3>
                        <div class="form-grid">
                            <div class="field"><label for="day-{{ $index }}-date">Date</label><input id="day-{{ $index }}-date" name="days[{{ $index }}][date]" type="date" min="{{ now()->toDateString() }}" value="{{ $day['date'] ?? '' }}" required><div class="error">@error("days.$index.date"){{ $message }}@enderror</div></div>
                            <div class="field"></div>
                            <div class="field"><label for="day-{{ $index }}-start">Start time</label><input id="day-{{ $index }}-start" name="days[{{ $index }}][start_time]" type="time" min="08:00" max="20:00" value="{{ $day['start_time'] ?? '' }}" required><div class="error">@error("days.$index.start_time"){{ $message }}@enderror</div></div>
                            <div class="field"><label for="day-{{ $index }}-end">End time</label><input id="day-{{ $index }}-end" name="days[{{ $index }}][end_time]" type="time" min="08:00" max="20:00" value="{{ $day['end_time'] ?? '' }}" required><div class="error">@error("days.$index.end_time"){{ $message }}@enderror</div></div>
                        </div>
                    </section>
                @endfor
            </div><div class="error">@error('days'){{ $message }}@enderror</div></div>
            <div class="field"><label for="pax">Estimated attendees</label><input id="pax" name="pax" type="number" min="1" value="{{ old('pax', $reservation->pax) }}" class="{{ $errors->has('pax') ? 'invalid' : '' }}" required><div class="error">@error('pax'){{ $message }}@enderror</div></div>
            <div class="field full"><label for="purpose">Purpose</label><input id="purpose" name="purpose" maxlength="160" value="{{ old('purpose', $reservation->purpose) }}" class="{{ $errors->has('purpose') ? 'invalid' : '' }}" placeholder="e.g. Leadership seminar" required><div class="error">@error('purpose'){{ $message }}@enderror</div></div>
            <div class="field full"><label for="description">Activity brief description</label><textarea id="description" name="description" maxlength="5000" class="{{ $errors->has('description') ? 'invalid' : '' }}" placeholder="Share the activity details and setup needs." required>{{ old('description', $reservation->description) }}</textarea><div class="error">@error('description'){{ $message }}@enderror</div></div>
        </div>
        <div class="actions" style="margin-top:20px"><button type="submit">{{ $reservation->exists ? 'Save changes' : 'Send request' }}</button><a class="button neutral" href="{{ route('reservations.index') }}">Cancel</a></div>
    </form>
</section>
@if($errors->has('facility_conflict'))
    <div class="modal-backdrop" id="facility-conflict-modal" role="presentation">
        <section class="modal" role="dialog" aria-modal="true" aria-labelledby="facility-conflict-title">
            <h2 id="facility-conflict-title">Facility time unavailable</h2>
            <p>{{ $errors->first('facility_conflict') }}</p>
            <div class="modal-actions"><button type="button" onclick="document.getElementById('facility-conflict-modal').remove()">Okay</button></div>
        </section>
    </div>
@endif
@endsection
@push('scripts')
<script>
    const durationInput = document.getElementById('duration_days');
    const scheduleDays = document.getElementById('schedule-days');
    const minDate = @json(now()->toDateString());

    function addScheduleDay(index) {
        const section = document.createElement('section');
        section.className = 'schedule-day';
        section.innerHTML = `<h3>Day ${index + 1}</h3><div class="form-grid">
            <div class="field"><label for="day-${index}-date">Date</label><input id="day-${index}-date" name="days[${index}][date]" type="date" min="${minDate}" required></div>
            <div class="field"></div>
            <div class="field"><label for="day-${index}-start">Start time</label><input id="day-${index}-start" name="days[${index}][start_time]" type="time" min="08:00" max="20:00" required></div>
            <div class="field"><label for="day-${index}-end">End time</label><input id="day-${index}-end" name="days[${index}][end_time]" type="time" min="08:00" max="20:00" required></div>
        </div>`;
        scheduleDays.append(section);
    }

    durationInput.addEventListener('change', () => {
        const count = Math.max(1, Math.min(7, Number.parseInt(durationInput.value, 10) || 1));
        durationInput.value = count;
        while (scheduleDays.children.length < count) {
            addScheduleDay(scheduleDays.children.length);
        }
        while (scheduleDays.children.length > count) {
            scheduleDays.lastElementChild.remove();
        }
        Array.from(scheduleDays.children).forEach((section, index) => {
            section.querySelector('h3').textContent = `Day ${index + 1}`;
        });
    });
</script>
@endpush