<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $result = $conn->query("SELECT image_id, image_path, patient_id, doctor_id, department_id, subcategory_id, description FROM Images");
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    echo json_encode($images);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $image_path = $data['image_path'] ?? '';
    $patient_id = $data['patient_id'] ?? '';
    $doctor_id = $data['doctor_id'] ?? '';
    $department_id = $data['department_id'] ?? '';
    $subcategory_id = $data['subcategory_id'] ?? '';
    $description = $data['description'] ?? '';
    if (!$image_path || !$patient_id || !$doctor_id || !$department_id || !$subcategory_id) {
        echo json_encode(['error' => 'All fields required']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO Images (image_path, patient_id, doctor_id, department_id, subcategory_id, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("siiiis", $image_path, $patient_id, $doctor_id, $department_id, $subcategory_id, $description);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['image_id'] ?? '';
    $image_path = $data['image_path'] ?? '';
    $patient_id = $data['patient_id'] ?? '';
    $doctor_id = $data['doctor_id'] ?? '';
    $department_id = $data['department_id'] ?? '';
    $subcategory_id = $data['subcategory_id'] ?? '';
    $description = $data['description'] ?? '';
    if (!$id || !$image_path || !$patient_id || !$doctor_id || !$department_id || !$subcategory_id) {
        echo json_encode(['error' => 'All fields required']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE Images SET image_path=?, patient_id=?, doctor_id=?, department_id=?, subcategory_id=?, description=? WHERE image_id=?");
    $stmt->bind_param("siiiisi", $image_path, $patient_id, $doctor_id, $department_id, $subcategory_id, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['image_id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'ID required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM Images WHERE image_id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
$conn->close(); 