<?php
require 'backend/db.php';

echo "=== Testing Updated Analytics Functions ===\n\n";

// Test Treatment Analytics
echo "1. Testing Treatment Analytics:\n";
$result = $conn->query("
    SELECT 
        COUNT(DISTINCT i.patient_id) as total_patients,
        COUNT(DISTINCT i.doctor_id) as total_doctors,
        COUNT(DISTINCT i.department_id) as total_departments,
        ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_duration,
        ROUND(COUNT(DISTINCT i.patient_id) / COUNT(DISTINCT i.doctor_id), 1) as avg_patients_per_doctor,
        ROUND(COUNT(DISTINCT i.image_id) / COUNT(DISTINCT i.patient_id), 1) as avg_images_per_patient
    FROM Images i
    LEFT JOIN Patients p ON i.patient_id = p.patient_id
");
$metrics = $result->fetch_assoc();
echo "   - Total Patients: " . $metrics['total_patients'] . "\n";
echo "   - Total Doctors: " . $metrics['total_doctors'] . "\n";
echo "   - Total Departments: " . $metrics['total_departments'] . "\n";
echo "   - Avg Treatment Duration: " . ($metrics['avg_treatment_duration'] ?? 'N/A') . " days\n";
echo "   - Avg Patients per Doctor: " . ($metrics['avg_patients_per_doctor'] ?? 'N/A') . "\n";
echo "   - Avg Images per Patient: " . ($metrics['avg_images_per_patient'] ?? 'N/A') . "\n\n";

// Test Gender Demographics
echo "2. Testing Gender Demographics:\n";
$result = $conn->query("
    SELECT 
        p.gender,
        COUNT(DISTINCT p.patient_id) as patient_count,
        COUNT(DISTINCT i.image_id) as total_images,
        ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_age
    FROM Patients p
    LEFT JOIN Images i ON p.patient_id = i.patient_id
    WHERE p.gender IS NOT NULL
    GROUP BY p.gender
    ORDER BY patient_count DESC
");
while ($row = $result->fetch_assoc()) {
    echo "   - " . $row['gender'] . ": " . $row['patient_count'] . " patients, " . $row['total_images'] . " images, avg age " . ($row['avg_age'] ?? 'N/A') . "\n";
}
echo "\n";

// Test Department Performance
echo "3. Testing Department Performance:\n";
$result = $conn->query("
    SELECT 
        dept.department_name,
        COUNT(DISTINCT i.patient_id) as patients_treated,
        COUNT(DISTINCT i.doctor_id) as doctors_count,
        ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_patient_age,
        ROUND(COUNT(DISTINCT i.patient_id) / COUNT(DISTINCT i.doctor_id), 1) as patients_per_doctor
    FROM Departments dept
    LEFT JOIN Images i ON dept.department_id = i.department_id
    LEFT JOIN Patients p ON i.patient_id = p.patient_id
    GROUP BY dept.department_id, dept.department_name
    ORDER BY patients_treated DESC
    LIMIT 3
");
while ($row = $result->fetch_assoc()) {
    echo "   - " . $row['department_name'] . ": " . $row['patients_treated'] . " patients, " . $row['doctors_count'] . " doctors, avg age " . ($row['avg_patient_age'] ?? 'N/A') . ", " . $row['patients_per_doctor'] . " patients/doctor\n";
}
echo "\n";

// Test Doctor Performance
echo "4. Testing Doctor Performance:\n";
$result = $conn->query("
    SELECT 
        d.name as doctor_name,
        dept.department_name,
        COUNT(DISTINCT i.patient_id) as patients_treated,
        ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_patient_age,
        ROUND(COUNT(DISTINCT i.image_id) / COUNT(DISTINCT i.patient_id), 1) as images_per_patient,
        ROUND(COUNT(DISTINCT i.patient_id) / GREATEST(DATEDIFF(NOW(), d.created_at), 1), 2) as patients_per_day
    FROM Doctors d
    LEFT JOIN Departments dept ON d.department_id = dept.department_id
    LEFT JOIN Images i ON d.doctor_id = i.doctor_id
    LEFT JOIN Patients p ON i.patient_id = p.patient_id
    GROUP BY d.doctor_id, d.name, dept.department_name, d.created_at
    ORDER BY patients_treated DESC
    LIMIT 3
");
while ($row = $result->fetch_assoc()) {
    echo "   - " . $row['doctor_name'] . " (" . $row['department_name'] . "): " . $row['patients_treated'] . " patients, avg age " . ($row['avg_patient_age'] ?? 'N/A') . ", " . $row['images_per_patient'] . " images/patient, " . $row['patients_per_day'] . " patients/day\n";
}
echo "\n";

// Test Monthly Trends
echo "5. Testing Monthly Trends:\n";
$result = $conn->query("
    SELECT 
        DATE_FORMAT(upload_date, '%Y-%m') as month,
        COUNT(DISTINCT image_id) as images_uploaded,
        COUNT(DISTINCT patient_id) as patients_treated,
        COUNT(DISTINCT doctor_id) as doctors_active,
        COUNT(DISTINCT department_id) as departments_active
    FROM Images
    WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(upload_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 3
");
while ($row = $result->fetch_assoc()) {
    echo "   - " . $row['month'] . ": " . $row['images_uploaded'] . " images, " . $row['patients_treated'] . " patients, " . $row['doctors_active'] . " doctors, " . $row['departments_active'] . " departments\n";
}
echo "\n";

echo "=== All Analytics Tests Completed Successfully ===\n";
?> 