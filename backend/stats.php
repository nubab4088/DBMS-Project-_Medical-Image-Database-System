<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php';

$stats = [];

// Get image count
$result = $conn->query("SELECT COUNT(*) as count FROM Images");
$stats['image_count'] = $result->fetch_assoc()['count'];

// Get patient count
$result = $conn->query("SELECT COUNT(*) as count FROM Patients");
$stats['patient_count'] = $result->fetch_assoc()['count'];

// Get doctor count
$result = $conn->query("SELECT COUNT(*) as count FROM Doctors");
$stats['doctor_count'] = $result->fetch_assoc()['count'];

$conn->close();

echo json_encode($stats);
?> 