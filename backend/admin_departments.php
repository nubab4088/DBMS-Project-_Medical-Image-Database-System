<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $result = $conn->query("SELECT department_id, department_name FROM Departments");
    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    echo json_encode($departments);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['department_name'] ?? '';
    if (!$name) {
        echo json_encode(['error' => 'Name required']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO Departments (department_name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['department_id'] ?? '';
    $name = $data['department_name'] ?? '';
    if (!$id || !$name) {
        echo json_encode(['error' => 'ID and name required']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE Departments SET department_name=? WHERE department_id=?");
    $stmt->bind_param("si", $name, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['department_id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'ID required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM Departments WHERE department_id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
$conn->close(); 