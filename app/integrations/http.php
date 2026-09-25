<?php
declare(strict_types=1);

/** Minimal cURL wrapper. Returns ['status' => int, 'body' => string, 'json' => ?array, 'error' => ?string]. */
function http_request(string $method, string $url, array $headers = [], $body = null, int $timeout = 30): array
{
    $ch = curl_init($url);
    $h = [];
    foreach ($headers as $k => $v) {
        $h[] = is_int($k) ? $v : "{$k}: {$v}";
    }
    if (is_array($body)) {
        $body = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $h[] = 'Content-Type: application/json';
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_USERAGENT => 'TheGiftBoxx/1.0',
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = $resp === false ? curl_error($ch) : null;
    curl_close($ch);
    $resp = $resp === false ? '' : (string) $resp;
    $json = json_decode($resp, true);
    return ['status' => $status, 'body' => $resp, 'json' => is_array($json) ? $json : null, 'error' => $error];
}
