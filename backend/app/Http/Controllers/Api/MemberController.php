<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $members = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully.',
            'data' => $members->items(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
                'last_page' => $members->lastPage(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,member',
            'job_title' => 'required|string|max:255',
            'avatar_url' => 'nullable|string|max:255',
        ]);

        $member = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'job_title' => $request->job_title,
            'avatar_url' => $request->avatar_url,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully.',
            'data' => $member,
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $member = User::findOrFail($id);

        if (!$request->user()->isAdmin() && $request->user()->id !== $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member retrieved successfully.',
            'data' => $member,
            'meta' => null,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $member = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,member',
            'job_title' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|boolean',
            'avatar_url' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['name', 'email', 'role', 'job_title', 'is_active', 'avatar_url']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully.',
            'data' => $member,
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $member = User::findOrFail($id);
        $member->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Member deactivated successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
