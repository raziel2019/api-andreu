<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ContactVerificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->only('token'), [
            'token' => ['required', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $secret = config('services.turnstile.secret_key');

        if (! is_string($secret) || trim($secret) === '') {
            return response()->json(['success' => false], 503);
        }

        $payload = ['secret' => $secret, 'response' => $validator->validated()['token']];

        if ($request->ip() !== null) {
            $payload['remoteip'] = $request->ip();
        }

        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(3)->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
        } catch (ConnectionException) {
            return response()->json(['success' => false], 503);
        }

        $result = $response->json();

        if (! $response->successful() || ! is_array($result) || ! is_bool($result['success'] ?? null)) {
            return response()->json(['success' => false], 503);
        }

        if ($result['success'] !== true) {
            return response()->json(['success' => false], 422);
        }

        return response()->json(['success' => true]);
    }
}
