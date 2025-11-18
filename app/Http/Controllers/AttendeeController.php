<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendeeResource;
use App\Models\User;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('profile');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('job_title', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    });
            });
        }

        $filter = $request->query('filter');

        if ($filter === 'first_timers') {
            $query->whereHas('profile', fn ($profileQuery) => $profileQuery->where('is_first_timer', true));
        }

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $attendees = $query->paginate($perPage)->appends($request->query());

        return AttendeeResource::collection($attendees);
    }
}
