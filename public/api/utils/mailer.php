<?php
// public/api/utils/mailer.php
require_once __DIR__ . '/../config/env.php';

class Mailer {
    private static string $host = 'smtp.hostinger.com';
    private static int $port = 465;
    private static string $user = 'info@nex-design.online';
    private static string $pass = 'Tal#1985';
    private static string $fromName = 'Nex Design Studio';

    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
        self::$host = Env::get('SMTP_HOST', self::$host);
        self::$port = (int)Env::get('SMTP_PORT', self::$port);
        self::$user = Env::get('SMTP_USER', self::$user);
        self::$pass = Env::get('SMTP_PASS', self::$pass);
        self::$fromName = Env::get('MAIL_FROM_NAME', self::$fromName);

        $configFile = __DIR__ . '/../config/config.local.php';
        if (file_exists($configFile)) {
            $cfg = require $configFile;
            if (!empty($cfg['smtp_host'])) self::$host = $cfg['smtp_host'];
            if (!empty($cfg['smtp_port'])) self::$port = (int)$cfg['smtp_port'];
            if (!empty($cfg['smtp_user'])) self::$user = $cfg['smtp_user'];
            if (!empty($cfg['smtp_pass'])) self::$pass = $cfg['smtp_pass'];
            if (!empty($cfg['from_name'])) self::$fromName = $cfg['from_name'];
        }


        $socket = @fsockopen('ssl://' . self::$host, self::$port, $errno, $errstr, 12);
        if (!$socket) {
            error_log("SMTP connection failed: ($errno) $errstr");
            return false;
        }

