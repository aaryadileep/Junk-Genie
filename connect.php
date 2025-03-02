<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "junkgenie"; // Ensure this matches the database name you created

// Set timezone for PHP
date_default_timezone_set('Asia/Kolkata');

// Connect to MySQL server and select the database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone for this connection
$conn->query("SET time_zone = '+05:30'");

?>
