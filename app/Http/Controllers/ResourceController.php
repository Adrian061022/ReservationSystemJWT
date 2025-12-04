<?php

namespace App\Http\Controllers;

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

    
}