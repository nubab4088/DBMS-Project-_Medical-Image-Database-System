<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php';

$sql = "SELECT
            i.image_id,
            i.image_path,
            i.description,
            p.name as patient_name,
            d.name as doctor_name,
            dep.department_name,
            sub.subcategory_name
        FROM
            Images i
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        LEFT JOIN Doctors d ON i.doctor_id = d.doctor_id
        LEFT JOIN Departments dep ON i.department_id = dep.department_id
        LEFT JOIN Subcategories sub ON i.subcategory_id = sub.subcategory_id
        ORDER BY i.upload_date DESC";
$result = $conn->query($sql);

$images = array();
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $images[] = $row;
  }
}

$conn->close();

echo json_encode($images);
?> 