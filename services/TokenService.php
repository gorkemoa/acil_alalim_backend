<?php
// services/TokenService.php

class TokenService {
    private static $algo = 'HS256';

    public static function create(array $payload, int $ttlSeconds = 86400) {
        $header = ['typ' => 'JWT', 'alg' => self::$algo];
        $payload['exp'] = time() + $ttlSeconds;

        $segments = [
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload))
        ];
        $signingInput = implode('.', $segments);
        $signature = self::sign($signingInput);
        $segments[] = self::base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public static function verify(string $token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $signature = self::base64UrlDecode($encodedSignature);

        if (!hash_equals(self::sign($signingInput), $signature)) return null;

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;

        return $payload;
    }

    private static function sign(string $data) {
        $secret = getenv('TOKEN_SECRET') ?: 'change_me';
        return hash_hmac('sha256', $data, $secret, true);
    }

    private static function base64UrlEncode(string $data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
