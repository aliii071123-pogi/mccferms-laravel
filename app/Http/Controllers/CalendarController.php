<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $this->month($request);
        $events = $this->eventsFor($month);
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $days = collect();

        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $days->push($day->copy());
        }

        $eventsByDate = $events->groupBy(fn (array $event) => $event['starts_at']->toDateString());

        return view('calendar.index', compact('month', 'days', 'eventsByDate'));
    }

    public function apiIndex(Request $request)
    {
        $month = $this->month($request);

        return response()->json([
            'data' => $this->eventsFor($month)->map(fn (array $event) => [
                'facility' => $event['facility'],
                'starts_at' => $event['starts_at']->toISOString(),
                'ends_at' => $event['ends_at']->toISOString(),
                'status' => $event['status'],
            ]),
        ]);
    }

    private function month(Request $request): Carbon
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        return isset($data['month'])
            ? Carbon::createFromFormat('!Y-m', $data['month'])
            : now()->startOfMonth();
    }

    private function eventsFor(Carbon $month): Collection
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth()->endOfDay();
        $reservations = Reservation::with(['facility', 'schedules'])
            ->whereNotNull('facility_id')
            ->whereIn('status', ['pending', 'recommended', 'approved'])
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->get();

        return $reservations->flatMap(fn (Reservation $reservation) => $reservation->scheduleBlocks()
            ->filter(fn ($schedule) => $schedule->starts_at->lte($end) && $schedule->ends_at->gte($start))
            ->map(fn ($schedule) => [
                'facility' => $reservation->facility->name,
                'starts_at' => $schedule->starts_at,
                'ends_at' => $schedule->ends_at,
                'status' => $reservation->status === 'recommended' ? 'pending' : $reservation->status,
            ]))
            ->sortBy('starts_at')
            ->values();
    }
}
