<?php
require 'db.php';

// Temporarily disable authentication for testing
// session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     echo json_encode(['error' => 'Unauthorized access. Admin privileges required.']);
//     exit;
// }

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'overview':
        getOverview();
        break;
    case 'patient_records':
        getPatientRecords();
        break;
    case 'doctor_performance':
        getDoctorPerformance();
        break;
    case 'treatment_analytics':
        getTreatmentAnalytics();
        break;
    case 'department_analytics':
        getDepartmentAnalytics();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getOverview() {
    global $conn;
    
    // Get basic stats
    $result = $conn->query("
        SELECT 
            COUNT(DISTINCT i.patient_id) as total_patients,
            COUNT(DISTINCT i.doctor_id) as total_doctors,
            COUNT(DISTINCT i.image_id) as total_images,
            COUNT(DISTINCT i.department_id) as total_departments,
            COUNT(DISTINCT CASE WHEN i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as active_patients,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_days
        FROM Images i
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
    ");
    $stats = $result->fetch_assoc();
    
    // Get recent activity
    $result = $conn->query("
        SELECT 
            d.name as doctor_name,
            dept.department_name,
            COUNT(DISTINCT i.patient_id) as patient_count,
            MAX(i.upload_date) as last_activity
        FROM Doctors d
        LEFT JOIN Departments dept ON d.department_id = dept.department_id
        LEFT JOIN Images i ON d.doctor_id = i.doctor_id
        GROUP BY d.doctor_id, d.name, dept.department_name
        ORDER BY last_activity DESC
        LIMIT 10
    ");
    $recent_activity = [];
    while ($row = $result->fetch_assoc()) {
        $recent_activity[] = $row;
    }
    
    echo json_encode([
        'total_patients' => $stats['total_patients'],
        'total_doctors' => $stats['total_doctors'],
        'total_images' => $stats['total_images'],
        'total_departments' => $stats['total_departments'],
        'active_patients' => $stats['active_patients'],
        'avg_treatment_days' => $stats['avg_treatment_days'],
        'recent_activity' => $recent_activity
    ]);
}

function getPatientRecords() {
    global $conn;
    
    $sql = "
        SELECT 
            p.patient_id,
            p.name,
            ROUND(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d')), 0) as age,
            p.gender,
            d.name as doctor_name,
            dept.department_name,
            COUNT(DISTINCT i.image_id) as image_count,
            MAX(i.upload_date) as last_activity
        FROM Patients p
        LEFT JOIN Images i ON p.patient_id = i.patient_id
        LEFT JOIN Doctors d ON i.doctor_id = d.doctor_id
        LEFT JOIN Departments dept ON i.department_id = dept.department_id
        GROUP BY p.patient_id, p.name, p.date_of_birth, p.gender, d.name, dept.department_name
        ORDER BY last_activity DESC
        LIMIT 50
    ";
    
    $result = $conn->query($sql);
    $patients = [];
    
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    
    echo json_encode($patients);
}

function getDoctorPerformance() {
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
    ";
    
    $result = $conn->query($sql);
    $doctors = [];
    
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    
    // Get additional performance metrics
    $performance_metrics = [];
    
    // Top performing doctors by different metrics
    $result = $conn->query("
        SELECT 
            d.name as doctor_name,
            COUNT(DISTINCT i.patient_id) as patients_treated
        FROM Doctors d
        LEFT JOIN Images i ON d.doctor_id = i.doctor_id
        GROUP BY d.doctor_id, d.name
        ORDER BY patients_treated DESC
        LIMIT 5
    ");
    $performance_metrics['top_by_patients'] = [];
    while ($row = $result->fetch_assoc()) {
        $performance_metrics['top_by_patients'][] = $row;
    }
    
    // Most efficient doctors (lowest avg treatment days)
    $result = $conn->query("
        SELECT 
            d.name as doctor_name,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_days
        FROM Doctors d
        LEFT JOIN Images i ON d.doctor_id = i.doctor_id
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        WHERE i.upload_date IS NOT NULL
        GROUP BY d.doctor_id, d.name
        HAVING avg_treatment_days > 0
        ORDER BY avg_treatment_days ASC
        LIMIT 5
    ");
    $performance_metrics['most_efficient'] = [];
    while ($row = $result->fetch_assoc()) {
        $performance_metrics['most_efficient'][] = $row;
    }
    
    // Gender distribution of patients by doctor
    $result = $conn->query("
        SELECT 
            d.name as doctor_name,
            p.gender,
            COUNT(DISTINCT p.patient_id) as patient_count
        FROM Doctors d
        LEFT JOIN Images i ON d.doctor_id = i.doctor_id
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        WHERE p.gender IS NOT NULL
        GROUP BY d.doctor_id, d.name, p.gender
        ORDER BY d.name, patient_count DESC
    ");
    $performance_metrics['gender_distribution'] = [];
    while ($row = $result->fetch_assoc()) {
        $performance_metrics['gender_distribution'][] = $row;
    }
    
    echo json_encode([
        'doctors' => $doctors,
        'performance_metrics' => $performance_metrics
    ]);
}

function getTreatmentAnalytics() {
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
    
    // Enhanced department performance with efficiency metrics
    $result = $conn->query("
        SELECT 
            dept.department_name,
            COUNT(DISTINCT i.image_id) as total_images,
            COUNT(DISTINCT i.patient_id) as patients_treated,
            COUNT(DISTINCT i.doctor_id) as doctors_count,
            COUNT(DISTINCT CASE WHEN i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as active_patients,
            COUNT(DISTINCT CASE WHEN i.upload_date < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN i.patient_id END) as completed_patients,
            ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_treatment_days,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_patient_age,
            MIN(i.upload_date) as first_activity,
            MAX(i.upload_date) as last_activity,
            ROUND(COUNT(DISTINCT i.patient_id) / COUNT(DISTINCT i.doctor_id), 1) as patients_per_doctor
        FROM Departments dept
        LEFT JOIN Images i ON dept.department_id = i.department_id
        LEFT JOIN Patients p ON i.patient_id = p.patient_id
        GROUP BY dept.department_id, dept.department_name
        ORDER BY patients_treated DESC
    ");
    $analytics['department_performance'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['department_performance'][] = $row;
    }
    
    // Patient demographics by gender
    $result = $conn->query("
        SELECT 
            p.gender,
            COUNT(DISTINCT p.patient_id) as patient_count,
            COUNT(DISTINCT i.image_id) as total_images,
            ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_age,
            COUNT(DISTINCT CASE WHEN i.upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN p.patient_id END) as active_patients
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
    
    echo json_encode($analytics);
}

function getDepartmentAnalytics() {
    global $conn;
    
    $department_id = $_GET['department_id'] ?? null;
    
    if ($department_id) {
        $where_clause = "WHERE dept.department_id = '$department_id'";
    } else {
        $where_clause = "";
    }
    
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
        $where_clause
        GROUP BY dept.department_id, dept.department_name
        ORDER BY total_patients DESC
    ";
    
    $result = $conn->query($sql);
    $departments = [];
    
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    // If specific department requested, get additional details
    if ($department_id) {
        $additional_data = [];
        
        // Gender distribution for this department
        $result = $conn->query("
            SELECT 
                p.gender,
                COUNT(DISTINCT p.patient_id) as patient_count,
                ROUND(AVG(YEAR(CURDATE()) - YEAR(p.date_of_birth) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(p.date_of_birth, '%m%d'))), 1) as avg_age
            FROM Images i
            LEFT JOIN Patients p ON i.patient_id = p.patient_id
            WHERE i.department_id = '$department_id' AND p.gender IS NOT NULL
            GROUP BY p.gender
            ORDER BY patient_count DESC
        ");
        $additional_data['gender_distribution'] = [];
        while ($row = $result->fetch_assoc()) {
            $additional_data['gender_distribution'][] = $row;
        }
        
        // Subcategory breakdown for this department
        $result = $conn->query("
            SELECT 
                s.subcategory_name,
                COUNT(DISTINCT i.image_id) as image_count,
                COUNT(DISTINCT i.patient_id) as patient_count,
                COUNT(DISTINCT i.doctor_id) as doctor_count
            FROM Images i
            LEFT JOIN Subcategories s ON i.subcategory_id = s.subcategory_id
            WHERE i.department_id = '$department_id'
            GROUP BY s.subcategory_id, s.subcategory_name
            ORDER BY patient_count DESC
        ");
        $additional_data['subcategory_breakdown'] = [];
        while ($row = $result->fetch_assoc()) {
            $additional_data['subcategory_breakdown'][] = $row;
        }
        
        // Monthly trends for this department
        $result = $conn->query("
            SELECT 
                DATE_FORMAT(upload_date, '%Y-%m') as month,
                COUNT(DISTINCT image_id) as images_uploaded,
                COUNT(DISTINCT patient_id) as patients_treated,
                COUNT(DISTINCT doctor_id) as doctors_active
            FROM Images
            WHERE department_id = '$department_id' AND upload_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(upload_date, '%Y-%m')
            ORDER BY month DESC
        ");
        $additional_data['monthly_trends'] = [];
        while ($row = $result->fetch_assoc()) {
            $additional_data['monthly_trends'][] = $row;
        }
        
        echo json_encode([
            'department' => $departments[0] ?? null,
            'additional_data' => $additional_data
        ]);
    } else {
        echo json_encode($departments);
    }
}
?> 