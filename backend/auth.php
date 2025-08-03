<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkAuth();
        break;
    case 'register':
        handleRegister();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleLogin() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }
    
    // For demo purposes, we'll use simple password matching
    // In production, you should use password_verify() with proper hashing
    $sql = "SELECT doctor_id, name, email, password_hash, access_level, department_id FROM Doctors WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $doctor = $result->fetch_assoc();
        
        // Use password_verify to check the password
        if (password_verify($password, $doctor['password_hash'])) {
            // Set session data
            $_SESSION['user_id'] = $doctor['doctor_id'];
            $_SESSION['user_name'] = $doctor['name'];
            $_SESSION['user_email'] = $doctor['email'];
            $_SESSION['user_role'] = $doctor['access_level'];
            $_SESSION['department_id'] = $doctor['department_id'];
            $_SESSION['logged_in'] = true;
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $doctor['doctor_id'],
                    'name' => $doctor['name'],
                    'email' => $doctor['email'],
                    'role' => $doctor['access_level'],
                    'department_id' => $doctor['department_id']
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Invalid password']);
        }
    } else {
        echo json_encode(['error' => 'User not found']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function checkAuth() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'],
                'department_id' => $_SESSION['department_id']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}

function handleRegister() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';
    $department_id = $input['department'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($role) || empty($department_id)) {
        echo json_encode(['error' => 'All fields are required']);
        return;
    }
    
    // Check for duplicate email
    $stmt = $conn->prepare("SELECT doctor_id FROM Doctors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['error' => 'Email already registered']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');
    
    // Insert new doctor/admin
    $sql = "INSERT INTO Doctors (name, email, password_hash, access_level, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $email, $password_hash, $role, $department_id, $created_at);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['error' => 'Registration failed']);
    }
    $stmt->close();
}

$conn->close();
?> 