<?php

class Csrf
{
    private const SESSION_KEY = '_csrf_tokens';

    public static function token(string $context = 'default'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY][$context])) {
            $_SESSION[self::SESSION_KEY][$context] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY][$context];
    }

    public static function field(string $context = 'default'): string
    {
        $token = htmlspecialchars(self::token($context), ENT_QUOTES, 'UTF-8');
        $contextEsc = htmlspecialchars($context, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="_csrf_context" value="' . $contextEsc . '">'
            . '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function validateRequest(string $context = 'default'): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $postedContext = (string)($_POST['_csrf_context'] ?? '');
        $postedToken = $_POST['_csrf_token'] ?? null;
        $expectedToken = $_SESSION[self::SESSION_KEY][$context] ?? '';

        return hash_equals($context, $postedContext)
            && is_string($postedToken)
            && $expectedToken !== ''
            && hash_equals($expectedToken, $postedToken);
    }

    public static function rotate(string $context = 'default'): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        unset($_SESSION[self::SESSION_KEY][$context]);
    }
}
