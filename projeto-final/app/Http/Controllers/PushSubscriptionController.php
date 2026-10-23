<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PushSubscriptionController extends Controller
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.auth' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
        ]);

        $user->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
        );
        $user->update(['wants_push' => true]);

        return response()->json(['message' => 'Dispositivo registrado.'], 201);
    }
}
