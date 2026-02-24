<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/London');

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ((strlen($value) >= 2) && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

loadEnv(__DIR__ . '/.env');

// AJAX request from fetch() – we'll return JSON instead of redirecting
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function sendResponse($success, $isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success]);
        exit();
    }
    if ($success) {
        header("Location: index.html?status=success");
    } else {
        header("Location: index.html?status=error");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false]);
        exit();
    }
    header("Location: index.html");
    exit();
}

// Ensure UTF-8 input doesn't turn into "Iâ€™" in email clients
$name    = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
$email   = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
$subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
$message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    sendResponse(false, $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, $isAjax);
}

// Backup log
$log_entry = "\n" . str_repeat("=", 50) . "\n";
$log_entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
$log_entry .= "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message\n";
$log_entry .= str_repeat("=", 50) . "\n";
file_put_contents(__DIR__ . '/submissions.txt', $log_entry, FILE_APPEND);

$mail = new PHPMailer(true);

try {
    $mail->CharSet  = PHPMailer::CHARSET_UTF8;
    $mail->Encoding = PHPMailer::ENCODING_BASE64;

    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USERNAME');
    $mail->Password   = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);

    $mail->setFrom(getenv('SMTP_FROM_EMAIL') ?: getenv('SMTP_USERNAME'), getenv('SMTP_FROM_NAME') ?: 'Portfolio');
    $mail->addAddress(getenv('SMTP_TO_EMAIL') ?: getenv('SMTP_USERNAME'));
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "Portfolio Contact: " . $subject;

    // Logo: embed PNG if present, else text fallback
    $logoHtml = "<span style=\"font-weight:700;color:#fbff2b;font-size:18px;\">Kyle Devs</span>";
    $emailLogoPath = __DIR__ . '/assets/email-logo.png';
    if (file_exists($emailLogoPath)) {
        $mail->addEmbeddedImage($emailLogoPath, 'email_logo', 'email-logo.png');
        $logoHtml = "<img src=\"cid:email_logo\" alt=\"Kyle Devs\" class=\"logo-email\" style=\"height:32px;width:auto;display:block;\">";
    }

    // Preserve newlines/paragraphs in the message for HTML
    $messageHtml = nl2br($message);
    $dateStr = date('Y-m-d H:i');

    $mail->Body = "
    <html>
    <head>
      <meta charset=\"UTF-8\">
      <meta name=\"color-scheme\" content=\"light\">
      <meta name=\"supported-color-schemes\" content=\"light\">
      <style>
        :root { color-scheme: light; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #e5e5e5; background:#262036 !important; }
        .outer { padding: 24px 0; background:#262036 !important; }
        .card { max-width: 640px; margin: 0 auto; background:#191420 !important; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,0.08); }
        .card-header { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#191420 !important; }
        .logo-email { height:32px; width:auto; display:block; }
        .site-name { font-size:18px; font-weight:700; color:#fbff2b; margin:0; }
        .card-body { padding:20px; background:#191420 !important; }
        .sender-row { display:flex; align-items:flex-start; justify-content:space-between; gap:24px; margin-bottom:16px; }
        .sender-left { flex:1; }
        .sender-right { font-size:12px; color:rgba(255,255,255,0.6); text-align:right; white-space:nowrap; }
        .section-title { font-size:13px; text-transform:uppercase; letter-spacing:0.08em; color:rgba(255,255,255,0.6); margin:0 0 8px; }
        .field-row { margin-bottom:10px; font-size:14px; }
        .field-label { font-weight:600; color:#fbff2b; }
        .field-value { color:rgba(255,255,255,0.88); }
        .message-box { margin-top:6px; padding:14px 16px; background:#16111f !important; border-radius:8px; border-left:4px solid #fbff2b; }
        .card-footer { padding:10px 20px 14px; font-size:11px; color:rgba(255,255,255,0.5); border-top:1px solid rgba(255,255,255,0.06); text-align:center; background:#191420 !important; }
        a { color:#fbff2b; }
      </style>
    </head>
    <body style=\"background:#262036;\">
      <div class=\"outer\" style=\"background:#262036;\">
        <div class=\"card\" style=\"background:#191420;\">
          <div class=\"card-header\" style=\"background:#191420;\">
            <div>{$logoHtml}</div>
            <p class=\"site-name\">Kyle Devs</p>
          </div>

          <div class=\"card-body\" style=\"background:#191420;\">
            <div class=\"sender-row\">
              <div class=\"sender-left\">
                <p class=\"section-title\">Sender details</p>
                <div class=\"field-row\"><span class=\"field-label\">Name:</span> <span class=\"field-value\">{$name}</span></div>
                <div class=\"field-row\"><span class=\"field-label\">Email:</span> <span class=\"field-value\">{$email}</span></div>
                <div class=\"field-row\"><span class=\"field-label\">Subject:</span> <span class=\"field-value\">{$subject}</span></div>
              </div>
              <div class=\"sender-right\">
                <div>From: {$name}</div>
                <div style=\"font-size:11px;margin-top:4px;\">{$dateStr}</div>
              </div>
            </div>

            <p class=\"section-title\" style=\"margin-top:18px;\">Message</p>
            <div class=\"message-box\" style=\"background:#16111f;\">{$messageHtml}</div>
          </div>

          <div class=\"card-footer\" style=\"background:#191420;\">
            Sent from <a href=\"https://kyledevs.com\" target=\"_blank\" rel=\"noopener noreferrer\">kyledevs.com</a>
          </div>
        </div>
      </div>
    </body>
    </html>
    ";
    $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();
    sendResponse(true, $isAjax);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/email_errors.log', date('Y-m-d H:i:s') . " - " . $mail->ErrorInfo . "\n", FILE_APPEND);
    sendResponse(false, $isAjax);
}
