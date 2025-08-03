<?php
session_start();
// Optionally, check if user is logged in and is admin
// if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.html'); exit; }
require 'backend/db.php';
// Fetch stats for cards
$total_patients = $conn->query("SELECT COUNT(*) as c FROM Patients")->fetch_assoc()['c'];
$total_doctors = $conn->query("SELECT COUNT(*) as c FROM Doctors")->fetch_assoc()['c'];
$total_images = $conn->query("SELECT COUNT(*) as c FROM Images")->fetch_assoc()['c'];
$total_departments = $conn->query("SELECT COUNT(*) as c FROM Departments")->fetch_assoc()['c'];
$active_patients = $conn->query("SELECT COUNT(DISTINCT patient_id) as c FROM Images WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['c'];
$completed_patients = $conn->query("SELECT COUNT(DISTINCT patient_id) as c FROM Images WHERE upload_date < DATE_SUB(NOW(), INTERVAL 30 DAY) AND patient_id NOT IN (SELECT patient_id FROM Images WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY))")->fetch_assoc()['c'];
$avg_treatment_days = $conn->query("SELECT ROUND(AVG(DATEDIFF(i.upload_date, p.created_at)), 1) as avg_days FROM Images i LEFT JOIN Patients p ON i.patient_id = p.patient_id")->fetch_assoc()['avg_days'] ?? 'N/A';
// Recent activity (last 5)
$recent_activity = $conn->query("
    SELECT d.name as doctor_name, COUNT(DISTINCT i.patient_id) as patient_count, dep.department_name, MAX(i.upload_date) as last_activity
    FROM Doctors d
    JOIN Images i ON d.doctor_id = i.doctor_id
    JOIN Departments dep ON i.department_id = dep.department_id
    GROUP BY d.doctor_id, dep.department_id
    ORDER BY last_activity DESC
    LIMIT 5
");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MIDS</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f4f7fa;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.12);
            padding: 32px 28px;
        }
        h1 {
            text-align: center;
            margin-bottom: 32px;
        }
        .add-btn {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            padding: 12px 32px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .add-btn:hover {
            background: linear-gradient(135deg, #357abd 0%, #2d5a87 100%);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        .activity-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-info {
            flex: 1;
        }
        .activity-stats {
            text-align: right;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Admin Dashboard</h1>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-patients"><?php echo $total_patients; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-doctors"><?php echo $total_doctors; ?></div>
                <div class="stat-label">Total Doctors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-images"><?php echo $total_images; ?></div>
                <div class="stat-label">Total Images</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-departments"><?php echo $total_departments; ?></div>
                <div class="stat-label">Departments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="active-patients"><?php echo $active_patients; ?></div>
                <div class="stat-label">Active Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="completed-patients"><?php echo $completed_patients; ?></div>
                <div class="stat-label">Completed Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="avg-treatment-days"><?php echo $avg_treatment_days; ?></div>
                <div class="stat-label">Avg Treatment Days</div>
            </div>
        </div>
        <div class="chart-grid">
            <div class="chart-container">
                <div class="chart-title">Monthly Activity Trends</div>
                <canvas id="overviewMonthlyChart" height="120"></canvas>
            </div>
            <div class="chart-container">
                <div class="chart-title">Department Distribution</div>
                <canvas id="overviewDeptChart" height="120"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <div class="chart-title">Recent Activity</div>
            <div id="recent-activity">
                <?php while($row = $recent_activity->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <strong><?php echo htmlspecialchars($row['doctor_name']); ?></strong> treated <strong><?php echo $row['patient_count']; ?></strong> patients in <?php echo htmlspecialchars($row['department_name']); ?>
                    </div>
                    <div class="activity-stats">
                        <small><?php echo htmlspecialchars($row['last_activity']); ?></small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <script>
    // Example dummy data for charts (replace with AJAX if you want live data)
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const imagesUploaded = [12, 19, 3, 5, 2, 3, 7, 8, 6, 10, 12, 9];
    const patientsTreated = [5, 8, 2, 4, 1, 2, 4, 5, 3, 6, 7, 5];
    const deptNames = ["X-ray", "CT Scan", "MRI", "Ultrasound", "Dentistry", "Ophthalmology"];
    const deptPatients = [30, 25, 20, 15, 10, 5];
    // Monthly Activity Trends Chart
    new Chart(document.getElementById('overviewMonthlyChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Images Uploaded',
                    data: imagesUploaded,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Patients Treated',
                    data: patientsTreated,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
    // Department Distribution Chart
    new Chart(document.getElementById('overviewDeptChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: deptNames,
            datasets: [
                {
                    label: 'Patients Treated',
                    data: deptPatients,
                    backgroundColor: 'rgba(0, 123, 255, 0.6)'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    </script>
</body>
</html> 