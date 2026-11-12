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
        $query = Reservation::with(['user', 'facility', 'equipment', 'schedules']);

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
            'scheduleDays' => [[]],
        ]);
    }

    public function store(Request $request)
    {
        [$data, $equipment, $schedules] = $this->validatedRequest($request);

        $reservation = DB::transaction(function () use ($request, $data, $equipment, $schedules) {
            $this->assertAvailable($data, $equipment, $schedules);
            $reservation = Reservation::create([
                ...$data,
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'return_due_at' => $equipment ? Carbon::parse($data['ends_at'])->addDays(2) : null,
            ]);
            $reservation->equipment()->sync($equipment);
            $reservation->schedules()->createMany($schedules);

            return $reservation;
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->load(['facility', 'equipment', 'schedules']), 'message' => 'Request sent. Printed letters still need to be signed.'], 201);
        }

        return to_route('reservations.show', $reservation)
            ->with('success', 'Request sent. Remember to have printed letters signed before your activity.')
            ->with('request_submitted', true);
    }

    public function show(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->load(['user', 'facility', 'equipment', 'reviewer', 'schedules'])]);
        }

        $reservation->load('schedules');

        return view('reservations.show', compact('reservation'));
    }

    public function edit(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);
        abort_unless($reservation->status === 'pending' && $request->user()->role === 'student', 403);

        $reservation->load(['equipment', 'schedules']);
        $scheduleDays = $reservation->schedules->isNotEmpty()
            ? $reservation->schedules->map(fn ($schedule) => [
                'date' => $schedule->starts_at->toDateString(),
                'start_time' => $schedule->starts_at->format('H:i'),
                'end_time' => $schedule->ends_at->format('H:i'),
            ])->all()
            : [[
                'date' => $reservation->starts_at->toDateString(),
                'start_time' => $reservation->starts_at->format('H:i'),
                'end_time' => $reservation->ends_at->format('H:i'),
            ]];

        return view('reservations.form', [
            'reservation' => $reservation,
            'facilities' => Facility::where('is_available', true)->orderBy('name')->get(),
            'equipment' => Equipment::where('is_available', true)->orderBy('name')->get(),
            'scheduleDays' => $scheduleDays,
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeReservation($request, $reservation);
        abort_unless($reservation->status === 'pending' && $request->user()->role === 'student', 403);
        [$data, $equipment, $schedules] = $this->validatedRequest($request);

        DB::transaction(function () use ($reservation, $data, $equipment, $schedules) {
            $this->assertAvailable($data, $equipment, $schedules, $reservation);
            $reservation->update([
                ...$data,
                'return_due_at' => $equipment ? Carbon::parse($data['ends_at'])->addDays(2) : null,
            ]);
            $reservation->equipment()->sync($equipment);
            $reservation->schedules()->delete();
            $reservation->schedules()->createMany($schedules);
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $reservation->fresh()->load(['facility', 'equipment', 'schedules'])]);
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
            'duration_days' => ['required', 'integer', 'between:1,7'],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'days.*.start_time' => ['required', 'date_format:H:i'],
            'days.*.end_time' => ['required', 'date_format:H:i'],
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
            $days = $request->input('days', []);
            if (! is_array($days)) {
                return;
            }
            if ((int) $request->input('duration_days') !== count($days)) {
                $validator->errors()->add('duration_days', 'Add a date and time for each event day.');
            }

            $previousDate = null;
            foreach ($days as $index => $day) {
                if (! is_array($day)) {
                    continue;
                }

                $date = $day['date'] ?? null;
                if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    if ($previousDate !== null && $date <= $previousDate) {
                        $validator->errors()->add("days.$index.date", 'Choose each event day after the previous day.');
                    }
                    $previousDate = $date;
                }

                $start = $day['start_time'] ?? null;
                $end = $day['end_time'] ?? null;
                foreach (['start_time' => $start, 'end_time' => $end] as $field => $time) {
                    if (is_string($time) && ($time < '08:00' || $time > '20:00')) {
                        $validator->errors()->add("days.$index.$field", 'Reservations are available between 8:00 AM and 8:00 PM.');
                    }
                }
                if (is_string($start) && is_string($end) && $end <= $start) {
                    $validator->errors()->add("days.$index.end_time", 'Choose an end time after the start time.');
                }
                if ($date === now()->toDateString() && is_string($start) && $start <= now()->format('H:i')) {
                    $validator->errors()->add("days.$index.start_time", 'Choose a start time later than now.');
                }
            }
        });

        $validated = $validator->validate();
        $equipment = collect($validated['equipment'] ?? [])
            ->filter(fn ($quantity) => (int) $quantity > 0)
            ->mapWithKeys(fn ($quantity, $id) => [(int) $id => ['quantity' => (int) $quantity]])
            ->all();
        ksort($equipment);
        $schedules = collect($validated['days'])->map(fn ($day) => [
            'starts_at' => Carbon::parse($day['date'].' '.$day['start_time']),
            'ends_at' => Carbon::parse($day['date'].' '.$day['end_time']),
        ])->sortBy('starts_at')->values();
        $validated['starts_at'] = $schedules->first()['starts_at'];
        $validated['ends_at'] = $schedules->last()['ends_at'];
        unset($validated['duration_days'], $validated['days'], $validated['equipment']);

        return [$validated, $equipment, $schedules->all()];
    }

    private function assertAvailable(array $data, array $equipment, array $schedules, ?Reservation $ignore = null): void
    {
        if (! empty($data['facility_id'])) {
            $facility = Facility::whereKey($data['facility_id'])->where('is_available', true)->lockForUpdate()->first();
            if (! $facility) {
                throw ValidationException::withMessages(['facility_id' => 'This facility is currently unavailable.']);
            }
            if (($data['pax'] ?? 0) > $facility->capacity) {
                throw ValidationException::withMessages(['pax' => 'This facility capacity is '.$facility->capacity.'.']);
            }
            $conflict = collect($schedules)->contains(function ($schedule) use ($facility, $ignore) {
                return Reservation::where('facility_id', $facility->id)
                    ->whereIn('status', self::BUSY_STATUSES)
                    ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                    ->where(function ($query) use ($schedule) {
                        $query->whereHas('schedules', fn ($query) => $query
                            ->where('starts_at', '<', $schedule['ends_at'])
                            ->where('ends_at', '>', $schedule['starts_at']))
                            ->orWhere(fn ($query) => $query->whereDoesntHave('schedules')
                                ->where('starts_at', '<', $schedule['ends_at'])
                                ->where('ends_at', '>', $schedule['starts_at']));
                    })
                    ->exists();
            });
            if ($conflict) {
                throw ValidationException::withMessages([
                    'facility_conflict' => 'Please communicate with the facility staff to find out which institute student council or student organization requested this time so you can coordinate with them.',
                ]);
            }
        }

        foreach ($equipment as $equipmentId => $details) {
            $item = Equipment::whereKey($equipmentId)->where('is_available', true)->lockForUpdate()->first();
            if (! $item) {
                throw ValidationException::withMessages(['equipment.'.$equipmentId => 'This equipment is currently unavailable.']);
            }
            foreach ($schedules as $schedule) {
                $reserved = DB::table('reservation_equipment')
                    ->join('reservations', 'reservations.id', '=', 'reservation_equipment.reservation_id')
                    ->leftJoin('reservation_schedules', 'reservations.id', '=', 'reservation_schedules.reservation_id')
                    ->where('reservation_equipment.equipment_id', $equipmentId)
                    ->whereIn('reservations.status', self::BUSY_STATUSES)
                    ->where(function ($query) use ($schedule) {
                        $query->where(fn ($query) => $query->whereNotNull('reservation_schedules.id')
                            ->where('reservation_schedules.starts_at', '<', $schedule['ends_at'])
                            ->where('reservation_schedules.ends_at', '>', $schedule['starts_at']))
                            ->orWhere(fn ($query) => $query->whereNull('reservation_schedules.id')
                                ->where('reservations.starts_at', '<', $schedule['ends_at'])
                                ->where('reservations.ends_at', '>', $schedule['starts_at']));
                    })
                    ->when($ignore, fn ($query) => $query->where('reservations.id', '!=', $ignore->id))
                    ->sum('reservation_equipment.quantity');
                if ($reserved + $details['quantity'] > $item->quantity_available) {
                    throw ValidationException::withMessages(['equipment.'.$equipmentId => 'Only '.max(0, $item->quantity_available - $reserved).' units are available for this time.']);
                }
            }
        }
    }

    private function authorizeReservation(Request $request, Reservation $reservation): void
    {
        abort_unless($request->user()->role !== 'student' || $reservation->user_id === $request->user()->id, 403);
    }
}
