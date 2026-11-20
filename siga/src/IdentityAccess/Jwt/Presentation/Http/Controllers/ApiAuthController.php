<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Jwt\Presentation\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Src\IdentityAccess\Jwt\Infrastructure\JwtTokenService;

/** POST /api/auth/login. */
final class ApiAuthController
{
    public function __construct(
        private readonly JwtTokenService $tokens,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        return response()->json([
            'token' => $this->tokens->issueFor($user->id),
            'token_type' => 'Bearer',
        ]);
    }
}
