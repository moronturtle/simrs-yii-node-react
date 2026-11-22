<?php

declare(strict_types=1);

namespace app\services;

use app\models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtService
{
    private const TTL_SECONDS = 28800;

    private string $secret;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: 'local-dev-jwt-secret-0123456789abcdef';
    }

    public function issue(User $user): string
    {
        $now = time();

        return JWT::encode([
            'iss' => 'simrs-yii',
            'sub' => (string) $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + self::TTL_SECONDS,
        ], $this->secret, 'HS256');
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return json_decode(json_encode($decoded), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
    }

    public function ttlSeconds(): int
    {
        return self::TTL_SECONDS;
    }
}
