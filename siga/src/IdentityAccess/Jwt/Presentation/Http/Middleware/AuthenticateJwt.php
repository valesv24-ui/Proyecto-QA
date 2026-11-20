<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Jwt\Presentation\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\IdentityAccess\Jwt\Domain\Exceptions\InvalidTokenException;
use Src\IdentityAccess\Jwt\Infrastructure\JwtTokenService;
use Symfony\Component\HttpFoundation\Response;

/** Protege una ruta de /api/* exigiendo "Authorization: Bearer <token>". */
final class AuthenticateJwt
{
    public function __construct(
        private readonly JwtTokenService $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = substr($header, strlen('Bearer '));

        try {
            $userId = $this->tokens->userIdFromToken($token);
        } catch (InvalidTokenException) {
            return $this->unauthorized();
        }

        $user = User::find($userId);

        if ($user === null) {
            return $this->unauthorized();
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json(['message' => 'Token inválido o ausente.'], 401);
    }
}
