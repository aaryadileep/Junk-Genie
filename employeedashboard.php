<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="css/employeestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/logo.png" alt="Company Logo">
        </div>
        <div class="profile">
            <span>Employee Name</span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main>
        <!-- Profile Section -->
        <section class="card">
            <h2><i class="fas fa-user"></i> Profile</h2>
            <div class="profile-details">
                <p><strong>Name:</strong> Employee Name</p>
                <p><strong>Email:</strong> employee@example.com</p>
                <p><strong>Phone:</strong> 123-456-7890</p>
                <p><strong>City:</strong> City Name</p>
                <p><strong>Availability:</strong> 
                    <span id="availability-status">Available</span>
                    <button id="toggle-availability" class="btn">Set Unavailable</button>
                </p>
            </div>
        </section>

        <!-- Tasks Section -->
        <section class="card">
            <h2><i class="fas fa-tasks"></i> Tasks</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pickup Location</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>Location A</td>
                        <td>Assigned</td>
                        <td>10:00 AM</td>
                        <td>
                            <button class="btn accept-btn">Accept</button>
                            <button class="btn reject-btn">Reject</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- History Section -->
        <section class="card">
            <h2><i class="fas fa-history"></i> History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Customer Info</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2024-02-21</td>
                        <td>Location B</td>
                        <td>Customer XYZ</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Notifications Section -->
        <section class="card">
            <h2><i class="fas fa-bell"></i> Notifications</h2>
            <div id="notification-list">
                <p>No new notifications.</p>
            </div>
        </section>
    </main>

    <script src="js/employeejs.js"></script>
</body>
</html>
