<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Handle city status toggle
if (isset($_POST['toggle_status'])) {
    $city_id = $_POST['city_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE cities SET is_active = ? WHERE city_id = ?");
    $stmt->bind_param("ii", $new_status, $city_id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to toggle status."]);
    }
    exit();
}

// Handle adding new city
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_city'])) {
    $city_name = trim($_POST['city_name']);
    
    // Validate city name
    if (empty($city_name)) {
        echo json_encode(["success" => false, "error" => "City name cannot be empty."]);
        exit();
    } else {
        // Check if city already exists
        $stmt = $conn->prepare("SELECT city_id FROM cities WHERE city_name = ?");
        $stmt->bind_param("s", $city_name);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            echo json_encode(["success" => false, "error" => "City already exists."]);
            exit();
        } else {
            // Insert new city
            $stmt = $conn->prepare("INSERT INTO cities (city_name, is_active) VALUES (?, 1)");
            $stmt->bind_param("s", $city_name);
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "error" => "Failed to add city."]);
            }
            exit();
        }
    }
}

// Fetch cities with row number
$query = "SELECT 
            ROW_NUMBER() OVER (ORDER BY created_at) as serial_no,
            city_id,
            city_name,
            is_active,
            created_at,
            (SELECT COUNT(*) FROM users WHERE users.city_id = cities.city_id) as user_count
          FROM cities
          ORDER BY created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Management | JunkGenie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --primary-light: #C8E6C9;
            --secondary: #F5F5F5;
            --success: #4CAF50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
        }

        body {
            background: #f5f6fa;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-inactive {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .toggle-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .toggle-activate {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .toggle-deactivate {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .add-city-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .city-count {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .modal-header {
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border-radius: 10px;
            border: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }

        .is-invalid {
            border-color: var(--danger) !important;
        }

        .invalid-feedback {
            color: var(--danger);
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        
        /* Improved table styling */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        #citiesTable th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        #citiesTable td {
            vertical-align: middle;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            margin-left: 2px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary) !important;
            color: white !important;
            border: none;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--primary-light);
            color: var(--primary-dark) !important;
            border: 1px solid var(--primary);
        }
    </style>
</head>

<body>
<?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h4 class="mb-0">
                <i class="fas fa-city me-2"></i>
                City Management
            </h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCityModal">
                <i class="fas fa-plus me-2"></i>Add New City
            </button>
        </div>

        <!-- Success/Error Messages -->
        <div id="messageContainer"></div>

        <!-- City Table -->
        <div class="table-container">
            <table id="citiesTable" class="table table-hover table-striped" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>S.No</th>
                        <th>City Name</th>
                        <th>Users</th>
                        <th>Added Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['serial_no']; ?></td>
                        <td><?php echo htmlspecialchars($row['city_name']); ?></td>
                        <td>
                            <?php echo $row['user_count']; ?>
                            <span class="city-count">Users</span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <label class="status-toggle">
                                <input type="checkbox" 
                                       onchange="toggleStatus(<?php echo $row['city_id']; ?>, this.checked)"
                                       <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add City Modal -->
    <div class="modal fade" id="addCityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New City</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCityForm" method="POST">
                        <div class="mb-3">
                            <label class="form-label">City Name</label>
                            <input type="text" class="form-control" name="city_name" id="city_name" required>
                            <div class="invalid-feedback" id="cityNameError"></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add City
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize DataTable only once
        $(document).ready(function() {
            if (!$.fn.DataTable.isDataTable('#citiesTable')) {
                $('#citiesTable').DataTable({
                    "order": [[0, "asc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "language": {
                        "search": "_INPUT_",
                        "searchPlaceholder": "Search cities...",
                        "lengthMenu": "Show _MENU_ cities per page",
                        "paginate": {
                            "previous": "<i class='fas fa-chevron-left'></i>",
                            "next": "<i class='fas fa-chevron-right'></i>"
                        }
                    },
                    "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                           "<'row'<'col-sm-12'tr>>" +
                           "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                });
            }
            
            // Handle form submission with better feedback
            $('#addCityForm').on('submit', function(e) {
                e.preventDefault();
                
                const cityName = $('#city_name').val().trim();
                const errorContainer = $('#cityNameError');
                
                // Reset previous errors
                errorContainer.text('');
                $('#city_name').removeClass('is-invalid');
                
                // Validate city name
                if (!cityName) {
                    errorContainer.text('City name is required');
                    $('#city_name').addClass('is-invalid');
                    return;
                }

                // Submit form using AJAX
                $.ajax({
                    url: 'citymanagement.php',
                    type: 'POST',
                    data: {
                        add_city: 1,
                        city_name: cityName
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showMessage('City added successfully!', 'success');
                            // Close modal
                            $('#addCityModal').modal('hide');
                            // Reset form
                            $('#addCityForm')[0].reset();
                            // Reload page to show new city
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            // Show error message
                            errorContainer.text(response.error || 'Failed to add city');
                            $('#city_name').addClass('is-invalid');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred. Please try again.', 'danger');
                    }
                });
            });
        });

        // Status toggle function
        function toggleStatus(cityId, status) {
            $.ajax({
                url: 'citymanagement.php',
                type: 'POST',
                data: {
                    toggle_status: 1,
                    city_id: cityId,
                    new_status: status ? 1 : 0
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showMessage('Status updated successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showMessage(response.error || 'Failed to update status', 'danger');
                        setTimeout(() => location.reload(), 1000);
                    }
                },
                error: function() {
                    showMessage('An error occurred. Please try again.', 'danger');
                }
            });
        }
        
        // Function to show messages
        function showMessage(message, type) {
            const messageContainer = $('#messageContainer');
            messageContainer.html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                messageContainer.find('.alert').alert('close');
            }, 5000);
        }
    </script>
</body>
</html>