        $read = function() use ($socket) {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function($cmd) use ($socket, $read) {
            fputs($socket, $cmd . "\r\n");
            return $read();
        };

        $read();
        $write("EHLO nex-design.online");
        $write("AUTH LOGIN");
        $write(base64_encode(self::$user));
        $write(base64_encode(self::$pass));

        $write("MAIL FROM: <" . self::$user . ">");
        $write("RCPT TO: <" . $toEmail . ">");
        $write("DATA");

        $boundary = '=_nex_part_' . md5(uniqid((string)time(), true));

        $headers = [];
        $headers[] = 'From: ' . self::$fromName . ' <' . self::$user . '>';
        $headers[] = 'To: ' . ($toName ? '=?UTF-8?B?' . base64_encode($toName) . '?= ' : '') . '<' . $toEmail . '>';
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: NexDesignStudio/1.0';

        $body = implode("\r\n", $headers) . "\r\n\r\n";

        if (!empty($textBody)) {
            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textBody)) . "\r\n";
        }

        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= '--' . $boundary . "--\r\n.\r\n";

        fputs($socket, $body);
        $read();

        $write("QUIT");
        fclose($socket);
        return true;
    }

    public static function sendWelcomeEarlyAccess(array $user): bool {
        $subject = "Welcome to Nex Studio — Queue Spot #" . ($user['waitlist_number'] ?? '1') . " Confirmed";
        $name = htmlspecialchars($user['name']);
        $queueNumber = htmlspecialchars((string)($user['waitlist_number'] ?? '1'));
        $os = htmlspecialchars($user['preferred_os'] === 'mac_arm' ? 'macOS (Apple Silicon)' : ucfirst($user['preferred_os']));
        $userType = ($user['user_type'] === 'student') ? 'University Student' : 'Graduate / Professional';
        $institution = htmlspecialchars($user['institution']);
        $major = htmlspecialchars($user['faculty_major']);

        $html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
  body { margin: 0; padding: 0; background-color: #070709; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #eae8e4; }
  .container { max-width: 580px; margin: 30px auto; background-color: #121115; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow: hidden; }
  .header { padding: 32px 28px; text-align: center; background: #0c0b0e; border-bottom: 1px solid rgba(255,255,255,0.06); }
  .logo { font-size: 18px; font-weight: 800; letter-spacing: 4px; color: #f5f4f0; }
  .content { padding: 32px 28px; }
  .badge { display: inline-block; padding: 5px 12px; border-radius: 6px; background: rgba(225,29,72,0.12); border: 1px solid rgba(225,29,72,0.3); color: #fb7185; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
  .queue-card { margin: 24px 0; padding: 22px; border-radius: 12px; background: #18171c; border: 1px solid rgba(255,255,255,0.08); text-align: center; }
  .queue-num { font-size: 38px; font-weight: 800; color: #ffffff; margin: 4px 0; font-family: monospace; }
  .specs-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
  .specs-table td { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
  .label { color: #8e8c94; }
  .val { color: #ffffff; font-weight: 600; text-align: right; }
  .btn { display: inline-block; padding: 12px 24px; background: #e11d48; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; margin-top: 15px; }
  .footer { padding: 20px 28px; text-align: center; font-size: 11px; color: #5e5c64; border-top: 1px solid rgba(255,255,255,0.06); font-family: monospace; }
</style>
</head>
<body>
<div class='container'>
  <div class='header'>
    <div class='logo'>NEX DESIGN</div>
    <p style='margin: 6px 0 0 0; color: #8e8c94; font-size: 12px; font-family: monospace;'>STANDALONE DESKTOP PRE-RELEASE</p>
  </div>
  <div class='content'>
    <div style='text-align: center;'>
      <span class='badge'>Waitlist Confirmed</span>
      <h1 style='font-size: 22px; font-weight: 700; margin: 16px 0 6px 0; color: #ffffff;'>Welcome, {$name}</h1>
      <p style='color: #a1a1aa; font-size: 13px; line-height: 1.6;'>Your early-access account is successfully reserved for the desktop application.</p>
    </div>

    <div class='queue-card'>
      <div style='font-size: 10px; color: #fb7185; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-family: monospace;'>Early Access Queue Position</div>
      <div class='queue-num'><span style='color: #e11d48;'>#</span>{$queueNumber}</div>
      <div style='font-size: 11px; color: #34d399; font-weight: 600; font-family: monospace;'>Wave 1 Priority Allocation</div>
    </div>

    <table class='specs-table'>
      <tr><td class='label'>Account Category</td><td class='val'>{$userType}</td></tr>
      <tr><td class='label'>Institution / University</td><td class='val'>{$institution}</td></tr>
      <tr><td class='label'>Major / Field</td><td class='val'>{$major}</td></tr>
      <tr><td class='label'>Target Platform</td><td class='val' style='color: #fb7185; font-family: monospace;'>{$os}</td></tr>
    </table>

    <div style='text-align: center; margin-top: 20px;'>
      <a href='https://nex-design.online/account' class='btn' target='_blank'>View Account Status</a>
    </div>
  </div>
  <div class='footer'>
    &copy; " . date('Y') . " Nex Design Studio &bull; Sent by info@nex-design.online
  </div>
</div>
</body>
</html>
";

        return self::send($user['email'], $user['name'], $subject, $html);
    }

    public static function sendBetaInvite(array $user): bool {
        $subject = "Nex Studio Desktop Beta Access is Ready";
        $name = htmlspecialchars($user['name']);

        $html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
  body { margin: 0; padding: 0; background-color: #070709; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #eae8e4; }
  .container { max-width: 580px; margin: 30px auto; background-color: #121115; border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; overflow: hidden; }
  .content { padding: 36px 28px; text-align: center; }
  .btn { display: inline-block; padding: 12px 24px; background: #e11d48; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; margin-top: 20px; }
</style>
</head>
<body>
<div class='container'>
  <div class='content'>
    <h1 style='color: #ffffff; font-size: 22px; margin: 0 0 12px 0;'>Beta Access Available, {$name}</h1>
    <p style='color: #a1a1aa; font-size: 13px; line-height: 1.6;'>
      You have been granted priority access to download the beta build of <strong>Nex Design Studio</strong>.
    </p>
    <a href='https://nex-design.online/account' class='btn' target='_blank'>Access Desktop Beta</a>
  </div>
</div>
</body>
</html>
";
        return self::send($user['email'], $user['name'], $subject, $html);
    }
}
