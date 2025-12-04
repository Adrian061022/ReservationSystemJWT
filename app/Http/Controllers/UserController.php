<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /users/me
    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    // PUT /users/me
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|min:6',
            'phone' => 'sometimes|nullable|string',
        ]);

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->filled('password')) $user->password = Hash::make($request->password);
        if ($request->has('phone')) $user->phone = $request->phone;

        $user->save();

        return response()->json($user, 200);
    }

    // Admin: list users
    public function index(Request $request)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(User::all(), 200);
    }

    // Admin: show user
    public function show(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        return response()->json($user, 200);
    }

    // Admin: delete user
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }
}
