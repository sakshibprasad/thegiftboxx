<?php
declare(strict_types=1);

/**
 * API keys and passwords stored in the database are encrypted with the
 * app_key from config.php (which lives outside the public web folder).
 * Uses libsodium when available, otherwise OpenSSL AES-256-GCM.
 */
const APP_KEY_BYTES = 32;

function app_key(): string
{
    $key = base64_decode((string) config('app_key', ''), true);
    if ($key === false || strlen($key) !== APP_KEY_BYTES) {
        throw new RuntimeException('app_key missing or invalid in app/config.php');
    }
    return $key;
}

function encrypt_value(string $plain): string
{
    if (function_exists('sodium_crypto_secretbox')) {
        $nonce = random_bytes(24);
        return 'enc:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, app_key()));
    }
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return 'enc2:' . base64_encode($iv . $tag . $cipher);
}

function decrypt_value(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    if (str_starts_with($stored, 'enc2:')) {
        $raw = base64_decode(substr($stored, 5), true);
        if ($raw === false || strlen($raw) < 29) {
            return '';
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? '' : $plain;
    }
    if (!str_starts_with($stored, 'enc:')) {
        return $stored;
    }
    if (!function_exists('sodium_crypto_secretbox_open')) {
        return '';
    }
    $raw = base64_decode(substr($stored, 4), true);
    if ($raw === false || strlen($raw) <= 24) {
        return '';
    }
    $plain = sodium_crypto_secretbox_open(substr($raw, 24), substr($raw, 0, 24), app_key());
    return $plain === false ? '' : $plain;
}
