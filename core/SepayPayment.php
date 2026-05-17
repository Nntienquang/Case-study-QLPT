<?php

require_once __DIR__ . '/Env.php';

class SepayPayment
{
    public static function bankName(): string
    {
        return (string)(Env::get('SEPAY_BANK_NAME') ?: Env::get('VIETQR_BANK_NAME', ''));
    }

    public static function bankAccount(): string
    {
        return (string)(Env::get('SEPAY_BANK_ACCOUNT') ?: Env::get('VIETQR_ACCOUNT_NO', ''));
    }

    public static function accountName(): string
    {
        return (string)(Env::get('SEPAY_BANK_ACCOUNT_NAME') ?: Env::get('VIETQR_ACCOUNT_NAME', ''));
    }

    public static function bankBin(): string
    {
        return (string)Env::get('VIETQR_BANK_BIN', '');
    }

    public static function webhookApiKey(): string
    {
        return (string)Env::get('SEPAY_WEBHOOK_API_KEY', '');
    }

    public static function webhookSecret(): string
    {
        return (string)(Env::get('SEPAY_WEBHOOK_SECRET') ?: Env::get('SEPAY_IPN_SECRET', ''));
    }

    public static function isConfigured(): bool
    {
        return self::bankName() !== '' && self::bankAccount() !== '';
    }

    public static function qrUrl(int $amount, string $description): string
    {
        if (self::bankBin() !== '') {
            return 'https://img.vietqr.io/image/' . rawurlencode(self::bankBin() . '-' . self::bankAccount() . '-compact2.png') . '?' . http_build_query([
                'amount' => max(0, $amount),
                'addInfo' => $description,
                'accountName' => self::accountName(),
            ]);
        }

        return 'https://qr.sepay.vn/img?' . http_build_query([
            'acc' => self::bankAccount(),
            'bank' => self::normalizedBankName(),
            'amount' => max(0, $amount),
            'des' => $description,
        ]);
    }

    private static function normalizedBankName(): string
    {
        $bank = trim(self::bankName());
        $map = [
            'MB Bank' => 'MBBank',
            'MBBank' => 'MBBank',
            'MB' => 'MBBank',
            'Vietcombank' => 'Vietcombank',
            'VCB' => 'Vietcombank',
            'BIDV' => 'BIDV',
            'Techcombank' => 'Techcombank',
            'TCB' => 'Techcombank',
            'ACB' => 'ACB',
            'VPBank' => 'VPBank',
        ];

        return $map[$bank] ?? str_replace(' ', '', $bank);
    }

    public static function verifyApiKeyHeader(): bool
    {
        $expected = self::webhookApiKey();
        if ($expected === '' || $expected === 'change-me') {
            return false;
        }

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        return hash_equals('Apikey ' . $expected, trim($header));
    }

    public static function verifyHmacSignature(string $rawBody): bool
    {
        $secret = self::webhookSecret();
        if ($secret === '') {
            return false;
        }

        $signature = $_SERVER['HTTP_X_SEPAY_SIGNATURE'] ?? '';
        $timestamp = (int)($_SERVER['HTTP_X_SEPAY_TIMESTAMP'] ?? 0);
        if ($signature === '' || $timestamp <= 0) {
            return false;
        }

        // Keep replay protection tolerant because local dev machines often drift.
        if (abs(time() - $timestamp) > 600) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    public static function verifyLegacySecretHeader(): bool
    {
        $secret = self::webhookSecret();
        if ($secret === '') {
            return false;
        }

        $header = $_SERVER['HTTP_X_SECRET_KEY'] ?? '';
        return $header !== '' && hash_equals($secret, trim($header));
    }

    public static function isTrustedSepayIp(): bool
    {
        $ip = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''));
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return in_array($ip, [
            '172.236.138.20',
            '172.233.83.68',
            '171.244.35.2',
            '151.158.108.68',
            '151.158.109.79',
            '103.255.238.139',
            '2400:8905::2000:8cff:fe98:45cd',
            '2600:3c15::2000:8aff:fedd:874b',
        ], true);
    }
}
