<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
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

        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $members = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully.',
            'data' => UserResource::collection($members->getCollection()),
            'meta' => [
                'current_page' => $members->currentPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
                'last_page' => $members->lastPage(),
            ],
        ]);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $data = $request->validated();

        $member = User::create([
            ...$data,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member created successfully.',
            'data' => new UserResource($member),
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $member = User::findOrFail($id);

        if (! $request->user()->isAdmin() && $request->user()->id !== $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member retrieved successfully.',
            'data' => new UserResource($member),
            'meta' => null,
        ]);
    }

    public function update(UpdateMemberRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $member = User::findOrFail($id);

        $data = $request->safe()->except('password');

        if (
            $member->id === $request->user()->id
            && array_key_exists('is_active', $data)
            && $data['is_active'] === false
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
                'error' => ['code' => 'RESOURCE_CONFLICT', 'details' => null],
            ], 409);
        }

        $removesActiveAdmin = $member->isAdmin() && $member->is_active && (
            ($data['role'] ?? 'admin') !== 'admin'
            || (array_key_exists('is_active', $data) && $data['is_active'] === false)
        );

        if (
            $removesActiveAdmin
            && User::where('role', 'admin')->where('is_active', true)->count() <= 1
        ) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active administrator is required.',
                'error' => ['code' => 'RESOURCE_CONFLICT', 'details' => null],
            ], 409);
        }

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $member->update($data);

        if (array_key_exists('is_active', $data) && $data['is_active'] === false) {
            $member->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Member updated successfully.',
            'data' => new UserResource($member),
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $member = User::findOrFail($id);

        if ($member->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
                'error' => ['code' => 'RESOURCE_CONFLICT', 'details' => null],
            ], 409);
        }

        if (
            $member->isAdmin()
            && User::where('role', 'admin')->where('is_active', true)->count() <= 1
        ) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active administrator is required.',
                'error' => ['code' => 'RESOURCE_CONFLICT', 'details' => null],
            ], 409);
        }

        $member->update(['is_active' => false]);
        $member->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member deactivated successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
