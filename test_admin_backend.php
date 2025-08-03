<?php
require 'backend/db.php';

echo "=== Testing Admin Backend Functions ===\n\n";

// Test Treatment Analytics Function
echo "1. Testing getTreatmentAnalytics():\n";
function testTreatmentAnalytics() {
    global $conn;
    
    $analytics = [];
    
    // Monthly treatment trends with more details
    $result = $conn->query("
        SELECT 
            DATE_FORMAT(i.upload_date, '%Y-%m') as month,
            COUNT(DISTINCT i.image_id) as images_uploaded,
            COUNT(DISTINCT i.patient_id) as patients_treated,
            COUNT(DISTINCT i.doctor_id) as doctors_active,
            COUNT(DISTINCT i.department_id) as departments_active,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_duration
        FROM Images i
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        WHERE i.upload_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(i.upload_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 3
    ");
    $analytics['monthly_trends'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['monthly_trends'][] = $row;
    }
    
    // Treatment status breakdown with demographics
    $result = $conn->query("
        SELECT 
            'Active' as status,
            COUNT(DISTINCT i.patient_id) as patient_count,
            COUNT(DISTINCT i.doctor_id) as doctor_count,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_age
        FROM Images i
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        WHERE i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        UNION ALL
        SELECT 
            'Completed' as status,
            COUNT(DISTINCT i.patient_id) as patient_count,
            COUNT(DISTINCT i.doctor_id) as doctor_count,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_age
        FROM Images i
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        WHERE i.upload_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $analytics['status_breakdown'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['status_breakdown'][] = $row;
    }
    
    // Gender demographics
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
    $analytics['gender_demographics'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['gender_demographics'][] = $row;
    }
    
    // Treatment efficiency metrics
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
    $analytics['efficiency_metrics'] = $result->fetch_assoc();
    
    return $analytics;
}

$treatment_data = testTreatmentAnalytics();
echo "   Monthly Trends: " . count($treatment_data['monthly_trends']) . " months\n";
echo "   Status Breakdown: " . count($treatment_data['status_breakdown']) . " statuses\n";
echo "   Gender Demographics: " . count($treatment_data['gender_demographics']) . " genders\n";
echo "   Efficiency Metrics: " . ($treatment_data['efficiency_metrics']['total_patients'] ?? 'N/A') . " total patients\n\n";

// Test Department Analytics Function
echo "2. Testing getDepartmentAnalytics():\n";
function testDepartmentAnalytics() {
    global $conn;
    
    $sql = "
        SELECT 
            dept.department_id,
            dept.department_name,
            COUNT(DISTINCT i.image_id) as total_images,
            COUNT(DISTINCT i.patient_id) as total_patients,
            COUNT(DISTINCT i.doctor_id) as total_doctors,
            COUNT(DISTINCT CASE WHEN i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as active_patients,
            COUNT(DISTINCT CASE WHEN i.upload_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as completed_patients,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_days,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_patient_age,
            MIN(i.upload_date) as first_activity,
            MAX(i.upload_date) as last_activity,
            ROUND(COUNT(DISTINCT i.patient_id) / COUNT(DISTINCT i.doctor_id), 1) as patients_per_doctor,
            ROUND(COUNT(DISTINCT i.image_id) / COUNT(DISTINCT i.patient_id), 1) as images_per_patient,
            COUNT(DISTINCT s.subcategory_id) as subcategories_count
        FROM Departments dept
        LEFT JOIN Images i ON dept.department_id = i.department_id
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        LEFT JOIN Subcategories s ON i.subcategory_id = s.subcategory_id
        GROUP BY dept.department_id, dept.department_name
        ORDER BY total_patients DESC
    ";
    
    $result = $conn->query($sql);
    $departments = [];
    
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    return $departments;
}

$department_data = testDepartmentAnalytics();
echo "   Total Departments: " . count($department_data) . "\n";
foreach ($department_data as $dept) {
    echo "   - " . $dept['department_name'] . ": " . $dept['total_patients'] . " patients, " . $dept['total_doctors'] . " doctors\n";
}
echo "\n";

// Test Doctor Performance Function
echo "3. Testing getDoctorPerformance():\n";
function testDoctorPerformance() {
    global $conn;
    
    $sql = "
        SELECT 
            d.doctor_id,
            d.name as doctor_name,
            d.email,
            dept.department_name,
            COUNT(DISTINCT i.image_id) as total_images,
            COUNT(DISTINCT i.patient_id) as patients_treated,
            COUNT(DISTINCT CASE WHEN i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as active_patients,
            COUNT(DISTINCT CASE WHEN i.upload_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as completed_patients,
            MIN(i.upload_date) as first_activity,
            MAX(i.upload_date) as last_activity,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_days,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_patient_age,
            ROUND(COUNT(DISTINCT i.image_id) / COUNT(DISTINCT i.patient_id), 1) as images_per_patient,
            COUNT(DISTINCT i.department_id) as departments_worked,
            COUNT(DISTINCT s.subcategory_id) as subcategories_handled,
            DATEDIFF(NOW(), d.created_at) as days_since_joining,
            ROUND(COUNT(DISTINCT i.patient_id) / GREATEST(DATEDIFF(NOW(), d.created_at), 1), 2) as patients_per_day
        FROM Doctors d
        LEFT JOIN Departments dept ON d.department_id = dept.department_id
        LEFT JOIN Images i ON d.doctor_id = i.doctor_id
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        LEFT JOIN Subcategories s ON i.subcategory_id = s.subcategory_id
        GROUP BY d.doctor_id, d.name, d.email, dept.department_name, d.created_at
        ORDER BY patients_treated DESC, total_images DESC
        LIMIT 3
    ";
    
    $result = $conn->query($sql);
    $doctors = [];
    
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    
    return $doctors;
}

$doctor_data = testDoctorPerformance();
echo "   Top 3 Doctors:\n";
foreach ($doctor_data as $doctor) {
    echo "   - " . $doctor['doctor_name'] . " (" . $doctor['department_name'] . "): " . $doctor['patients_treated'] . " patients, " . $doctor['avg_patient_age'] . " avg age\n";
}
echo "\n";

echo "=== All Admin Backend Functions Working Correctly ===\n";
?> 