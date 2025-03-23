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

// Fetch assigned pickups with tracking details
$pickup_query = "SELECT 
    c.id, 
    u.fullname AS customer_name,
    u.phone AS customer_phone,
    ua.address_line,
    ua.landmark,
    ci.city_name,
    c.pickup_date,
    c.pickup_status,
    c.created_at
    FROM cart c 
    JOIN users u ON c.user_id = u.user_id 
    JOIN user_addresses ua ON c.address_id = ua.address_id
    JOIN cities ci ON ua.city_id = ci.city_id
    WHERE c.assigned_employee_id = ? 
    AND c.pickup_status != 'Completed'
    AND c.pickup_status != 'Rejected'
    ORDER BY c.pickup_date ASC";

$stmt = $conn->prepare($pickup_query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$pickups = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Pickups | JunkGenie</title>
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

        .tracking-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .tracking-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #FFF3E0; color: #E65100; }
        .status-accepted { background: #E3F2FD; color: #1565C0; }
        .status-in-progress { background: #E8F5E9; color: #2E7D32; }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -34px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary-green);
        }

        .action-buttons button {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'esidebar.php'; ?>

    <div class="main-content">
        <div class="tracking-card mb-4">
            <h2>Track Pickups</h2>
            <p class="text-muted">Monitor and update your assigned pickup requests</p>
        </div>

        <?php if ($pickups->num_rows > 0): ?>
            <?php while ($pickup = $pickups->fetch_assoc()): ?>
                <div class="tracking-card">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4>Order #OI<?php echo $pickup['id']; ?></h4>
                            <p class="text-muted mb-0">
                                Scheduled for <?php echo date('F d, Y', strtotime($pickup['pickup_date'])); ?>
                            </p>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($pickup['pickup_status']); ?>">
                            <?php echo $pickup['pickup_status']; ?>
                        </span>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Customer Details</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($pickup['customer_name']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($pickup['customer_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Pickup Location</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($pickup['address_line']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($pickup['landmark'] . ', ' . $pickup['city_name']); ?></p>
                        </div>
                    </div>

                    <div class="timeline mb-4">
                        <div class="timeline-item">
                            <p class="mb-0"><strong>Order Created</strong></p>
                            <small class="text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($pickup['created_at'])); ?>
                            </small>
                        </div>
                        <?php if ($pickup['pickup_status'] != 'Pending'): ?>
                        <div class="timeline-item">
                            <p class="mb-0"><strong>Status Updated</strong></p>
                            <small class="text-muted">
                                <?php echo $pickup['pickup_status']; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <?php if ($pickup['pickup_status'] == 'Pending'): ?>
                            <button type="button" class="btn btn-success" 
                                    onclick="updateStatus(<?php echo $pickup['id']; ?>, 'In Progress')">
                                <i class="fas fa-truck"></i> Start Pickup
                            </button>
                        <?php elseif ($pickup['pickup_status'] == 'In Progress'): ?>
                            <button type="button" class="btn btn-primary" 
                                    onclick="updateStatus(<?php echo $pickup['id']; ?>, 'Completed')">
                                <i class="fas fa-check"></i> Complete Pickup
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-primary" 
                                onclick="viewDetails(<?php echo $pickup['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="tracking-card text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4>All Caught Up!</h4>
                <p class="text-muted">No pending pickups to track at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Pickup Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to update this pickup's status?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmStatusUpdate">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(cartId, newStatus) {
            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            const confirmBtn = document.getElementById('confirmStatusUpdate');
            
            confirmBtn.onclick = function() {
                fetch('update_pickup_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_id: cartId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
                
                modal.hide();
            };
            
            modal.show();
        }
    </script>
</body>
</html> 