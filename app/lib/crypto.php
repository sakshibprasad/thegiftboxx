<?php
declare(strict_types=1);

/**
 * API keys and passwords stored in the database are encrypted with the
 * app_key from config.php (which lives outside the public web folder).
 */
function app_key(): string
{
    $key = base64_decode((string) config('app_key', ''), true);
    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('app_key missing or invalid in app/config.php');
    }
    return $key;
}

function encrypt_value(string $plain): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return 'enc:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, app_key()));
}

function decrypt_value(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    if (!str_starts_with($stored, 'enc:')) {
        return $stored;
    }
    $raw = base64_decode(substr($stored, 4), true);
    if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return '';
    }
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, app_key());
    return $plain === false ? '' : $plain;
}
