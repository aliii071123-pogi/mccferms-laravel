<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipment = Equipment::with(['reservations' => fn ($query) => $query
            ->with('schedules')
            ->whereIn('status', ['pending', 'approved'])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')])->orderBy('name')->get();
        $categories = Equipment::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $editing = $request->filled('edit') ? Equipment::findOrFail($request->query('edit')) : new Equipment;

        return view('equipment.index', compact('equipment', 'categories', 'editing'));
    }

    public function apiIndex()
    {
        $equipment = Equipment::with(['reservations' => fn ($query) => $query
            ->with('schedules')
            ->whereIn('status', ['pending', 'approved'])
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at')])->orderBy('name')->get();

        return response()->json(['data' => $equipment->map(fn (Equipment $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'description' => $item->description,
            'staff_in_charge' => $item->staff_in_charge,
            'quantity_available' => $item->quantity_available,
            'is_available' => $item->is_available,
            'schedule' => $item->reservations->flatMap(fn ($reservation) => $reservation->scheduleBlocks()->map(fn ($schedule) => [
                'starts_at' => $schedule->starts_at->toISOString(),
                'ends_at' => $schedule->ends_at->toISOString(),
                'quantity' => $reservation->pivot->quantity,
                'status' => $reservation->status,
            ]))->values(),
        ])]);
    }

    public function store(Request $request)
    {
        $item = Equipment::create($this->validated($request));

        if ($request->expectsJson()) {
            return response()->json(['data' => $item], 201);
        }

        return to_route('equipment.index')->with('success', 'Equipment added to the catalog.');
    }

    public function update(Request $request, Equipment $equipment)
    {
        $equipment->update($this->validated($request, $equipment));

        if ($request->expectsJson()) {
            return response()->json(['data' => $equipment->fresh()]);
        }

        return to_route('equipment.index')->with('success', 'Equipment details updated.');
    }

    public function destroy(Equipment $equipment)
    {
        abort_if($equipment->reservations()->exists(), 409, 'Equipment with reservation history cannot be deleted. Mark it unavailable instead.');
        $equipment->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Equipment removed.']);
        }

        return to_route('equipment.index')->with('success', 'Equipment removed.');
    }

    private function validated(Request $request, ?Equipment $equipment = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('equipment')->ignore($equipment)],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'staff_in_charge' => ['required', 'string', 'max:160'],
            'quantity_available' => ['required', 'integer', 'between:0,100000'],
            'is_available' => ['sometimes', 'boolean'],
        ]) + ['is_available' => $request->boolean('is_available')];
    }
}
