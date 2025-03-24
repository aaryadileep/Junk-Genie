<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Get employee ID
$user_id = $_SESSION['user_id'];
$emp_query = "SELECT employee_id FROM employees WHERE user_id = ?";
$emp_stmt = $conn->prepare($emp_query);
$emp_stmt->bind_param("i", $user_id);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$emp_data = $emp_result->fetch_assoc();
$employee_id = $emp_data['employee_id'];

// Fetch completed pickups from cart table
$history_query = "SELECT 
    c.id, 
    u.fullname AS customer_name,
    u.phone AS customer_phone,
    ua.address_line,
    ua.landmark,
    ci.city_name,
    c.pickup_date,
    c.pickup_status,
    c.created_at,
    c.updated_at,
    DATEDIFF(c.updated_at, c.pickup_date) as completion_difference
    FROM cart c 
    JOIN users u ON c.user_id = u.user_id 
    JOIN user_addresses ua ON c.address_id = ua.address_id
    JOIN cities ci ON ua.city_id = ci.city_id
    WHERE c.assigned_employee_id = ? 
    AND c.pickup_status = 'Completed'
    ORDER BY c.updated_at DESC";

$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$history = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup History | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #2E7D32;
            --light-green: #4CAF50;
            --sidebar-width: 250px;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background-color: var(--primary-green);
            padding: 1rem;
            z-index: 1000;
            height: 100vh;
            overflow-y: auto;
        }

        .logo-container {
            text-align: center;
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 0.5rem;
            border: 2px solid white;
        }

        .brand-name {
            font-size: 1.1rem;
            margin: 0;
            color: white;
            font-weight: 500;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-item i {
            width: 24px;
            margin-right: 10px;
            font-size: 1rem;
        }

        .menu-item span {
            font-size: 0.9rem;
        }

        .menu-item:hover, .menu-item.active {
            background: var(--light-green);
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .history-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #2E7D32;
            transition: transform 0.3s ease;
        }

        .history-card:hover {
            transform: translateY(-5px);
        }

        .completion-badge {
            background-color: #E8F5E9;
            color: #2E7D32;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timeline-info {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 0;
        }

        .timeline-icon {
            width: 30px;
            height: 30px;
            background: #2E7D32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .completion-status {
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            background: #E8F5E9;
            color: #2E7D32;
            display: inline-block;
        }

        .early-completion {
            color: #2E7D32;
        }

        .late-completion {
            color: #D32F2F;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <div class="tracking-card mb-4">
            <h2><i class="fas fa-history me-2"></i>Pickup History</h2>
            <p class="text-muted">View your completed pickup requests</p>
        </div>

        <?php if ($history->num_rows > 0): ?>
            <?php while ($pickup = $history->fetch_assoc()): ?>
                <div class="history-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4>Order #OI<?php echo $pickup['id']; ?></h4>
                            <div class="text-muted">
                                <i class="fas fa-calendar-check me-2"></i>
                                Completed on <?php echo date('F d, Y, h:i A', strtotime($pickup['updated_at'])); ?>
                            </div>
                        </div>
                        <span class="completion-badge">
                            <i class="fas fa-check-circle"></i>
                            Completed
                        </span>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user me-2"></i>Customer Details</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($pickup['customer_name']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($pickup['customer_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Pickup Location</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($pickup['address_line']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($pickup['landmark'] . ', ' . $pickup['city_name']); ?></p>
                        </div>
                    </div>

                    <div class="timeline-info">
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div>
                                <strong>Scheduled Date</strong><br>
                                <?php echo date('F d, Y', strtotime($pickup['pickup_date'])); ?>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <strong>Completion Date</strong><br>
                                <?php echo date('F d, Y, h:i A', strtotime($pickup['updated_at'])); ?>
                                <?php
                                $diff = $pickup['completion_difference'];
                                if ($diff < 0) {
                                    echo "<span class='early-completion ms-2'>(Completed " . abs($diff) . " days early)</span>";
                                } elseif ($diff > 0) {
                                    echo "<span class='late-completion ms-2'>(Completed " . $diff . " days late)</span>";
                                } else {
                                    echo "<span class='completion-status ms-2'>Completed on time</span>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="tracking-card text-center">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h4>No History Yet</h4>
                <p class="text-muted">You haven't completed any pickups yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>