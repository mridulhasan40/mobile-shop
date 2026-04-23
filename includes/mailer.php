<?php
/**
 * Mailer Helper
 * Uses PHP mail() by default.
 * To use SMTP (Gmail, Mailgun etc.), install PHPMailer:
 *   composer require phpmailer/phpmailer
 * Then uncomment the PHPMailer section below and configure credentials.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Send an email
 *
 * @param string $to      Recipient email
 * @param string $subject Email subject
 * @param string $body    HTML body
 * @return bool
 */
function sendMail($to, $subject, $body) {
    $fromName  = SITE_NAME;
    $fromEmail = MAIL_FROM;

    // ── Option A: PHP mail() ────────────────────────────────────────────────
    // Works on most shared hosts. For XAMPP locally, configure sendmail.
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $result = mail($to, $subject, $body, $headers);
    if ($result) return true;

    // ── Fallback: Log to file (useful for local dev) ────────────────────────
    logMailToFile($to, $subject, $body);
    return false;
}

/**
 * Log email to a local file (for development without SMTP)
 */
function logMailToFile($to, $subject, $body) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);

    $entry = "[" . date('Y-m-d H:i:s') . "] TO: {$to} | SUBJECT: {$subject}\n{$body}\n" . str_repeat('-', 80) . "\n\n";
    file_put_contents($logDir . 'mail.log', $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email, $token) {
    $resetUrl = SITE_URL . '/pages/reset-password.php?token=' . urlencode($token);
    $subject  = SITE_NAME . ' — Password Reset Request';
    $body     = getEmailTemplate('Password Reset', "
        <p>Hello,</p>
        <p>We received a request to reset your password for your <strong>" . SITE_NAME . "</strong> account.</p>
        <p style='text-align:center;margin:30px 0;'>
            <a href='{$resetUrl}' style='background:#0ea5e9;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:16px;'>
                Reset My Password
            </a>
        </p>
        <p>Or copy this link into your browser:</p>
        <p style='color:#64748b;font-size:13px;word-break:break-all;'>{$resetUrl}</p>
        <p>This link expires in <strong>1 hour</strong>. If you didn't request a reset, you can safely ignore this email.</p>
    ");
    return sendMail($email, $subject, $body);
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($email, $userName, $orderId, $orderTotal) {
    $orderUrl = SITE_URL . '/pages/orders.php?id=' . $orderId;
    $subject  = SITE_NAME . ' — Order #' . $orderId . ' Confirmed!';
    $body     = getEmailTemplate('Order Confirmed 🎉', "
        <p>Hi <strong>{$userName}</strong>,</p>
        <p>Thank you for your order! We're getting it ready for you.</p>
        <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
            <tr><td style='padding:10px;background:#f8fafc;border-radius:6px;'>
                <strong>Order ID:</strong> #{$orderId}<br>
                <strong>Total:</strong> " . formatPrice($orderTotal) . "
            </td></tr>
        </table>
        <p style='text-align:center;margin:30px 0;'>
            <a href='{$orderUrl}' style='background:#0ea5e9;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:16px;'>
                Track My Order
            </a>
        </p>
        <p>We will notify you when your order ships. Thank you for shopping with us!</p>
    ");
    return sendMail($email, $subject, $body);
}

/**
 * Generate a styled HTML email template
 */
function getEmailTemplate($title, $content) {
    return "<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>{$title}</title></head>
<body style='margin:0;padding:0;background:#f1f5f9;font-family:Inter,Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center' style='padding:40px 20px;'>
      <table width='600' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,.05);'>
        <tr><td style='background:linear-gradient(135deg,#0ea5e9,#6366f1);padding:30px;text-align:center;'>
          <h1 style='color:#fff;margin:0;font-size:24px;'>" . SITE_NAME . "</h1>
          <p style='color:rgba(255,255,255,.85);margin:8px 0 0;font-size:14px;'>{$title}</p>
        </td></tr>
        <tr><td style='padding:40px;color:#334155;font-size:15px;line-height:1.7;'>
          {$content}
        </td></tr>
        <tr><td style='background:#f8fafc;padding:24px;text-align:center;color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;'>
          &copy; " . date('Y') . " " . SITE_NAME . " — All rights reserved.<br>
          <a href='" . SITE_URL . "' style='color:#0ea5e9;text-decoration:none;'>Visit our store</a>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";
}
