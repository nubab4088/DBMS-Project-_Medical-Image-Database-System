<?php
require 'backend/db.php';

$name = $email = $role = $department = '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $department = $_POST['department'] ?? '';

    // Validate
    if (!$name || !$email || !$password || !$confirm_password || !$role || !$department) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');
            // Insert
            $sql = "INSERT INTO Doctors (name, email, password_hash, access_level, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("ssssss", $name, $email, $password_hash, $role, $department, $created_at);
            if ($stmt2->execute()) {
                $success = 'Registration successful! You can now log in.';
                $name = $email = $role = $department = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MIDS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1a365d 0%, #4a90e2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.18);
            padding: 40px 32px;
            width: 100%;
            max-width: 400px;
        }
        .register-container h2 {
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 13px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #4a90e2;
            outline: none;
        }
        .register-btn {
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
        .register-btn:hover {
            background: linear-gradient(135deg, #357abd 0%, #2d5a87 100%);
        }
        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 15px;
            text-align: center;
        }
        .success {
            background: #e6ffed;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Create Account</h2>
        <?php if ($success): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Create a password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter your password">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Doctor" <?php if ($role=="Doctor") echo 'selected'; ?>>Doctor</option>
                    <option value="Admin" <?php if ($role=="Admin") echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department" required>
                    <option value="">Select Department</option>
                    <option value="1" <?php if ($department=="1") echo 'selected'; ?>>X-ray</option>
                    <option value="2" <?php if ($department=="2") echo 'selected'; ?>>CT Scan</option>
                    <option value="3" <?php if ($department=="3") echo 'selected'; ?>>MRI</option>
                    <option value="4" <?php if ($department=="4") echo 'selected'; ?>>Ultrasound</option>
                    <option value="5" <?php if ($department=="5") echo 'selected'; ?>>Dentistry</option>
                    <option value="6" <?php if ($department=="6") echo 'selected'; ?>>Ophthalmology</option>
                </select>
            </div>
            <button type="submit" class="register-btn">Register</button>
        </form>
    </div>
</body>
</html> 