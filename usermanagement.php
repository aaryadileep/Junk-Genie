<?php
session_start();
require_once 'connect.php';

// Ensure only Admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch Cities for Dropdown
$cityQuery = "SELECT city_id, city_name FROM cities WHERE is_active = 1";
$cityResult = $conn->query($cityQuery);
$cities = [];
while ($row = $cityResult->fetch_assoc()) {
    $cities[] = $row;
}

// Handle User Updates
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city_id = trim($_POST['city_id']);

    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, city_id=? WHERE user_id=? AND role='End User'");
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $city_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully";
    } else {
        $_SESSION['error'] = "Error updating user";
    }
    header("Location: usermanagement.php");
    exit();
}

// Handle User Deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role='End User'");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting user";
    }
    header("Location: usermanagement.php");
    exit();
}

// Fetch Only End Users
$query = "SELECT u.user_id, u.fullname, u.email, u.phone, c.city_name, u.is_active 
          FROM users u 
          LEFT JOIN cities c ON u.city_id = c.city_id 
          WHERE u.role = 'End User'
          ORDER BY u.fullname";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>End User Management - JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>

    <div class="container mt-4">
        <h2>End User Management</h2>
        
        <a href="admindashboard.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                            <td><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        
                        <div class="mb-3">
                            <label for="edit_fullname" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_fullname" name="fullname" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_city" class="form-label">City</label>
                            <select class="form-select" id="edit_city" name="city_id" required>
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['city_id']; ?>">
                                        <?php echo htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.user_id;
        document.getElementById('edit_fullname').value = user.fullname;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_phone').value = user.phone;
        document.getElementById('edit_city').value = user.city_id;
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
    </script>
</body>
</html>
