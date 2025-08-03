
<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'localhost';      // MailHog SMTP
    $mail->Port = 1025;             // MailHog default port
    $mail->SMTPAuth = false;        // No authentication for local MailHog

    // Recipients
    $mail->setFrom('test@mids.local', 'MIDS Test');
    $mail->addAddress('test@receiver.com'); // This can be anything — MailHog will catch it

    // Content
    $mail->Subject = 'Test Email from PHPMailer 🚀';
    $mail->Body    = "Hi Bably,\n\nThis is a test email sent via PHPMailer to MailHog.\nIf you're reading this, your setup is 🔥.\n\nLove,\nCPT ❤️";

    $mail->send();
    echo "✅ Test email sent successfully. Check MailHog at http://localhost:8025\n";
} catch (Exception $e) {
    echo "❌ Email could not be sent. Error: {$mail->ErrorInfo}\n";
}
