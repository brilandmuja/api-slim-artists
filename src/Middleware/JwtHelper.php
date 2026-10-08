<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static $secretKey = 'ITcampus-SaintMichel-Annecy-cle-secrete-2026'; // 32+ caractères pour la v7
    private static $algorithm = 'HS256';

    public static function generateToken($data, $expiry = 3600)
    {
        $issuedAt = time();
        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $issuedAt + $expiry,
            'data' => $data,
        ];
        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }

    public static function validateToken($token)
    {
        try {
            return JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
        } catch (\Throwable $e) {
            // DEBUG : à retirer une fois le problème trouvé
            file_put_contents(
                __DIR__ . '/../../logs/jwt-debug.log',
                get_class($e) . ' : ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
            return null;
        }
    }
}