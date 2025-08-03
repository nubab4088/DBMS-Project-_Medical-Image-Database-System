<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'list') {
        $result = $conn->query("SELECT doctor_id, name, email, access_level, department_id FROM Doctors");
        $doctors = [];
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        echo json_encode($doctors);
    } elseif ($action === 'add') {
        $data = json_decode(file_get_contents('php://input'), true);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';
        $department = $data['department_id'] ?? '';
        if (!$name || !$email || !$password || !$role || !$department) {
            echo json_encode(['error' => 'All fields required']);
            exit;
        }
        $access_level = ($role === 'Admin') ? 'Admin' : 'User';
        
        // Generate doctor_id in format D + department_id (2 digits) + sequence (3 digits)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Doctors WHERE department_id = ?");
        $stmt->bind_param("i", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'] + 1;
        $stmt->close();
        
        $doctor_id = 'D' . str_pad($department, 2, '0', STR_PAD_LEFT) . str_pad($count, 3, '0', STR_PAD_LEFT);
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Doctors (doctor_id, name, email, password_hash, access_level, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssi", $doctor_id, $name, $email, $password_hash, $access_level, $department);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
    } elseif ($action === 'edit') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['doctor_id'] ?? '';
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $role = $data['role'] ?? '';
        $department = $data['department_id'] ?? '';
        $password = $data['password'] ?? '';
        if (!$id || !$name || !$email || !$role || !$department) {
            echo json_encode(['error' => 'All fields required except password']);
            exit;
        }
        $access_level = ($role === 'Admin') ? 'Admin' : 'User';
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE Doctors SET name=?, email=?, password_hash=?, access_level=?, department_id=? WHERE doctor_id=?");
            $stmt->bind_param("ssssis", $name, $email, $password_hash, $access_level, $department, $id);
        } else {
            $stmt = $conn->prepare("UPDATE Doctors SET name=?, email=?, access_level=?, department_id=? WHERE doctor_id=?");
            $stmt->bind_param("sssis", $name, $email, $access_level, $department, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
    } elseif ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['doctor_id'] ?? '';
        if (!$id) {
            echo json_encode(['error' => 'ID required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM Doctors WHERE doctor_id=?");
        $stmt->bind_param("s", $id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close(); 