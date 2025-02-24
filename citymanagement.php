<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            display: flex;
        }

        .container {
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 80%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #34495e;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-group input[type="checkbox"] {
            width: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        td button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #f39c12;
            color: white;
        }

        .btn-edit:hover {
            background-color: #e67e22;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>City Management</h1>
            <a href="admindashboard.php" class="btn btn-secondary">Back</a>
        </div>

        <?php
        include 'connect.php';

        // Handle form submission for adding a city
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_city'])) {
            $city_name = $_POST['city_name'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $sql = "INSERT INTO cities (city_name, is_active) VALUES ('$city_name', $is_active)";

            if ($conn->query($sql) === TRUE) {
                echo "New city added successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Handle form submission for editing a city
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_city'])) {
            $city_id = $_POST['city_id'];
            $city_name = $_POST['city_name'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $sql = "UPDATE cities SET city_name = '$city_name', is_active = $is_active WHERE city_id = $city_id";

            if ($conn->query($sql) === TRUE) {
                echo "City updated successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }

        // Handle form submission for deleting a city
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_city'])) {
            $city_id = $_POST['city_id'];

            $sql = "DELETE FROM cities WHERE city_id = $city_id";

            if ($conn->query($sql) === TRUE) {
                echo "City deleted successfully";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
        ?>

        <form action="citymanagement.php" method="POST">
            <div class="form-group">
                <label for="city_name">City Name</label>
                <input type="text" id="city_name" name="city_name" required>
            </div>
            <div class="form-group">
                <label for="is_active">Active</label>
                <input type="checkbox" id="is_active" name="is_active" checked>
            </div>
            <button type="submit" name="add_city" class="btn btn-primary">Add City</button>
        </form>

        <h2>Existing Cities</h2>
        <table>
            <thead>
                <tr>
                    <th>City ID</th>
                    <th>City Name</th>
                    <th>Active</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT city_id, city_name, is_active, created_at FROM cities";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["city_id"] . "</td>";
                        echo "<td>" . $row["city_name"] . "</td>";
                        echo "<td>" . ($row["is_active"] ? 'Yes' : 'No') . "</td>";
                        echo "<td>" . $row["created_at"] . "</td>";
                        echo "<td>
                            <form style='display:inline;' action='citymanagement.php' method='POST'>
                                <input type='hidden' name='city_id' value='" . $row["city_id"] . "'>
                                <input type='text' name='city_name' value='" . $row["city_name"] . "' required>
                                <label for='is_active'>Active</label>
                                <input type='checkbox' name='is_active'" . ($row["is_active"] ? " checked" : "") . ">
                                <button type='submit' name='edit_city' class='btn btn-edit'>Edit</button>
                            </form>
                            <form style='display:inline;' action='citymanagement.php' method='POST'>
                                <input type='hidden' name='city_id' value='" . $row["city_id"] . "'>
                                <button type='submit' name='delete_city' class='btn btn-delete'>Delete</button>
                            </form>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No cities found</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
