<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $result = $conn->query("SELECT subcategory_id, subcategory_name, department_id FROM Subcategories");
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    echo json_encode($subcategories);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['subcategory_name'] ?? '';
    $department_id = $data['department_id'] ?? '';
    if (!$name || !$department_id) {
        echo json_encode(['error' => 'Name and department required']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO Subcategories (subcategory_name, department_id) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $department_id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['subcategory_id'] ?? '';
    $name = $data['subcategory_name'] ?? '';
    $department_id = $data['department_id'] ?? '';
    if (!$id || !$name || !$department_id) {
        echo json_encode(['error' => 'ID, name, and department required']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE Subcategories SET subcategory_name=?, department_id=? WHERE subcategory_id=?");
    $stmt->bind_param("sii", $name, $department_id, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['subcategory_id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'ID required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM Subcategories WHERE subcategory_id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
$conn->close(); 