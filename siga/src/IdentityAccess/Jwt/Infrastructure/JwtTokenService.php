<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Jwt\Infrastructure;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Src\IdentityAccess\Jwt\Domain\Exceptions\InvalidTokenException;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Throwable;

/** Tarea 2.4 (ES-*, autenticación de la capa de API). */
final class JwtTokenService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlMinutes,
        private readonly ClockInterface $clock,
    ) {}

    public function issueFor(int $userId): string
    {
        $now = $this->clock->now();

        return JWT::encode([
            'sub' => $userId,
            'iat' => $now->getTimestamp(),
            'exp' => $now->modify("+{$this->ttlMinutes} minutes")->getTimestamp(),
        ], $this->secret, self::ALGORITHM);
    }

    /**
     * @throws InvalidTokenException si el token está mal formado, tiene una firma inválida.
     */
    public function userIdFromToken(string $token): int
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (Throwable $e) {
            throw InvalidTokenException::becauseOf($e);
        }

        return (int) $decoded->sub;
    }
}
