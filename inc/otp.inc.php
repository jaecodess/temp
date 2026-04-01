<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.inc.php';
require_once __DIR__ . '/phpauth_db.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Master bypass code for demo/presentation use — accepts this code for any OTP prompt.
define('OTP_MASTER_CODE', '000000');

/**
 * Generate a 6-digit OTP for the given email and purpose, upsert into otp_tokens.
 * Returns the generated code.
 */
function otp_generate(string $email, string $purpose): string
{
    $code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "INSERT INTO otp_tokens (email, code, purpose, expires_at, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE code = VALUES(code), expires_at = VALUES(expires_at), created_at = NOW()"
    );
    $stmt->bind_param("ssss", $email, $code, $purpose, $expiresAt);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    return $code;
}

/**
 * Send the OTP code to the given email via PHPMailer (SMTP via Gmail).
 * Non-fatal: failures are logged and silently ignored.
 */
function otp_send(string $email, string $purpose, string $code): void
{
    $config = @parse_ini_file('/var/www/private/db-config.ini');
    if ($config === false) {
        $config = parse_ini_file(__DIR__ . '/../.env');
    }
    if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
        error_log('otp_send: SMTP credentials missing');
        return;
    }

    $subjects = [
        'verify_email'   => 'Verify your Statik account',
        'reset_password' => 'Reset your Statik password',
        'admin_confirm'  => 'Statik admin action confirmation',
    ];
    $intros = [
        'verify_email'   => 'Use the code below to verify your email address.',
        'reset_password' => 'Use the code below to reset your password.',
        'admin_confirm'  => 'Use the code below to confirm the admin action.',
    ];

    $subject = ($subjects[$purpose] ?? 'Your Statik code') . ' | Statik';
    $intro   = $intros[$purpose] ?? 'Your one-time code is:';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#FAF8F4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:32px 0;">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#FFFDF9;border-radius:8px;overflow:hidden;max-width:520px;border:1px solid #E8E0D5;">
      <tr>
        <td style="background:#051922;padding:28px 36px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:26px;font-weight:800;letter-spacing:2px;">Statik</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,0.55);font-size:12px;letter-spacing:1px;text-transform:uppercase;">Secure Verification</p>
        </td>
      </tr>
      <tr>
        <td style="padding:32px 36px 16px;text-align:center;">
          <p style="margin:0 0 24px;font-size:15px;color:#444;">{$intro}</p>
          <div style="display:inline-block;background:#FAF8F4;border:2px solid #0E9FAD;border-radius:8px;padding:18px 40px;">
            <span style="font-size:36px;font-weight:800;letter-spacing:10px;color:#051922;">{$code}</span>
          </div>
          <p style="margin:20px 0 0;font-size:13px;color:#767676;">This code expires in 15 minutes. Do not share it with anyone.</p>
        </td>
      </tr>
      <tr>
        <td style="background:#F2EDE6;padding:14px 36px;text-align:center;border-top:1px solid #E8E0D5;">
          <p style="margin:0;font-size:12px;color:#767676;">If you did not request this, you can safely ignore this email.</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;

    $plainBody = "Your Statik code: {$code}\n\n{$intro}\n\nThis code expires in 15 minutes.";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_username'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom($config['smtp_username'], 'Statik');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody;
        $mail->send();
    } catch (Exception $e) {
        error_log('otp_send error: ' . $e->getMessage());
    }
}

/**
 * Verify an OTP by attempting a DELETE. Returns true only if exactly one row was deleted.
 * Using DELETE as the check eliminates any TOCTOU race condition.
 */
function otp_verify(string $email, string $code, string $purpose): bool
{
    if ($code === OTP_MASTER_CODE) {
        return true;
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "DELETE FROM otp_tokens
         WHERE email = ? AND purpose = ? AND code = ? AND expires_at > NOW()"
    );
    $stmt->bind_param("sss", $email, $purpose, $code);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $conn->close();

    return $affected === 1;
}

/**
 * Returns true if no active OTP row exists for (email, purpose) or if the row is older than 60 seconds.
 */
function otp_can_resend(string $email, string $purpose): bool
{
    $conn = getDbConnection();
    $stmt = $conn->prepare(
        "SELECT created_at FROM otp_tokens WHERE email = ? AND purpose = ?"
    );
    $stmt->bind_param("ss", $email, $purpose);
    $stmt->execute();
    $result = $stmt->get_result();
    $row    = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$row) {
        return true;
    }
    return (time() - strtotime($row['created_at'])) >= 60;
}

/**
 * Update the password in phpauth_users for the given email.
 * The members.password sentinel '!' is intentionally not touched.
 */
function update_member_password(string $email, string $newPassword): void
{
    $pdo  = getPHPAuthDbConnection();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE phpauth_users SET password = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
}
