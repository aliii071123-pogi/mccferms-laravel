<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Facility;
use App\Models\Reservation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $reservations = Reservation::with(['facility', 'equipment', 'user']);
        if ($user->role === 'student') {
            $reservations->where('user_id', $user->id);
        }

        return view('dashboard', [
            'facilities' => Facility::where('is_available', true)->orderBy('name')->get(),
            'equipment' => Equipment::where('is_available', true)->orderBy('name')->get(),
            'upcoming' => (clone $reservations)->where('ends_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
            'pendingCount' => $user->role === 'student'
                ? (clone $reservations)->where('status', 'pending')->count()
                : Reservation::where('status', 'pending')->count(),
            'activeCount' => (clone $reservations)->where('status', 'approved')->where('ends_at', '>=', now())->count(),
        ]);
    }

    public function api(Request $request)
    {
        $user = $request->user();
        $reservations = Reservation::query();
        if ($user->role === 'student') {
            $reservations->where('user_id', $user->id);
        }

        return response()->json(['data' => [
            'pending_requests' => $user->role === 'student'
                ? (clone $reservations)->where('status', 'pending')->count()
                : Reservation::where('status', 'pending')->count(),
            'approved_upcoming' => (clone $reservations)->where('status', 'approved')->where('ends_at', '>=', now())->count(),
            'facilities_available' => Facility::where('is_available', true)->count(),
            'equipment_types_available' => Equipment::where('is_available', true)->count(),
        ]]);
    }
}
