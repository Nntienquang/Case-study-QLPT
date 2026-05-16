<?php

class Captcha
{
    private const CODE_LENGTH = 5;
    private const SLIDER_MIN = 42;
    private const SLIDER_MAX = 248;
    private const SLIDER_TOLERANCE = 8;

    public static function ensure(string $key): array
    {
        if (!isset($_SESSION[$key]['code'])) {
            self::generate($key);
        }

        return $_SESSION[$key];
    }

    public static function generate(string $key): array
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $_SESSION[$key] = [
            'code' => $code,
            'created_at' => time(),
        ];

        return $_SESSION[$key];
    }

    public static function validate(string $key, string $answer): bool
    {
        $expected = strtoupper(trim((string)($_SESSION[$key]['code'] ?? '')));
        $actual = strtoupper(trim($answer));

        if ($expected === '' || strlen($actual) < 4) {
            return false;
        }

        return hash_equals($expected, $actual);
    }

    public static function ensureSlider(string $key): array
    {
        if (!isset($_SESSION[$key]['target'], $_SESSION[$key]['token'])) {
            self::generateSlider($key);
        }

        return $_SESSION[$key];
    }

    public static function generateSlider(string $key): array
    {
        $_SESSION[$key] = [
            'target' => random_int(self::SLIDER_MIN, self::SLIDER_MAX),
            'token' => bin2hex(random_bytes(16)),
            'created_at' => time(),
        ];

        return $_SESSION[$key];
    }

    public static function validateSlider(string $key, string $position, string $token): bool
    {
        $challenge = $_SESSION[$key] ?? [];
        $expectedToken = (string)($challenge['token'] ?? '');
        $target = (int)($challenge['target'] ?? -1000);
        $actual = (int)round((float)$position);

        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            return false;
        }

        return abs($actual - $target) <= self::SLIDER_TOLERANCE;
    }

    public static function failureCount(string $key): int
    {
        return (int)($_SESSION[$key] ?? 0);
    }

    public static function recordFailure(string $key): int
    {
        $_SESSION[$key] = self::failureCount($key) + 1;

        return $_SESSION[$key];
    }

    public static function clearFailures(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
