<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get weekly orders data
$weekly_query = "SELECT 
    DATE(pickup_date) as date,
    COUNT(*) as order_count
    FROM cart
    WHERE pickup_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(pickup_date)
    ORDER BY date";
$weekly_result = $conn->query($weekly_query);

// Get monthly orders data
$monthly_query = "SELECT 
    DATE_FORMAT(pickup_date, '%Y-%m') as month,
    COUNT(*) as order_count
    FROM cart
    WHERE pickup_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(pickup_date, '%Y-%m')
    ORDER BY month";
$monthly_result = $conn->query($monthly_query);

// Employee performance query
$employee_query = "SELECT 
    u.fullname as employee_name,
    e.employee_id,
    e.availability,
    COUNT(DISTINCT c.id) as total_pickups,
    COUNT(DISTINCT CASE WHEN c.pickup_status = 'Completed' THEN c.id END) as completed_pickups,
    COUNT(DISTINCT CASE WHEN c.pickup_status = 'Pending' THEN c.id END) as pending_pickups,
    COUNT(DISTINCT CASE WHEN c.pickup_status = 'Cancelled' THEN c.id END) as cancelled_pickups,
    ROUND(
        COUNT(DISTINCT CASE WHEN c.pickup_status = 'Completed' THEN c.id END) * 100.0 / 
        NULLIF(COUNT(DISTINCT c.id), 0),
        1
    ) as completion_rate,
    ROUND(
        AVG(CASE WHEN c.pickup_status = 'Completed' 
            THEN TIMESTAMPDIFF(HOUR, c.created_at, c.pickup_date) 
            END
        ), 1
    ) as avg_completion_time
    FROM employees e
    JOIN users u ON e.user_id = u.user_id
    LEFT JOIN cart c ON e.employee_id = c.assigned_employee_id
    WHERE u.role = 'Employee'
    GROUP BY e.employee_id, u.fullname, e.availability
    ORDER BY total_pickups DESC";
