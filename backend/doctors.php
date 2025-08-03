<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php';

// Joining with Departments to get department name
$sql = "SELECT d.doctor_id, d.name, d.email, dep.department_name 
        FROM Doctors d
        JOIN Departments dep ON d.department_id = dep.department_id";
$result = $conn->query($sql);

$doctors = array();
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $doctors[] = $row;
  }
}

$conn->close();

echo json_encode($doctors);
?> 