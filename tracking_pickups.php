<?php
session_start();
require_once 'connect.php';

// Add this function at the top of your PHP section
function getPickupCountdown($pickup_date) {
    $pickup = new DateTime($pickup_date);
    $today = new DateTime();
    $interval = $today->diff($pickup);
    
    if ($today > $pickup) {
        return '<span class="countdown overdue"><i class="fas fa-exclamation-circle"></i> Overdue</span>';
    } elseif ($interval->days == 0) {
        return '<span class="countdown today"><i class="fas fa-clock"></i> Today</span>';
    } elseif ($interval->days == 1) {
        return '<span class="countdown tomorrow"><i class="fas fa-clock"></i> Tomorrow</span>';
    } else {
        return '<span class="countdown upcoming"><i class="fas fa-calendar"></i> ' . $interval->days . ' days left</span>';
    }
}

if (!isset($_SESSION['user_id'])) {
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
    AND c.pickup_status IN ('Accepted')
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
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
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
            transition: all 0.3s ease;
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

        .date-today {
            color: orange;
            font-weight: bold;
        }

        .date-passed {
            color: red;
            font-weight: bold;
        }

        .completion-checkbox {
            position: relative;
        }

        .complete-checkbox {
            display: none;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border: 2px solid #2E7D32;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            color: transparent;
        }

        .checkbox-label:hover {
            background-color: rgba(46, 125, 50, 0.1);
        }

        .complete-checkbox:checked + .checkbox-label {
            background-color: #2E7D32;
            color: white;
        }

        .success-animation {
            font-size: 80px;
            color: #2E7D32;
            animation: scaleUp 0.5s ease;
        }

        @keyframes scaleUp {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        #successModal .modal-content {
            border-radius: 15px;
            border: none;
        }

        #successModal .btn-success {
            border-radius: 25px;
            padding: 10px 30px;
        }

        .countdown {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .countdown.overdue {
            background-color: #FFE7E7;
            color: #D32F2F;
            border: 1px solid #FFCDD2;
        }

        .countdown.today {
            background-color: #FFF3E0;
            color: #E65100;
            border: 1px solid #FFE0B2;
        }

        .countdown.tomorrow {
            background-color: #E3F2FD;
            color: #1565C0;
            border: 1px solid #BBDEFB;
        }

        .countdown.upcoming {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }

        .countdown.completed {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }

        .fade-out {
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.5s ease;
        }

        .status-badge.status-completed {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .status-badge.status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-badge.status-in-progress {
            background-color: #E3F2FD;
            color: #1565C0;
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
                <div class="tracking-card" id="pickup-card-<?php echo $pickup['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="d-flex align-items-center">
                            <?php if ($pickup['pickup_status'] != 'Completed'): ?>
                                <div class="completion-checkbox me-3">
                                    <input type="checkbox" 
                                           id="complete_<?php echo $pickup['id']; ?>" 
                                           class="complete-checkbox" 
                                           onchange="completePickup(<?php echo $pickup['id']; ?>)">
                                    <label for="complete_<?php echo $pickup['id']; ?>" 
                                           class="checkbox-label">
                                        <i class="fas fa-check"></i>
                                    </label>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4>Order #OI<?php echo $pickup['id']; ?></h4>
                                <p class="text-muted mb-0">
                                    Scheduled for <?php echo date('F d, Y', strtotime($pickup['pickup_date'])); ?>
                                </p>
                                <div id="countdown-<?php echo $pickup['id']; ?>">
                                    <?php if ($pickup['pickup_status'] != 'Completed'): ?>
                                        <?php echo getPickupCountdown($pickup['pickup_date']); ?>
                                    <?php else: ?>
                                        <span class="countdown completed">
                                            <i class="fas fa-check-circle"></i> Completed
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($pickup['pickup_status']); ?>" id="status-<?php echo $pickup['id']; ?>">
                            <?php echo htmlspecialchars($pickup['pickup_status']); ?>
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

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="success-animation mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-success mb-3">Congratulations!</h3>
                    <p class="mb-0">You've successfully completed the pickup!</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function triggerConfetti() {
            const duration = 3000;
            const options = {
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            };

            // Fire multiple confetti bursts
            confetti({
                ...options,
                angle: 60,
                origin: { x: 0 }
            });
            confetti({
                ...options,
                angle: 120,
                origin: { x: 1 }
            });
            
            // Fire another burst after a slight delay
            setTimeout(() => {
                confetti({
                    particleCount: 50,
                    spread: 50,
                    origin: { y: 0.6 }
                });
            }, 500);
        }

        function completePickup(cartId) {
            const checkbox = document.getElementById(`complete_${cartId}`);
            
            if (checkbox.checked) {
                checkbox.disabled = true;
                
                fetch('update_pickup_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_id: cartId,
                        status: 'Completed'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Trigger confetti immediately
                        triggerConfetti();

                        // Update UI
                        const card = document.getElementById(`pickup-card-${cartId}`);
                        const countdown = document.getElementById(`countdown-${cartId}`);
                        const statusBadge = document.getElementById(`status-${cartId}`);
                        
                        countdown.innerHTML = '<span class="countdown completed"><i class="fas fa-check-circle"></i> Completed</span>';
                        statusBadge.textContent = 'Completed';
                        statusBadge.className = 'status-badge status-completed';
                        
                        // Show success modal
                        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        
                        // Remove the card after a delay
                        setTimeout(() => {
                            card.classList.add('fade-out');
                            setTimeout(() => {
                                card.remove();
                                // If no more cards, show the "all caught up" message
                                if (document.querySelectorAll('.tracking-card').length <= 1) {
                                    const mainContent = document.querySelector('.main-content');
                                    mainContent.innerHTML = `
                                        <div class="tracking-card mb-4">
                                            <h2>Track Pickups</h2>
                                            <p class="text-muted">Monitor and update your assigned pickup requests</p>
                                        </div>
                                        <div class="tracking-card text-center">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h4>All Caught Up!</h4>
                                            <p class="text-muted">No pending pickups to track at the moment.</p>
                                        </div>
                                    `;
                                }
                            }, 500);
                        }, 2000);
                    } else {
                        alert('Error updating status: ' + data.message);
                        checkbox.checked = false;
                        checkbox.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                    checkbox.checked = false;
                    checkbox.disabled = false;
                });
            }
        }
    </script>
</body>
</html>