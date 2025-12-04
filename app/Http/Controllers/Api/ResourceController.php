<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Resource;
use App\Models\Reservation;

class ResourceController extends Controller
{
    // GET /resources
    public function index(Request $request)
    {
        $resources = Resource::all();
        return response()->json($resources, 200);
    }

    // GET /resources/{resource}
    public function show(Request $request, Resource $resource)
    {
        return response()->json($resource, 200);
    }

    // POST /resources  (admin only)
    public function store(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Nincs jogosultságod erőforrás létrehozására.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'available' => 'sometimes|boolean',
        ]);

        $resource = Resource::create($validated);

        return response()->json($resource, 201);
    }

    // PUT/PATCH /resources/{resource}  (admin only)
    public function update(Request $request, Resource $resource)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Nincs jogosultságod erőforrás módosítására.'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'available' => 'sometimes|boolean',
        ]);

        $resource->update($validated);

        return response()->json($resource->fresh(), 200);
    }

    // DELETE /resources/{resource}  (admin only)
    public function destroy(Request $request, Resource $resource)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Nincs jogosultságod erőforrás törlésére.'], 403);
        }

        $resource->delete();

        return response()->json(['message' => 'Erőforrás törölve.'], 200);
    }

    // POST /resources/{resource}/reserve
    public function reserve(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'guests' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $start = $validated['start_time'];
        $end = $validated['end_time'];

        // Alap ütközésvizsgálat (ha Reservation model start_time/end_time mezőkkel rendelkezik)
        $conflict = Reservation::where('resource_id', $resource->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->where('start_time', '<=', $start)
                         ->where('end_time', '>=', $end);
                  });
            })->exists();

        if ($conflict) {
            return response()->json(['message' => 'A kiválasztott időpont ütközik egy meglévő foglalással.'], 409);
        }

        $reservationData = [
            'user_id' => $request->user()->id,
            'resource_id' => $resource->id,
            'start_time' => $start,
            'end_time' => $end,
            'guests' => $validated['guests'] ?? null,
            'note' => $validated['note'] ?? null,
        ];

        $reservation = Reservation::create($reservationData);

        return response()->json($reservation, 201);
    }
}