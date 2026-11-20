<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Jwt\Domain\Exceptions\InvalidTokenException;
use Src\IdentityAccess\Jwt\Infrastructure\JwtTokenService;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;

/** Tarea 2.4: cubre JwtTokenService en aislamiento total. */
final class JwtTokenServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_a_token_it_issues_decodes_back_to_the_same_user_id(): void
    {
        // Se usa la hora real (no una fecha fija).
        $clock = Mockery::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable);

        $service = new JwtTokenService(secret: 'test-secret-value-for-jwt-unit-tests-1234', ttlMinutes: 60, clock: $clock);

        $token = $service->issueFor(42);

        self::assertSame(42, $service->userIdFromToken($token));
    }

    public function test_a_token_past_its_ttl_is_rejected(): void
    {
        // firebase/php-jwt compara "exp" contra time() real del sistema al decodificar.
        $farInThePast = new DateTimeImmutable('2000-01-01 00:00:00');
        $clock = Mockery::mock(ClockInterface::class);
        $clock->shouldReceive('now')->once()->andReturn($farInThePast);

        $service = new JwtTokenService(secret: 'test-secret-value-for-jwt-unit-tests-1234', ttlMinutes: 60, clock: $clock);
        $expiredToken = $service->issueFor(42);

        $this->expectException(InvalidTokenException::class);
        $service->userIdFromToken($expiredToken);
    }

    public function test_a_token_signed_with_a_different_secret_is_rejected(): void
    {
        // Misma razón que en la prueba anterior: hora real.
        $clock = Mockery::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable);

        $issuer = new JwtTokenService(secret: 'secret-a-value-for-jwt-unit-tests-1234567', ttlMinutes: 60, clock: $clock);
        $verifier = new JwtTokenService(secret: 'secret-b-value-for-jwt-unit-tests-1234567', ttlMinutes: 60, clock: $clock);

        $token = $issuer->issueFor(42);

        $this->expectException(InvalidTokenException::class);
        $verifier->userIdFromToken($token);
    }

    public function test_a_malformed_token_is_rejected_without_a_fatal_error(): void
    {
        $clock = Mockery::mock(ClockInterface::class);

        $service = new JwtTokenService(secret: 'test-secret-value-for-jwt-unit-tests-1234', ttlMinutes: 60, clock: $clock);

        $this->expectException(InvalidTokenException::class);
        $service->userIdFromToken('esto-no-es-un-jwt');
    }
}
