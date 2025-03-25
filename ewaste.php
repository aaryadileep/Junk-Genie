<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Fetch completed pickups query
$query = "SELECT c.id as cart_id, 
          c.pickup_date, 
          c.pickup_status,
          u.fullname as customer_name, 
          u.phone,
          ua.address_line,
          ua.landmark,
          ct.city_name,
          ci.image as collection_image,
          GROUP_CONCAT(CONCAT(p.product_name, ' (', ci.description, ')') SEPARATOR ', ') as collected_items
          FROM cart c
          JOIN users u ON c.user_id = u.user_id
          JOIN user_addresses ua ON c.address_id = ua.address_id
          JOIN cities ct ON ua.city_id = ct.city_id
          JOIN cart_items ci ON c.id = ci.cart_id
          JOIN products p ON ci.product_id = p.product_id
          WHERE c.pickup_status = 'Completed'
          GROUP BY c.id
          ORDER BY c.pickup_date DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Waste Collection Records - JunkGenie</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-icon.primary {
            color: #007bff;
        }
        .stats-icon.success {
            color: #28a745;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .status-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
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
            transition: 0.4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .container-fluid {
        margin-left: 280px; /* Same width as sidebar */
        width: calc(100% - 280px);
        transition: margin-left 0.3s ease;
    }

    /* Update sidebar styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: 280px;
        z-index: 1000;
        background: #fff;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    @media (max-width: 768px) {
        .container-fluid {
            margin-left: 0;
            width: 100%;
        }
        
        .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
    }
    .table thead {
        background-color: #28a745 !important;  /* Bootstrap success green */
    }
    .collected-image {
        max-width: 100px;
        height: auto;
        cursor: pointer;
    }
    .modal-img {
        max-width: 100%;
        height: auto;
    }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </nav>

            <!-- Main content -->
            <div class="container-fluid">
                <div class="container mt-4">
                    <h2>E-Waste Collection Records</h2>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-striped table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th>Pickup ID</th>
                                    <th>Customer Name</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Items Collected</th>
                                    <th>Pickup Date</th>
                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $full_address = $row['address_line'];
                                        if (!empty($row['landmark'])) {
                                            $full_address .= ", " . $row['landmark'];
                                        }
                                        $full_address .= ", " . $row['city_name'];

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['cart_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                        echo "<td>" . htmlspecialchars($full_address) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['collected_items']) . "</td>";
                                        echo "<td>" . date('Y-m-d', strtotime($row['pickup_date'])) . "</td>";
                                        echo "<td><img src='" . htmlspecialchars($row['collection_image']) . "' class='collected-image' onclick='showImage(this.src)' alt='Collection Image'></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center'>No completed pickups found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Export buttons -->
                    <div class="mt-3 mb-4">
                        <button class="btn btn-success" onclick="exportToExcel()">Export to Excel</button>
                        <button class="btn btn-danger" onclick="exportToPDF()">Export to PDF</button>
                    </div>
                </div>
            </div>

            <!-- Add Modal for Image Preview -->
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Collection Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="modalImage" src="" class="modal-img" alt="Collection Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Export libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    
    <script>
    // Add image preview function
    function showImage(src) {
        document.getElementById('modalImage').src = src;
        var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    }

    // Export functions
    function exportToExcel() {
        var table = document.querySelector('table');
        var wb = XLSX.utils.table_to_book(table, {sheet: "E-Waste Records"});
        XLSX.writeFile(wb, 'ewaste_records.xlsx');
    }

    function exportToPDF() {
        var element = document.querySelector('.table-responsive');
        var opt = {
            margin: 1,
            filename: 'ewaste_records.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
</body>
</html> 