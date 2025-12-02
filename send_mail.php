<?php
// send_mail.php
// Helper function to send email. Uses a local PHPMailer copy if present
// (e.g. you downloaded the PHPMailer src into `PHPMailer/src`). Falls back
// to PHP's mail() if PHPMailer is not available or sending fails.
// Import classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_email($to, $subject, $body, $altBody = '') {
  // Try to use local PHPMailer (no Composer)
  $phpMailerSrc = __DIR__ . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
  $hasPHPMailer = file_exists($phpMailerSrc . 'PHPMailer.php') && file_exists($phpMailerSrc . 'SMTP.php') && file_exists($phpMailerSrc . 'Exception.php');

  if ($hasPHPMailer) {
    require_once $phpMailerSrc . 'Exception.php';
    require_once $phpMailerSrc . 'PHPMailer.php';
    require_once $phpMailerSrc . 'SMTP.php';

    $mail = new PHPMailer(true);
    try {
      // SMTP configuration - PLEASE ADJUST to your SMTP server
      // Replace these with your SMTP host, username and password
      $smtpHost = 'smtp.qq.com';
      $smtpPort = 587;
      $smtpUser = '2636499039@qq.com';
      $smtpPass = 'kjpeewkyhezueaji';
      $fromEmail = '2636499039@qq.com';
      $fromName = 'Auction Site';

      $mail->isSMTP();
      $mail->Host       = $smtpHost;
      $mail->SMTPAuth   = true;
      $mail->Username   = $smtpUser;
      $mail->Password   = $smtpPass;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port       = $smtpPort;

      $mail->setFrom($fromEmail, $fromName);
      $mail->addAddress($to);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body    = $body;
      if ($altBody) $mail->AltBody = $altBody;

      $mail->send();
      return true;
    } catch (Exception $e) {
      error_log('PHPMailer error: ' . $e->getMessage());
      // Fall through to mail() fallback
    }
  }

  // Fallback to PHP mail()
  $headers = "MIME-Version: 1.0\r\n" .
             "Content-type: text/html; charset=UTF-8\r\n" .
             "From: Auction Site <no-reply@example.com>\r\n";
  return mail($to, $subject, $body, $headers);
}
