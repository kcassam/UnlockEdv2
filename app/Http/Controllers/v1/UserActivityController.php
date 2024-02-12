<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserActivityRequest;
use App\Http\Resources\UserActivityResource;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserActivityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $sortBy = $request->query('sort', 'created_at'); // Change 'rank' to 'created_at' for timestamps
        $sortOrder = $request->query('order', 'asc');
        $search = $request->query('search', '');

        $query = UserActivity::query();

        // Apply search
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('browser_name', 'like', '%'.$search.'%')
                    ->orWhere('clicked_url', 'like', '%'.$search.'%');
            });
        }

        // Check if the user is an admin
        if ($request->user()->isAdmin()) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->where('user_id', $request->user()->id);
        }

        $userActivities = $query->paginate($perPage);

        return UserActivityResource::collection($userActivities);
    }

    public function show(Request $request, string $id)
    {
        $userActivity = UserActivity::findOrFail($id);

        if ($request->user()->isAdmin() || $userActivity->user_id == $request->user()->id) {
            return new UserActivityResource($userActivity);
        }

        return response()->json(['error' => "UserActivity with this ID not found for User: {$request->user()->username}"], Response::HTTP_NOT_FOUND);
    }

    public function store(StoreUserActivityRequest $request)
    {
        $validated = $request->validated();

        $userActivity = UserActivity::create($validated);

        return UserActivityResource::make($userActivity);
    }
}
