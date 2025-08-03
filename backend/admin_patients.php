<?php
require 'db.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $result = $conn->query("SELECT patient_id, name, date_of_birth, gender, contact_info, treatment_status FROM Patients");
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    echo json_encode($patients);
} elseif ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $date_of_birth = $data['date_of_birth'] ?? '';
    $gender = $data['gender'] ?? '';
    $contact_info = $data['contact_info'] ?? '';
    $treatment_status = $data['treatment_status'] ?? 'Running';
    if (!$name || !$date_of_birth || !$gender) {
        echo json_encode(['error' => 'Name, date of birth, and gender are required']);
        exit;
    }
    // Generate patient_id (e.g., P + timestamp)
    $patient_id = 'P' . date('ymdHis') . rand(10,99);
    $stmt = $conn->prepare("INSERT INTO Patients (patient_id, name, date_of_birth, gender, contact_info, treatment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $patient_id, $name, $date_of_birth, $gender, $contact_info, $treatment_status);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'edit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['patient_id'] ?? '';
    $name = $data['name'] ?? '';
    $date_of_birth = $data['date_of_birth'] ?? '';
    $gender = $data['gender'] ?? '';
    $contact_info = $data['contact_info'] ?? '';
    $treatment_status = $data['treatment_status'] ?? 'Running';
    if (!$id || !$name || !$date_of_birth || !$gender) {
        echo json_encode(['error' => 'ID, name, date of birth, and gender are required']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE Patients SET name=?, date_of_birth=?, gender=?, contact_info=?, treatment_status=? WHERE patient_id=?");
    $stmt->bind_param("ssssss", $name, $date_of_birth, $gender, $contact_info, $treatment_status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} elseif ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['patient_id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'ID required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM Patients WHERE patient_id=?");
    $stmt->bind_param("s", $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['error' => 'Invalid action']);
}
$conn->close(); 