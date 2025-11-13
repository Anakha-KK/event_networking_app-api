<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignupRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SignupController extends Controller
{
    public function __invoke(SignupRequest $request): JsonResponse
    {
        $formData = $request->validated();

        $user = DB::transaction(function () use ($formData) {
            $user = User::create([
                'name' => User::buildName($formData['first_name'], $formData['last_name']),
                'email' => $formData['email'],
                'password' => $formData['password'],
            ]);

            $user->profile()->create([
                'job_title' => $formData['job_title'],
                'company_name' => $formData['company_name'],
                'location' => $formData['location'] ?? null,
                'bio' => $formData['bio'] ?? null,
                'phone_number' => $formData['phone_number'] ?? null,
                'is_first_timer' => $formData['is_first_timer'] ?? false,
            ]);

            return $user->load('profile');
        });

        return response()->json([
            'message' => 'Signup successful.',
            'user' => $user,
        ], 201);
    }
}
