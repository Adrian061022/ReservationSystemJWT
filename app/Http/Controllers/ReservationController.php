<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;

class ReservationController extends Controller
{
    public function index(Request $request){

        $user = $request->user();

        if($user->is_admin){
            $reservations = Reservation::all();
        }else
        {
            $reservations = Reservation::where('user_id', $user->id)->get();
        }
        return response()->json($reservations, 200);
    }

    public function show(Request $request, $id){
        $user = $request->user();

        $reservation = Reservation::findOrFail($id);

        if(!$user->is_admin && $reservation->user_id != $user->id){
            return response()->json(['message' => 'Nincs jogosultságod megtekinteni ezt a foglalást!'], 403);
        }

        return response()->json($reservation, 200);
        
    }

 public function store(Request $request)
{
    $validated = $request->validate([
        'resource_id' => 'required|exists:resources,id',
        'start_time' => 'required|date|after_or_equal:now',
        'end_time'   => 'required|date|after:start_time',
    ]);

    $reservation = Reservation::create([
        'user_id' => $request->user()->id,
        'resource_id' => $validated['resource_id'],
        'start_time' => $validated['start_time'],
        'end_time' => $validated['end_time'],
        'status' => 'pending', // migráció szerinti default
    ]);

    return response()->json($reservation, 201);
}


public function update(Request $request, $id)
{
    $user = $request->user();
    $reservation = Reservation::findOrFail($id);

    // csak admin vagy a foglalás tulajdonosa módosíthatja
    if (!$user->is_admin && $reservation->user_id !== $user->id) {
        return response()->json(['message' => 'Nincs jogosultságod módosítani ezt a foglalást!'], 403);
    }

    $validated = $request->validate([
        'resource_id' => 'sometimes|required|exists:resources,id',
        'start_time' => 'sometimes|required|date|after_or_equal:now',
        'end_time'   => 'sometimes|required|date|after:start_time',
        'status'     => 'sometimes|in:pending,approved,rejected,cancelled',
    ]);

    // ha nem admin → a státuszt ne lehessen módosítani
    if (!$user->is_admin) {
        unset($validated['status']);
    }

    $reservation->update($validated);

    return response()->json($reservation->fresh(), 200);
}


public function destroy(Request $request, $id)
{
    $user = $request->user();
    $reservation = Reservation::findOrFail($id);

    if (!$user->is_admin && $reservation->user_id !== $user->id) {
        return response()->json(['message' => 'Nincs jogosultságod törölni ezt a foglalást!'], 403);
    }

    $reservation->delete();

    return response()->json(['message' => 'Foglalás törölve.'], 200);
}
}
