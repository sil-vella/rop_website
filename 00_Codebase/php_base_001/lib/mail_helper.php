<?php
/**
 * Mail helper for Dutch.mt (e.g. registration confirmation).
 * If MAIL_SMTP_* is configured (e.g. Gmail), sends via SMTP over TLS/SSL.
 * Otherwise falls back to PHP mail().
 */

declare(strict_types=1);

/**
 * Send a registration confirmation email.
 * Uses SMTP when config has mail_smtp_enabled and credentials; otherwise mail().
 *
 * @param string $toEmail Recipient email
 * @param string $toName  Recipient name (e.g. "John" or "John Smith")
 * @param string $eventName Event/party name (e.g. "18th April 2026")
 * @param array $config Config array (mail_from, mail_from_name, mail_smtp_* when using SMTP)
 * @return bool True if send succeeded
 */
function mail_send_registration_confirmation(string $toEmail, string $toName, string $eventName, array $config): bool
{
    $from = $config['mail_from'] ?? 'noreply@reignofplay.com';
    $fromName = $config['mail_from_name'] ?? 'Dutch.mt';
    $subject = 'Registration confirmed — ' . $eventName;
    $body = "Hello " . $toName . ",\n\n"
        . "You are registered for: " . $eventName . ".\n\n"
        . "Time and location will be sent at a later date.\n\n"
        . "We look forward to seeing you!\n\n"
        . "— " . ($fromName) . "\n";

    $to = trim($toEmail);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $useSmtp = !empty($config['mail_smtp_enabled'])
        && !empty($config['mail_smtp_host'])
        && $config['mail_smtp_user'] !== ''
        && $config['mail_smtp_password'] !== '';

    if ($useSmtp) {
        return mail_send_via_smtp($config, $from, $fromName, $to, $subject, $body);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . (strpos($fromName, ',') !== false || strpos($fromName, '"') !== false
            ? '"' . addslashes($fromName) . '" <' . $from . '>'
            : $fromName . ' <' . $from . '>'),
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Send a "tournament full" email: inform the user they will be notified on any dropouts.
 *
 * @param string $toEmail Recipient email
 * @param string $toName  Recipient name
 * @param string $eventName Event/party name
 * @param array $config Config array (mail_from, mail_from_name, mail_smtp_* when using SMTP)
 * @return bool True if send succeeded
 */
/**
 * Resolve contact-form inbox address.
 *
 * Priority: explicit $requestedRecipient (must be on allowlist) →
 * source map (MAIL_CONTACT_BY_SOURCE) → MAIL_CONTACT_TO / MAIL_FROM.
 *
 * Allowlist = MAIL_CONTACT_ALLOWLIST, or just the default inbox when unset.
 * Returns null if a requested recipient is not allowed.
 *
 * @param array $config
 * @param string|null $requestedRecipient From JSON `recipient`
 * @param string|null $sourceTag ROP|Dutch|Portfolio
 * @return string|null Lowercased email or null if rejected
 */
function mail_resolve_contact_recipient(array $config, ?string $requestedRecipient = null, ?string $sourceTag = null): ?string
{
    $default = strtolower(trim($config['mail_contact_to'] ?? $config['mail_from'] ?? 'hello@reignofplay.com'));
    $allowlist = $config['mail_contact_allowlist'] ?? [];
    if (!is_array($allowlist) || $allowlist === []) {
        $allowlist = $default !== '' ? [$default] : [];
    }
    $allowlist = array_values(array_unique(array_map('strtolower', $allowlist)));

    $requested = $requestedRecipient !== null ? strtolower(trim($requestedRecipient)) : '';
    if ($requested !== '') {
        if (!filter_var($requested, FILTER_VALIDATE_EMAIL) || !in_array($requested, $allowlist, true)) {
            return null;
        }
        return $requested;
    }

    $bySource = $config['mail_contact_by_source'] ?? [];
    if (is_array($bySource) && $sourceTag !== null && $sourceTag !== '' && isset($bySource[$sourceTag])) {
        $mapped = strtolower(trim((string) $bySource[$sourceTag]));
        if ($mapped !== '' && filter_var($mapped, FILTER_VALIDATE_EMAIL) && in_array($mapped, $allowlist, true)) {
            return $mapped;
        }
    }

    if ($default === '' || !filter_var($default, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $default;
}

/**
 * Send a contact-form message to the site inbox (Reply-To: submitter).
 *
 * @param string $name      Submitter name
 * @param string $fromEmail Submitter email (used as Reply-To)
 * @param string $message   Message body
 * @param array $config     Config array (mail_contact_to, mail_from, mail_smtp_*)
 * @param string|null $sourceTag Optional site tag appended to subject (e.g. ROP, Dutch)
 * @param string|null $platform Optional platform (e.g. iOS, Android)
 * @param string|null $recipient Optional inbox override (must be allowlisted)
 * @return bool True if send succeeded
 */
function mail_send_contact(string $name, string $fromEmail, string $message, array $config, ?string $sourceTag = null, ?string $platform = null, ?string $recipient = null): bool
{
    $to = mail_resolve_contact_recipient($config, $recipient, $sourceTag);
    $from = $config['mail_from'] ?? 'noreply@reignofplay.com';
    $fromName = $config['mail_from_name'] ?? 'Reign of Play';
    $subject = 'Contact form: ' . $name;
    if ($sourceTag !== null && $sourceTag !== '') {
        $subject .= ' — ' . $sourceTag;
    }
    $body = "Name: " . $name . "\n"
        . "Email: " . $fromEmail . "\n";
    if ($sourceTag !== null && $sourceTag !== '') {
        $body .= "Source: " . $sourceTag . "\n";
    }
    if ($platform !== null && $platform !== '') {
        $body .= "Platform: " . $platform . "\n";
    }
    $body .= "\nMessage:\n"
        . $message . "\n";

    if ($to === null || $to === '') {
        return false;
    }
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $useSmtp = !empty($config['mail_smtp_enabled'])
        && !empty($config['mail_smtp_host'])
        && $config['mail_smtp_user'] !== ''
        && $config['mail_smtp_password'] !== '';

    if ($useSmtp) {
        return mail_send_via_smtp($config, $from, $fromName, $to, $subject, $body, $fromEmail);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . (strpos($fromName, ',') !== false || strpos($fromName, '"') !== false
            ? '"' . addslashes($fromName) . '" <' . $from . '>'
            : $fromName . ' <' . $from . '>'),
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function mail_send_tournament_full(string $toEmail, string $toName, string $eventName, array $config): bool
{
    $from = $config['mail_from'] ?? 'noreply@reignofplay.com';
    $fromName = $config['mail_from_name'] ?? 'Dutch.mt';
    $subject = 'Tournament full — ' . $eventName;
    $body = "Hello " . $toName . ",\n\n"
        . "Thank you for your interest in: " . $eventName . ".\n\n"
        . "The tournament is already full. We will notify you if there are any dropouts and a place becomes available for you.\n\n"
        . "— " . ($fromName) . "\n";

    $to = trim($toEmail);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $useSmtp = !empty($config['mail_smtp_enabled'])
        && !empty($config['mail_smtp_host'])
        && $config['mail_smtp_user'] !== ''
        && $config['mail_smtp_password'] !== '';

    if ($useSmtp) {
        return mail_send_via_smtp($config, $from, $fromName, $to, $subject, $body);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: ' . (strpos($fromName, ',') !== false || strpos($fromName, '"') !== false
            ? '"' . addslashes($fromName) . '" <' . $from . '>'
            : $fromName . ' <' . $from . '>'),
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Send an email via SMTP (e.g. Gmail). Uses sockets; supports TLS (STARTTLS) and SSL.
 *
 * @param array $config Must contain mail_smtp_host, mail_smtp_port, mail_smtp_encrypt (tls|ssl), mail_smtp_user, mail_smtp_password
 * @param string $from Sender email
 * @param string $fromName Sender display name
 * @param string $to Recipient email
 * @param string $subject Subject line
 * @param string $body Plain text body
 * @return bool True if SMTP send succeeded
 */
function mail_send_via_smtp(array $config, string $from, string $fromName, string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $host = $config['mail_smtp_host'] ?? '';
    $port = (int) ($config['mail_smtp_port'] ?? 587);
    $encrypt = strtolower($config['mail_smtp_encrypt'] ?? 'tls');
    $user = $config['mail_smtp_user'] ?? '';
    $pass = $config['mail_smtp_password'] ?? '';
    if ($host === '' || $user === '' || $pass === '') {
        return false;
    }

    $ssl = ($encrypt === 'ssl');
    $target = ($ssl ? 'ssl://' : '') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $timeout = 15;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $fp = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        error_log(sprintf('SMTP connect failed: %s:%d %s (%d)', $host, $port, $errstr ?: 'unknown', $errno));
        return false;
    }

    $read = function () use ($fp): string {
        $line = @fgets($fp, 512);
        return $line !== false ? $line : '';
    };
    $expect = function (string $prefix) use ($read): bool {
        $line = $read();
        return strpos($line, $prefix) === 0;
    };

    if (!$expect('220')) {
        fclose($fp);
        return false;
    }

    fwrite($fp, "EHLO " . ($host) . "\r\n");
    if (!$expect('250')) {
        fclose($fp);
        return false;
    }
    while ($line = $read()) {
        if (strpos($line, '250 ') !== 0 && strpos($line, '250-') !== 0) {
            break;
        }
    }

    if (!$ssl && $encrypt === 'tls') {
        fwrite($fp, "STARTTLS\r\n");
        if (!$expect('220')) {
            fclose($fp);
            return false;
        }
        $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) {
            fclose($fp);
            return false;
        }
        fwrite($fp, "EHLO " . $host . "\r\n");
        if (!$expect('250')) {
            fclose($fp);
            return false;
        }
        while ($line = $read()) {
            if (strpos($line, '250 ') !== 0 && strpos($line, '250-') !== 0) {
                break;
            }
        }
    }

    fwrite($fp, "AUTH LOGIN\r\n");
    if (!$expect('334')) {
        fclose($fp);
        return false;
    }
    fwrite($fp, base64_encode($user) . "\r\n");
    if (!$expect('334')) {
        fclose($fp);
        return false;
    }
    fwrite($fp, base64_encode($pass) . "\r\n");
    if (!$expect('235')) {
        error_log('SMTP AUTH failed (check MAIL_SMTP_USER / MAIL_SMTP_PASSWORD or use Zoho app-specific password)');
        fclose($fp);
        return false;
    }

    fwrite($fp, "MAIL FROM:<" . $from . ">\r\n");
    if (!$expect('250')) {
        fclose($fp);
        return false;
    }
    fwrite($fp, "RCPT TO:<" . $to . ">\r\n");
    if (!$expect('250')) {
        fclose($fp);
        return false;
    }
    fwrite($fp, "DATA\r\n");
    if (!$expect('354')) {
        fclose($fp);
        return false;
    }

    $fromHeader = (strpos($fromName, ',') !== false || strpos($fromName, '"') !== false)
        ? '"' . addslashes($fromName) . '" <' . $from . '>'
        : $fromName . ' <' . $from . '>';
    $replyHeader = ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) ? $replyTo : $from;
    $raw = "From: " . $fromHeader . "\r\n"
        . "To: " . $to . "\r\n"
        . "Subject: " . $subject . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Reply-To: " . $replyHeader . "\r\n"
        . "\r\n"
        . $body
        . "\r\n.\r\n";
    fwrite($fp, $raw);
    if (!$expect('250')) {
        fclose($fp);
        return false;
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return true;
}
