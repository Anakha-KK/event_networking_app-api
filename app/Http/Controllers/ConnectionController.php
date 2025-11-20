<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectAttendeeRequest;
use App\Http\Requests\UpdateConnectionNotesRequest;
use App\Http\Resources\UserConnectionResource;
use App\Models\User;
use App\Models\UserConnection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ConnectionController extends Controller
{
    private const DAILY_LIMIT = 15;
    private const FIRST_TIMER_POINTS = 50;
    private const RETURNING_POINTS = 25;

    public function store(ConnectAttendeeRequest $request): JsonResponse
    {
        $user = $request->user();
        $attendeeId = $request->attendeeId();

        if ($attendeeId === $user->id) {
            return $this->errorResponse('You cannot connect with yourself.');
        }

        $attendee = User::with('profile')->find($attendeeId);

        if (! $attendee) {
            return $this->errorResponse('Attendee not found.', 404);
        }

        $signature = (string) $request->string('signature')->value();

        if (! $this->signatureMatches($attendee, $signature)) {
            return $this->errorResponse('The scan cannot be verified. Please try again.', 422);
        }

        if ($this->hasReachedDailyLimit($user->id)) {
            return $this->errorResponse('Daily connection limit reached. Try again tomorrow.', 429);
        }

        $pairToken = $this->pairToken($user->id, $attendee->id);

        if (UserConnection::where('pair_token', $pairToken)->exists()) {
            return $this->errorResponse('You have already connected with this attendee.');
        }

        $isFirstTimer = (bool) ($attendee->profile?->is_first_timer ?? false);
        $basePoints = $isFirstTimer ? self::FIRST_TIMER_POINTS : self::RETURNING_POINTS;
        $notes = $request->notes();
        $notesAdded = $notes !== null;
        $totalPoints = $notesAdded ? $basePoints * 2 : $basePoints;

        $connection = UserConnection::create([
            'user_id' => $user->id,
            'attendee_id' => $attendee->id,
            'pair_token' => $pairToken,
            'is_first_timer' => $isFirstTimer,
            'base_points' => $basePoints,
            'total_points' => $totalPoints,
            'notes_added' => $notesAdded,
            'notes' => $notes,
            'connected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Connection recorded.',
            'connection' => new UserConnectionResource($connection),
        ], 201);
    }

    public function updateNotes(UpdateConnectionNotesRequest $request, UserConnection $connection): JsonResponse
    {
        $user = $request->user();

        if ($connection->user_id !== $user->id) {
            abort(403, 'You are not allowed to update this connection.');
        }

        if ($connection->notes_added) {
            return $this->errorResponse('Notes were already added for this connection.');
        }

        $connection->fill([
            'notes' => $request->validated()['notes'],
            'notes_added' => true,
            'total_points' => $connection->base_points * 2,
        ])->save();

        return response()->json([
            'message' => 'Notes saved and points updated.',
            'connection' => new UserConnectionResource($connection),
        ]);
    }

    private function hasReachedDailyLimit(int $userId): bool
    {
        return UserConnection::where('user_id', $userId)
            ->whereDate('connected_at', Carbon::today())
            ->count() >= self::DAILY_LIMIT;
    }

    private function pairToken(int $userId, int $attendeeId): string
    {
        $ids = [$userId, $attendeeId];
        sort($ids);

        return implode(':', $ids);
    }

    private function signatureMatches(User $attendee, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        return hash_equals($attendee->qrSignature(), $signature);
    }

    private function errorResponse(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], $status);
    }
}
