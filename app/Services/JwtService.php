<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JwtService
{
    private function secret(): string
    {
        $secret = config('jwt.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException(
                'JWT_SECRET must contain at least 32 characters.'
            );
        }

        return $secret;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false
    {
        $data = strtr($data, '-_', '+/');
        $data .= str_repeat('=', (4 - strlen($data) % 4) % 4);

        return base64_decode($data, true);
    }

    public function createToken(User $user): string
    {
        $now = time();
        $expiresAt = $now + (int) config('jwt.ttl', 3600);
        $jti = bin2hex(random_bytes(16));

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = [
            'iss' => config('jwt.issuer'),
            'sub' => (string) $user->id,
            'iat' => $now,
            'exp' => $expiresAt,
            'jti' => $jti,
        ];

        $encodedHeader = $this->base64UrlEncode(
            json_encode($header, JSON_THROW_ON_ERROR)
        );

        $encodedPayload = $this->base64UrlEncode(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $unsignedToken = $encodedHeader.'.'.$encodedPayload;

        $signature = hash_hmac(
            'sha256',
            $unsignedToken,
            $this->secret(),
            true
        );

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    public function verifyToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $headerJson = $this->base64UrlDecode($encodedHeader);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        if (
            $headerJson === false ||
            $payloadJson === false ||
            $signature === false
        ) {
            throw new RuntimeException('Invalid token encoding.');
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (
            ! is_array($header) ||
            ($header['alg'] ?? null) !== 'HS256' ||
            ! is_array($payload)
        ) {
            throw new RuntimeException('Invalid token data.');
        }

        $unsignedToken = $encodedHeader.'.'.$encodedPayload;

        $expectedSignature = hash_hmac(
            'sha256',
            $unsignedToken,
            $this->secret(),
            true
        );

        if (! hash_equals($expectedSignature, $signature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        if (
            ! isset($payload['sub'], $payload['exp'], $payload['jti']) ||
            ! is_numeric($payload['sub']) ||
            ! is_numeric($payload['exp']) ||
            ! is_string($payload['jti'])
        ) {
            throw new RuntimeException('Missing token claims.');
        }

        if ((int) $payload['exp'] <= time()) {
            throw new RuntimeException('Token has expired.');
        }

        if (($payload['iss'] ?? null) !== config('jwt.issuer')) {
            throw new RuntimeException('Invalid token issuer.');
        }

        $revoked = DB::table('revoked_tokens')
            ->where('jti', $payload['jti'])
            ->exists();

        if ($revoked) {
            throw new RuntimeException('Token has been revoked.');
        }

        return $payload;
    }

    public function revokeToken(array $payload): void
    {
        DB::table('revoked_tokens')->updateOrInsert(
            ['jti' => $payload['jti']],
            [
                'expires_at' => date('Y-m-d H:i:s', (int) $payload['exp']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
