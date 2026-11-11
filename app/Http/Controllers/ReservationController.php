<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Facility;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    private const BUSY_STATUSES = ['pending', 'approved'];

    public function index(Request $request)
    {
        $user = $request->user();
        $history = $request->query('view') === 'history';
        $query = Reservation::with(['user', 'facility', 'equipment']);

        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }

        $query->when($history,
            fn ($query) => $query->where(fn ($query) => $query->where('ends_at', '<', now())->orWhereIn('status', ['rejected', 'cancelled'])),
            fn ($query) => $query->where(fn ($query) => $query->where('ends_at', '>=', now())->whereIn('status', self::BUSY_STATUSES)),
        );

        $reservations = $query->orderByDesc('starts_at')->paginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($reservations);
        }

        return view('reservations.index', compact('reservations', 'history'));
    }

    public function create()
    {
        return view('reservations.form', [
            'reservation' => new Reservation,
            'facilities' => Facility::where('is_available', true)->orderBy('name')->get(),
            'equipment' => Equipment::where('is_available', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        [$data, $equipment] = $this->validatedRequest($request);

        $reservation = DB::transaction(function () use ($request, $data, $equipment) {
            $this->assertAvailable($data, $equipment);
            $reservation = Reservation::create([
                ...$data,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'return_due_at' => $equipment ? Carbon::parse($data['ends_at'])->addDays(2) : null,
            ]);
            $reservation->equipment()->sync($equipment);

            return $reservation;
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->load(['facility', 'equipment']), 'message' => 'Request sent. Printed letters still need to be signed.'], 201);
        }

        return to_route('reservations.show', $reservation)
            ->with('success', 'Request sent. Remember to have printed letters signed before your activity.')
            ->with('request_submitted', true);
    }

    public function show(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->load(['user', 'facility', 'equipment', 'reviewer'])]);
        }

        return view('reservations.show', compact('reservation'));
    }

    public function edit(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);
        abort_unless($reservation->status === 'pending' && $request->user()->role === 'student', 403);

        return view('reservations.form', [
            'reservation' => $reservation->load('equipment'),
            'facilities' => Facility::where('is_available', true)->orderBy('name')->get(),
            'equipment' => Equipment::where('is_available', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);
        abort_unless($reservation->status === 'pending' && $request->user()->role === 'student', 403);
        [$data, $equipment] = $this->validatedRequest($request);

        DB::transaction(function () use ($reservation, $data, $equipment) {
            $this->assertAvailable($data, $equipment, $reservation);
            $reservation->update([
                ...$data,
                'return_due_at' => $equipment ? Carbon::parse($data['ends_at'])->addDays(2) : null,
            ]);
            $reservation->equipment()->sync($equipment);
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->fresh()->load(['facility', 'equipment'])]);
        }

        return to_route('reservations.show', $reservation)->with('success', 'Your request was updated.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);
        abort_unless($request->user()->role === 'student' && $reservation->status === 'pending', 403);
        $reservation->update(['status' => 'cancelled']);

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->fresh()]);
        }

        return to_route('reservations.index')->with('success', 'Your request was cancelled.');
    }

    public function respond(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        abort_unless($reservation->status === 'pending', 409);
        $reservation->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->fresh()]);
        }

        return to_route('reservations.index')->with('success', 'Request '.($data['status'] === 'approved' ? 'approved' : 'rejected').'.');
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        $reservation->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Reservation request deleted.']);
        }

        return to_route('reservations.index')->with('success', 'Reservation request deleted.');
    }

    private function validatedRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'pax' => ['required', 'integer', 'min:1', 'max:10000'],
            'purpose' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $hasEquipment = collect($request->input('equipment', []))->contains(fn ($quantity) => (int) $quantity > 0);
            if (! $request->filled('facility_id') && ! $hasEquipment) {
                $validator->errors()->add('facility_id', 'Choose a facility, equipment, or both.');
            }
            foreach (['start_time', 'end_time'] as $field) {
                $time = $request->input($field);
                if ($time && ($time < '08:00' || $time > '20:00')) {
                    $validator->errors()->add($field, 'Reservations are available between 8:00 AM and 8:00 PM.');
                }
            }
            if ($request->filled('date') && $request->filled('start_time') && $request->input('date') === now()->toDateString() && $request->input('start_time') <= now()->format('H:i')) {
                $validator->errors()->add('start_time', 'Choose a start time later than now.');
            }
        });

        $validated = $validator->validate();
        $equipment = collect($validated['equipment'] ?? [])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => ['quantity' => (int) $quantity]])
            ->all();
        ksort($equipment);
        $validated['starts_at'] = $validated['date'].' '.$validated['start_time'];
        $validated['ends_at'] = $validated['date'].' '.$validated['end_time'];
        unset($validated['date'], $validated['start_time'], $validated['end_time'], $validated['equipment']);

        return [$validated, $equipment];
    }

    private function assertAvailable(array $data, array $equipment, ?Reservation $ignore = null): void
    {
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        if (! empty($data['facility_id'])) {
            $facility = Facility::whereKey($data['facility_id'])->where('is_available', true)->lockForUpdate()->first();
            if (! $facility) {
                throw ValidationException::withMessages(['facility_id' => 'This facility is currently unavailable.']);
            }
            if (($data['pax'] ?? 0) > $facility->capacity) {
                throw ValidationException::withMessages(['pax' => 'This facility capacity is '.$facility->capacity.'.']);
            }
            $conflict = Reservation::where('facility_id', $facility->id)
                ->whereIn('status', self::BUSY_STATUSES)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['facility_id' => 'This facility is already requested for that time.']);
            }
        }

        foreach ($equipment as $equipmentId => $details) {
            $item = Equipment::whereKey($equipmentId)->where('is_available', true)->lockForUpdate()->first();
            if (! $item) {
                throw ValidationException::withMessages(['equipment.'.$equipmentId => 'This equipment is currently unavailable.']);
            }
            $reserved = DB::table('reservation_equipment')
                ->join('reservations', 'reservations.id', '=', 'reservation_equipment.reservation_id')
                ->where('reservation_equipment.equipment_id', $equipmentId)
                ->whereIn('reservations.status', self::BUSY_STATUSES)
                ->where('reservations.starts_at', '<', $endsAt)
                ->where('reservations.ends_at', '>', $startsAt)
                ->when($ignore, fn ($query) => $query->where('reservations.id', '!=', $ignore->id))
                ->sum('reservation_equipment.quantity');
            if ($reserved + $details['quantity'] > $item->quantity_available) {
                throw ValidationException::withMessages(['equipment.'.$equipmentId => 'Only '.max(0, $item->quantity_available - $reserved).' units are available for this time.']);
            }
        }
    }

    private function authorizeReservation(Request $request, Reservation $reservation): void
    {
        abort_unless($request->user()->role !== 'student' || $reservation->user_id === $request->user()->id, 403);
    }
}
