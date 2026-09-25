<?php
declare(strict_types=1);

/**
 * Send an HTML email through the SMTP account set in Admin → Settings → Email.
 * Falls back to PHP mail() when SMTP is not configured.
 */
function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $fromEmail = setting('smtp_user') ?: setting('store_email');
    $fromName = setting('mail_from_name') ?: setting('store_name');
    $host = setting('smtp_host');
    $pass = setting('smtp_pass');

    try {
        if ($host && setting('smtp_user') && $pass) {
            smtp_send($host, (int) setting('smtp_port'), (string) setting('smtp_secure'), setting('smtp_user'), $pass,
                $fromEmail, $fromName, $to, $subject, $html, $replyTo);
            return true;
        }
        $headers = build_mail_headers($fromEmail, $fromName, $to, $subject, $replyTo, false);
        $ok = @mail($to, encode_header($subject), chunk_split(base64_encode($html)), $headers);
        if (!$ok) {
            app_log('mail', 'mail() failed — set up SMTP in Settings → Email', ['to' => $to, 'subject' => $subject]);
        }
        return $ok;
    } catch (Throwable $e) {
        app_log('mail', 'send failed', ['to' => $to, 'subject' => $subject, 'error' => $e->getMessage()]);
        return false;
    }
}

function encode_header(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

function build_mail_headers(string $fromEmail, string $fromName, string $to, string $subject, ?string $replyTo, bool $full): string
{
    $h = [];
    if ($full) {
        $h[] = 'Date: ' . date('r');
        $h[] = 'To: <' . $to . '>';
        $h[] = 'Subject: ' . encode_header($subject);
        $h[] = 'Message-ID: <' . random_token(8) . '@' . (parse_url(site_url(), PHP_URL_HOST) ?: 'localhost') . '>';
    }
    $h[] = 'From: ' . encode_header($fromName) . ' <' . $fromEmail . '>';
    if ($replyTo) {
        $h[] = 'Reply-To: <' . $replyTo . '>';
    }
    $h[] = 'MIME-Version: 1.0';
    $h[] = 'Content-Type: text/html; charset=UTF-8';
    $h[] = 'Content-Transfer-Encoding: base64';
    return implode("\r\n", $h);
}

function smtp_send(string $host, int $port, string $secure, string $user, string $pass, string $fromEmail,
                   string $fromName, string $to, string $subject, string $html, ?string $replyTo): void
{
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        throw new RuntimeException("SMTP connect failed: {$errstr}");
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c, array $okCodes) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        $resp = $read();
        if (!in_array((int) substr($resp, 0, 3), $okCodes, true)) {
            throw new RuntimeException('SMTP error after "' . explode(' ', $c)[0] . '": ' . trim($resp));
        }
        return $resp;
    };
    $read();
    $ehlo = parse_url(site_url(), PHP_URL_HOST) ?: 'localhost';
    $cmd("EHLO {$ehlo}", [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('STARTTLS failed');
        }
        $cmd("EHLO {$ehlo}", [250]);
    }
    $cmd('AUTH LOGIN', [334]);
    $cmd(base64_encode($user), [334]);
    $cmd(base64_encode($pass), [235]);
    $cmd("MAIL FROM:<{$fromEmail}>", [250]);
    $cmd("RCPT TO:<{$to}>", [250, 251]);
    $cmd('DATA', [354]);
    $message = build_mail_headers($fromEmail, $fromName, $to, $subject, $replyTo, true)
        . "\r\n\r\n" . chunk_split(base64_encode($html));
    $cmd($message . "\r\n.", [250]);
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}

/** Wrap content in the branded email template. */
function email_layout(string $title, string $bodyHtml, ?array $button = null): string
{
    return render('emails/layout', ['title' => $title, 'body' => $bodyHtml, 'button' => $button]);
}
