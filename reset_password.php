<?php
date_default_timezone_set('Asia/Dhaka');
require 'backend/db.php';
$token = $_GET['token'] ?? '';
$message = '';
$show_form = false;
$email = '';

if ($token) {
    $stmt = $conn->prepare("SELECT email, expires_at FROM PasswordResets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($email, $expires_at);
        $stmt->fetch();
        if (strtotime($expires_at) >= time()) {
            $show_form = true;
        } else {
            $message = '<div class="error">This reset link has expired. Please request a new one.</div>';
        }
    } else {
        $message = '<div class="error">Invalid or expired reset link.</div>';
    }
    $stmt->close();
} else {
    $message = '<div class="error">No reset token provided.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!$password || !$confirm_password) {
        $message = '<div class="error">Please enter and confirm your new password.</div>';
        $show_form = true;
    } elseif ($password !== $confirm_password) {
        $message = '<div class="error">Passwords do not match.</div>';
        $show_form = true;
    } else {
        // Check token again and get email and expiry (use PHP time for expiry check)
        $stmt = $conn->prepare("SELECT email, expires_at FROM PasswordResets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($email, $expires_at);
            $stmt->fetch();
            if (strtotime($expires_at) >= time()) {
                // Update password in Doctors
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE Doctors SET password_hash = ? WHERE email = ?");
                $upd->bind_param("ss", $password_hash, $email);
                if ($upd->execute()) {
                    // Delete token
                    $del = $conn->prepare("DELETE FROM PasswordResets WHERE token = ?");
                    $del->bind_param("s", $token);
                    $del->execute();
                    $del->close();
                    $token = '';
                    $message = '<div class="success">Your password has been reset successfully! You can now <a href="login.html">log in</a>.</div>';
                    $show_form = false;
                } else {
                    $message = '<div class="error">Failed to reset password. Please try again.</div>';
                    $show_form = true;
                }
                $upd->close();
            } else {
                $message = '<div class="error">This reset link has expired. Please request a new one.</div>';
                $show_form = false;
            }
            $stmt->close();
        } else {
            $message = '<div class="error">Invalid or expired reset link.</div>';
            $show_form = false;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MIDS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1a365d 0%, #4a90e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            padding: 40px 32px;
            width: 100%;
            max-width: 400px;
        }
        .reset-container h2 {
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
        .reset-btn {
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
        .reset-btn:hover {
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
    <div class="reset-container">
        <h2>Reset Password</h2>
        <?php if ($message) echo $message; ?>
        <?php if ($show_form): ?>
        <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="post">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter new password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter new password">
            </div>
            <button type="submit" class="reset-btn">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html> 