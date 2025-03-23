<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: login.php");
    exit();
}

// At the top with other queries, add this:
$user_query = $conn->prepare("SELECT u.fullname 
                            FROM users u 
                            WHERE u.user_id = ?");
$user_query->bind_param("i", $_SESSION['user_id']);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();
?>
<div class="sidebar">
    <div class="logo-container">
        <img src="logo.jpg" alt="JunkGenie Logo" class="logo">
        <h4 class="brand-name">JunkGenie</h4>
    </div>
    <div class="menu">
        <a href="employeedashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employeedashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="employee_assigned_pickups.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employee_assigned_pickups.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck-loading"></i>
            <span>Assigned Pickups</span>
        </a>
        <a href="tracking_pickups.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'tracking_pickups.php' ? 'active' : ''; ?>">
            <i class="fas fa-map-marker-alt"></i>
            <span>Track Pickups</span>
        </a>
        <a href="employee_pickup_history.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employee_pickup_history.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Pickup History</span>
        </a>
        <a href="employee_profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'employee_profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>


</div>

<!-- Add this before the chart.js script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showRejectModal(cartId) {
    document.getElementById('reject_cart_id').value = cartId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function viewDetails(cartId) {
    fetch('get_pickup_details.php?cart_id=' + cartId)
        .then(response => response.json())
        .then(data => {
            const details = `
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-3 text-muted">Customer Information</h6>
                        <p><strong>Name:</strong> ${data.customer_name}</p>
                        <p><strong>Contact:</strong> ${data.phone}</p>
                        
                        <h6 class="card-subtitle mb-3 mt-4 text-muted">Pickup Details</h6>
                        <p><strong>Address:</strong> ${data.address_line}</p>
                        <p><strong>Landmark:</strong> ${data.landmark}</p>
                        <p><strong>City:</strong> ${data.city_name}</p>
                        <p><strong>Pickup Date:</strong> ${data.pickup_date}</p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge ${data.pickup_status.toLowerCase()}-status">
                                ${data.pickup_status}
                            </span>
                        </p>
                        ${data.reject_reason ? `
                            <h6 class="card-subtitle mb-3 mt-4 text-muted">Rejection Reason</h6>
                            <p>${data.reject_reason}</p>
                        ` : ''}
                    </div>
                </div>
            `;
            document.getElementById('pickupDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
        })
        .catch(error => console.error('Error:', error));
}
</script>


            </span>
        </div>
    </div>
</div> 