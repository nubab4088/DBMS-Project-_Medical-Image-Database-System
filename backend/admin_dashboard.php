<?php
require 'db.php';

// Total patients
$total_patients = $conn->query("SELECT COUNT(*) as c FROM Patients")->fetch_assoc()['c'];

// Active patients (patients with an image uploaded in the last 30 days)
$active_patients = $conn->query("
    SELECT COUNT(DISTINCT patient_id) as c 
    FROM Images 
    WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['c'];

// Treated patients (patients with at least one image)
$treated_patients = $conn->query("
    SELECT COUNT(DISTINCT patient_id) as c 
    FROM Images
")->fetch_assoc()['c'];

// Total doctors
$total_doctors = $conn->query("SELECT COUNT(*) as c FROM Doctors")->fetch_assoc()['c'];

// Total images
$total_images = $conn->query("SELECT COUNT(*) as c FROM Images")->fetch_assoc()['c'];

// Total departments
$total_departments = $conn->query("SELECT COUNT(*) as c FROM Departments")->fetch_assoc()['c'];

// Recent active patients (last 5)
$recent_patients = $conn->query("
    SELECT p.patient_id, p.name, MAX(i.upload_date) as last_activity
    FROM Patients p
    JOIN Images i ON p.patient_id = i.patient_id
    GROUP BY p.patient_id, p.name
    ORDER BY last_activity DESC
    LIMIT 5
");

$conn->close();
?>
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_patients; ?></div>
        <div class="stat-label">Total Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $active_patients; ?></div>
        <div class="stat-label">Active Patients (30 days)</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $treated_patients; ?></div>
        <div class="stat-label">Treated Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_doctors; ?></div>
        <div class="stat-label">Total Doctors</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_images; ?></div>
        <div class="stat-label">Total Images</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $total_departments; ?></div>
        <div class="stat-label">Departments</div>
    </div>
</div>

<h3 style="margin-top:32px;">Recent Active Patients</h3>
<table class="dashboard-table">
    <thead>
        <tr>
            <th>Patient ID</th>
            <th>Name</th>
            <th>Last Activity</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $recent_patients->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['patient_id']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['last_activity']); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table> 