$employee_result = $conn->query($employee_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .container-fluid {
            margin-left: 280px;
            width: calc(100% - 280px);
            padding: 20px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .performance-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            height: 100%;
        }
        .metric {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .container-fluid {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="container-fluid">
        <h2 class="mb-4">Analytics Dashboard</h2>

        <!-- Order Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h4>Weekly Orders</h4>
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h4>Monthly Orders</h4>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Employee Performance -->
        <h3 class="mb-4">Employee Performance</h3>
        <div class="row">
            <?php
            if ($employee_result && $employee_result->num_rows > 0) {
                while ($emp = $employee_result->fetch_assoc()) {
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="performance-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><?php echo htmlspecialchars($emp['employee_name']); ?></h5>
                                <span class="status-badge <?php echo $emp['availability'] == 'Available' ? 'status-available' : 'status-unavailable'; ?>">
                                    <?php echo htmlspecialchars($emp['availability']); ?>
                                </span>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="small text-muted">Total Pickups</div>
                                    <div class="metric"><?php echo $emp['total_pickups']; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Completion Rate</div>
                                    <div class="metric"><?php echo $emp['completion_rate']; ?>%</div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="small text-muted">Completed</div>
                                    <div class="h5 text-success"><?php echo $emp['completed_pickups']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Pending</div>
                                    <div class="h5 text-warning"><?php echo $emp['pending_pickups']; ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Cancelled</div>
                                    <div class="h5 text-danger"><?php echo $emp['cancelled_pickups']; ?></div>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-top">
                                <div class="small text-muted">Average Completion Time</div>
                                <div class="h6">
                                    <?php 
                                    echo $emp['avg_completion_time'] 
                                        ? $emp['avg_completion_time'] . ' hours' 
                                        : 'N/A'; 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">No employee data available</div></div>';
            }
            ?>
        </div>

        <!-- Add this right after the Employee Performance section, before closing container-fluid div -->
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <h3>Export Reports</h3>
                <button class="btn btn-success me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
                <button class="btn btn-danger" onclick="generatePDF()">
                    <i class="fas fa-file-pdf me-2"></i>Export to PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        // Weekly Chart
        const weeklyData = {
            labels: [<?php 
                $labels = [];
                $counts = [];
                if ($weekly_result) {
                    while ($row = $weekly_result->fetch_assoc()) {
                        $labels[] = "'" . date('D', strtotime($row['date'])) . "'";
                        $counts[] = $row['order_count'];
                    }
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                label: 'Orders',
                data: [<?php echo implode(',', $counts); ?>],
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        };

        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: weeklyData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Monthly Chart
        const monthlyData = {
            labels: [<?php 
                $labels = [];
                $counts = [];
                if ($monthly_result) {
                    while ($row = $monthly_result->fetch_assoc()) {
                        $labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                        $counts[] = $row['order_count'];
                    }
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                label: 'Orders',
                data: [<?php echo implode(',', $counts); ?>],
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1,
                tension: 0.4
            }]
        };

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: monthlyData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        function exportToExcel() {
            // Prepare data for Excel
            const data = [];
            
            // Add headers
            data.push([
                'Employee Name',
                'Availability',
                'Total Pickups',
                'Completed Pickups',
                'Pending Pickups',
                'Cancelled Pickups',
                'Completion Rate',
                'Avg Completion Time'
            ]);

            // Get employee data
            <?php
            if ($employee_result) {
                $employee_result->data_seek(0); // Reset result pointer
                while ($emp = $employee_result->fetch_assoc()) {
                    echo "data.push([
                        '" . addslashes($emp['employee_name']) . "',
                        '" . $emp['availability'] . "',
                        " . $emp['total_pickups'] . ",
                        " . $emp['completed_pickups'] . ",
                        " . $emp['pending_pickups'] . ",
                        " . $emp['cancelled_pickups'] . ",
                        '" . $emp['completion_rate'] . "%',
                        '" . ($emp['avg_completion_time'] ? $emp['avg_completion_time'] . ' hours' : 'N/A') . "'
                    ]);\n";
                }
            }
            ?>

            // Create workbook and worksheet
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Employee Performance");

            // Generate Excel file
            XLSX.writeFile(wb, 'employee_performance_report.xlsx');
        }

        function generatePDF() {
            // Create new jsPDF instance
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'pt', 'a4');
            
            // Add title
            doc.setFontSize(18);
            doc.setTextColor(40, 167, 69); // Green color
            doc.text('JunkGenie Performance Report', 40, 40);

            // Add date
            doc.setFontSize(12);
            doc.setTextColor(100);
            doc.text('Generated: ' + new Date().toLocaleDateString(), 40, 60);

            // Add weekly chart
            const weeklyCanvas = document.getElementById('weeklyChart');
            const weeklyImg = weeklyCanvas.toDataURL('image/png');
            doc.text('Weekly Orders', 40, 90);
            doc.addImage(weeklyImg, 'PNG', 40, 100, 350, 200);

            // Add monthly chart
            const monthlyCanvas = document.getElementById('monthlyChart');
            const monthlyImg = monthlyCanvas.toDataURL('image/png');
            doc.text('Monthly Orders', 420, 90);
            doc.addImage(monthlyImg, 'PNG', 420, 100, 350, 200);

            // Add employee performance table
            doc.text('Employee Performance', 40, 340);

            // Prepare table data
            const tableData = [];
            <?php
            if ($employee_result) {
                $employee_result->data_seek(0); // Reset result pointer
                while ($emp = $employee_result->fetch_assoc()) {
                    echo "tableData.push([
                        '" . addslashes($emp['employee_name']) . "',
                        '" . $emp['availability'] . "',
                        '" . $emp['total_pickups'] . "',
                        '" . $emp['completed_pickups'] . "',
                        '" . $emp['pending_pickups'] . "',
                        '" . $emp['cancelled_pickups'] . "',
                        '" . $emp['completion_rate'] . "%',
                        '" . ($emp['avg_completion_time'] ? $emp['avg_completion_time'] . ' hours' : 'N/A') . "'
                    ]);\n";
                }
            }
            ?>

            // Add table
            doc.autoTable({
                startY: 360,
                head: [[
                    'Employee Name',
                    'Status',
                    'Total',
                    'Completed',
                    'Pending',
                    'Cancelled',
                    'Rate',
                    'Avg Time'
                ]],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: [40, 167, 69],
                    textColor: 255,
                    fontSize: 10
                },
                bodyStyles: {
                    fontSize: 9
                },
                columnStyles: {
                    0: { cellWidth: 100 },
                    1: { cellWidth: 70 },
                    2: { cellWidth: 50 },
                    3: { cellWidth: 60 },
                    4: { cellWidth: 60 },
                    5: { cellWidth: 60 },
                    6: { cellWidth: 50 },
                    7: { cellWidth: 70 }
                }
            });

            // Save PDF
            doc.save('performance_report.pdf');
        }
    </script>

    <!-- Add these script tags before the closing body tag -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
</body>
</html>