<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php';

$sql = "SELECT
    pi.processed_image_id,
    pi.image_id,
    pi.processing_type,
    pi.processed_path,
    pi.processed_at,
    d.name as processed_by_name,
    i.image_path as original_image_path,
    i.description as original_description,
    p.name as patient_name,
    doc.name as doctor_name,
    dep.department_name,
    sub.subcategory_name
FROM
    ProcessedImages pi
LEFT JOIN Images i ON pi.image_id = i.image_id
LEFT JOIN Patients p ON i.patient_id = p.patient_id
LEFT JOIN Doctors doc ON i.doctor_id = doc.doctor_id
LEFT JOIN Departments dep ON i.department_id = dep.department_id
LEFT JOIN Subcategories sub ON i.subcategory_id = sub.subcategory_id
LEFT JOIN Doctors d ON pi.processed_by = d.doctor_id
ORDER BY pi.processed_at DESC";

$result = $conn->query($sql);
$processed_images = array();
if ($result && $result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $processed_images[] = $row;
  }
}

$conn->close();
echo json_encode($processed_images);
?> 