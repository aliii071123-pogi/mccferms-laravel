<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = Facility::with(['reservations' => fn ($query) => $query
            ->with('schedules')
            ->whereIn('status', ['pending', 'approved'])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')])->orderBy('name')->get();
        $editing = $request->filled('edit') ? Facility::findOrFail($request->query('edit')) : new Facility;

        return view('facilities.index', compact('facilities', 'editing'));
    }

    public function apiIndex()
    {
        $facilities = Facility::with(['reservations' => fn ($query) => $query
            ->with('schedules')
            ->whereIn('status', ['pending', 'approved'])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')])->orderBy('name')->get();

        return response()->json(['data' => $facilities->map(fn (Facility $facility) => [
            'id' => $facility->id,
            'name' => $facility->name,
            'location' => $facility->location,
            'description' => $facility->description,
            'capacity' => $facility->capacity,
            'staff_in_charge' => $facility->staff_in_charge,
            'is_available' => $facility->is_available,
            'schedule' => $facility->reservations->flatMap(fn ($reservation) => $reservation->scheduleBlocks()->map(fn ($schedule) => [
                'starts_at' => $schedule->starts_at->toISOString(),
                'ends_at' => $schedule->ends_at->toISOString(),
                'status' => $reservation->status,
            ]))->values(),
        ])]);
    }

    public function store(Request $request)
    {
        $facility = Facility::create($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json(['data' => $facility], 201);
        }

        return to_route('facilities.index')->with('success', 'Facility added to the catalog.');
    }

    public function update(Request $request, Facility $facility)
    {
        $facility->update($this->validated($request, $facility));

        if ($request->expectsJson()) {
            return response()->json(['data' => $facility->fresh()]);
        }

        return to_route('facilities.index')->with('success', 'Facility details updated.');
    }

    public function destroy(Facility $facility)
    {
        abort_if($facility->reservations()->exists(), 409, 'Facilities with reservation history cannot be deleted. Mark it unavailable instead.');
        $facility->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Facility removed.']);
        }

        return to_route('facilities.index')->with('success', 'Facility removed.');
    }

    private function validated(Request $request, ?Facility $facility = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('facilities')->ignore($facility)],
            'location' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'between:1,10000'],
            'staff_in_charge' => ['required', 'string', 'max:160'],
            'is_available' => ['sometimes', 'boolean'],
        ]) + ['is_available' => $request->boolean('is_available')];
    }
}
