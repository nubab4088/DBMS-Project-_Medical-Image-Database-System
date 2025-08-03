<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $result = $conn->query("SELECT id, name, description FROM dashboard2 ORDER BY id DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    if (!$name || !$description) {
        echo json_encode(['error' => 'Name and description required']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO dashboard2 (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    if (!$id || !$name || !$description) {
        echo json_encode(['error' => 'ID, name, and description required']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE dashboard2 SET name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'ID required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM dashboard2 WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
$conn->close(); 