<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require 'db.php';

$sql = "
    SELECT 
        p.patient_id, 
        p.name, 
        p.gender,
        YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d')) as age,
        p.contact_info,
        d.name as doctor_name,
        dept.department_name,
        s.subcategory_name,
        COUNT(i.image_id) as image_count,
        MIN(i.upload_date) as first_visit,
        MAX(i.upload_date) as last_visit,
        CASE 
            WHEN MAX(i.upload_date) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Active'
            ELSE 'Completed'
        END as status
    FROM Patients p
    LEFT JOIN Images i ON p.patient_id = i.patient_id
    LEFT JOIN Doctors d ON i.doctor_id = d.doctor_id
    LEFT JOIN Departments dept ON i.department_id = dept.department_id
    LEFT JOIN Subcategories s ON i.subcategory_id = s.subcategory_id
    GROUP BY p.patient_id, p.name, p.gender, p.date_of_birth, p.contact_info, d.name, dept.department_name, s.subcategory_name
    ORDER BY p.patient_id
";

$result = $conn->query($sql);

$patients = array();
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $patients[] = $row;
  }
}

$conn->close();

echo json_encode($patients);
?> 