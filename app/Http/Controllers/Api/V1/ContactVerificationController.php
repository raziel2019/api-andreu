<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TurnstileVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactVerificationController extends Controller
{
    public function __invoke(Request $request, TurnstileVerification $turnstile): JsonResponse
    {
        $validator = Validator::make($request->only('token'), [
            'token' => ['required', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        $status = $turnstile->verify($validator->validated()['token'], $request->ip());

        return response()->json(['success' => $status === 200], $status);
    }
}
