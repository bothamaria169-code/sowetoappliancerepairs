<?php
declare(strict_types=1);

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Every email goes out from one mailbox (Admin → Settings → Email, falling
 * back to secrets.php).
 *
 * @return array{host: string, port: int, user: string, pass: string, from: string}
 */
function mailboxConfig(): array
{
    $user = get_setting('smtp_user', SMTP_USER) ?: ADMIN_EMAIL;
    return [
        'host' => get_setting('smtp_host', SMTP_HOST),
        'port' => (int) get_setting('smtp_port', (string) SMTP_PORT) ?: 465,
        'user' => $user,
        'pass' => get_setting('smtp_pass', SMTP_PASS),
        'from' => $user,
    ];
}

/**
 * Sends one email and logs it in sent_emails (Admin → Sent log).
 *
 * Tries the mail server in Settings; if the web server can't reach that
 * address (common on shared hosting) it retries through localhost, and as a
 * last resort the server's own mail().
 *
 * $opt: in_reply_to (Message-ID), cc, files ([[bytes, filename], …] attached from memory).
 *
 * @param string|string[] $to
 * @return string '' on success, otherwise the reason it failed
 */
function sendMail(string|array $to, string $subject, string $html, array $attachments = [], ?string $replyTo = null, string $kind = '', array $opt = []): string
{
    $c = mailboxConfig();
    $GLOBALS['SAR_LAST_MIME'] = '';
    $routes = [];
    if ($c['pass'] !== '') {
        if ($c['host'] !== '') $routes[] = [$c['host'], $c['port'], true];
        if (!IS_LOCAL) {
            $routes[] = ['localhost', $c['port'], false];
            if ($c['port'] !== 587) $routes[] = ['localhost', 587, false];
        }
    }
    $routes[] = ['mail()', 0, false];

    $error = '';
    $via = '';
    foreach ($routes as [$host, $port, $verify]) {
        $error = '';
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            if ($host === 'mail()') {
                $mail->isMail();
                $mail->Sender = $c['from'];
            } else {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->Port       = $port;
                $mail->SMTPAuth   = true;
                $mail->Username   = $c['user'];
                $mail->Password   = $c['pass'];
                $mail->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Timeout    = 12;
                // localhost presents the server's own certificate, not mail.<domain>.
                if (!$verify) $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            }
            $mail->setFrom($c['from'], get_setting('mail_from_name', APP_NAME));
            foreach ((array) $to as $addr) $mail->addAddress($addr);
            foreach ((array) ($opt['cc'] ?? []) as $addr) $mail->addCC($addr);
            $mail->addReplyTo($replyTo ?: $c['from']);
            if (!empty($opt['in_reply_to'])) {
                $mail->addCustomHeader('In-Reply-To', $opt['in_reply_to']);
                $mail->addCustomHeader('References', $opt['in_reply_to']);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</tr>'], "\n", $html))));
            foreach ($attachments as $file) if (is_file($file)) $mail->addAttachment($file);
            foreach ($opt['files'] ?? [] as [$bytes, $name]) $mail->addStringAttachment($bytes, $name);
            $mail->send();
            $GLOBALS['SAR_LAST_MIME'] = $mail->getSentMIMEMessage();
            $via = $host === 'mail()' ? 'mail()' : ($verify ? 'smtp' : 'localhost');
            break;
        } catch (\Throwable $e) {
            $error = $e->getMessage() ?: 'Unknown mail error';
            // A wrong password won't get better on another route.
            if (stripos($error, 'authenticate') !== false) break;
        }
    }
    if ($error !== '') error_log('SAR mail to ' . implode(',', (array) $to) . ' failed: ' . $error);
    $GLOBALS['SAR_LAST_ROUTE'] = $via;
    try {
        getDB()->prepare('INSERT INTO sent_emails (to_email, subject, kind, status, error, mailbox) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([mb_substr(implode(', ', (array) $to), 0, 255), mb_substr($subject, 0, 255), $kind ?: null, $error === '' ? 'sent' : 'failed',
                       $error === '' ? null : mb_substr($error, 0, 500), mb_substr($via ?: '-', 0, 10)]);
    } catch (\Throwable) {
    }
    return $error;
}

/** The branded shell every email is wrapped in. */
function emailLayout(string $heading, string $bodyHtml, ?string $buttonText = null, ?string $buttonUrl = null, string $footerHtml = ''): string
{
    $btn = $buttonText && $buttonUrl
        ? '<p style="margin:28px 0 8px"><a href="' . e($buttonUrl) . '" style="background:#ff7a1a;color:#0a1c3d;text-decoration:none;padding:13px 26px;border-radius:10px;font-weight:700;display:inline-block">' . e($buttonText) . '</a></p>'
        : '';
    $h1    = $heading !== '' ? '<h1 style="font-size:21px;margin:0 0 14px">' . $heading . '</h1>' : '';
    $year  = date('Y');
    $url   = APP_URL;
    $phone = e(biz('biz_phone'));
    $mail  = e(biz('biz_email'));
    return <<<HTML
<!doctype html><html><body style="margin:0;background:#f3f5fa;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#0e1726">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5fa;padding:28px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e3e8f2">
<tr><td style="background:#0a1c3d;padding:16px 28px"><span style="color:#ffffff;font-size:20px;font-weight:800">Soweto <span style="color:#ff7a1a">Appliance Repairs</span></span></td></tr>
<tr><td style="padding:30px 28px 26px;font-size:15px;line-height:1.6">
{$h1}
{$bodyHtml}
{$btn}
{$footerHtml}
</td></tr>
<tr><td style="padding:16px 28px;background:#f7f8fc;color:#7c8899;font-size:12px">© {$year} Soweto Appliance Repairs · {$phone} · {$mail} · <a href="{$url}" style="color:#7c8899">{$url}</a></td></tr>
</table></td></tr></table></body></html>
HTML;
}

function emailRows(array $rows): string
{
    $out = '<table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:14px">';
    foreach ($rows as $k => $v) {
        $out .= '<tr><td style="padding:7px 0;color:#7c8899;width:38%;border-bottom:1px solid #eef1f6">' . e($k) . '</td>'
              . '<td style="padding:7px 0;border-bottom:1px solid #eef1f6"><strong>' . e((string) $v) . '</strong></td></tr>';
    }
    return $out . '</table>';
}
