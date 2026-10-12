<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TurnstileVerification
{
    public function verify(string $token, ?string $ip): int
    {
        $secret = config('services.turnstile.secret_key');
        if (! is_string($secret) || trim($secret) === '') {
            return 503;
        }
        $payload = ['secret' => $secret, 'response' => $token];
        if ($ip !== null) {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(3)->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
        } catch (ConnectionException) {
            return 503;
        }

        $result = $response->json();
        if (! $response->successful() || ! is_array($result) || ! is_bool($result['success'] ?? null)) {
            return 503;
        }

        return $result['success'] === true ? 200 : 422;
    }
}
