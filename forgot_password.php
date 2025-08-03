<?php
date_default_timezone_set('Asia/Dhaka');
require 'backend/db.php';
require 'vendor/autoload.php';  // PHPMailer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $message = '<div class="error">Please enter your email address.</div>';
    } else {
        // Check if email exists in Doctors
        $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Delete old tokens for this email
            $del = $conn->prepare("DELETE FROM PasswordResets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();
            $del->close();

            // Insert new token
            $ins = $conn->prepare("INSERT INTO PasswordResets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token, $expires_at);
            $ins->execute();
            $ins->close();

            // Send reset email using PHPMailer
            $reset_link = 'http://localhost/DBMS_Project_MIDS/reset_password.php?token=' . urlencode($token);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'localhost';
                $mail->Port = 1025;
                $mail->SMTPAuth = false;

                $mail->setFrom('no-reply@mids.local', 'MIDS');
                $mail->addAddress($email);
                $mail->Subject = 'MIDS Password Reset';

                // You can switch to isHTML(true) and use the HTML version if you want.
                $mail->Body = "Click the following link to reset your password:\n\n$reset_link\n\nIf you did not request this, ignore this email.";

                $mail->send();
            } catch (Exception $e) {
                error_log("Mail error: " . $mail->ErrorInfo);
            }
        }
        $stmt->close();

        // Always show generic message for security
        $message = '<div class="success">If this email exists in our system, a password reset link has been sent.</div>';
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - MIDS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1a365d 0%, #4a90e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .forgot-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            padding: 40px 32px;
            width: 100%;
            max-width: 400px;
        }
        .forgot-container h2 {
            text-align: center;
            color: #1a365d;
            margin-bottom: 24px;
            font-size: 2rem;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.2s;
        }
        .form-group input:focus {
            border-color: #4a90e2;
            outline: none;
        }
        .forgot-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .forgot-btn:hover {
            background: linear-gradient(135deg, #357abd 0%, #2d5a87 100%);
        }
        .success {
            background: #e6ffed;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 15px;
            text-align: center;
        }
        .error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Forgot Password</h2>
        <?php if ($message) echo $message; ?>
        <form action="forgot_password.php" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($email); ?>"
                />
            </div>
            <button type="submit" class="forgot-btn">Send Reset Link</button>
        </form>
    </div>
</body>
</html>
