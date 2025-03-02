<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get user's current city if not set in POST
if (!isset($_POST['city']) || empty($_POST['city'])) {
    // Default to Bangalore if geolocation fails
    $default_city = 'Bangalore';
    
    // Try to get city from database first
    $stmt = $conn->prepare("SELECT city FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $city = !empty($user['city']) ? $user['city'] : $default_city;
} else {
    $city = trim($_POST['city']);
}

// Update the city in database
$stmt = $conn->prepare("UPDATE users SET city = ? WHERE user_id = ?");
$stmt->bind_param("si", $city, $_SESSION['user_id']);

if ($stmt->execute()) {
    $_SESSION['city'] = $city;
    echo json_encode([
        'success' => true,
        'city' => $city
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Could not update city',
        'city' => $_SESSION['city'] ?? 'Bangalore'
    ]);
}

// Add this to your userdashboard.php JavaScript section
function updateLocation() {
    if (navigator.geolocation) {
        // Show loading state
        document.getElementById('cityDisplay').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        navigator.geolocation.getCurrentPosition(position => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            
            // Use Nominatim API to get city name
            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`)
                .then(response => response.json())
                .then(data => {
                    const city = data.address.city || data.address.town || data.address.village || 'Bangalore';
                    updateCityInDatabase(city);
                })
                .catch(() => {
                    // If geolocation fails, use existing city or default
                    updateCityInDatabase();
                });
        }, error => {
            // Handle geolocation error
            updateCityInDatabase();
        });
    } else {
        // If geolocation not supported
        updateCityInDatabase();
    }
}

function updateCityInDatabase(city = null) {
    const formData = new FormData();
    if (city) formData.append('city', city);

    fetch('update_city.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cityDisplay').textContent = data.city;
        } else {
            document.getElementById('cityDisplay').textContent = data.city || 'Bangalore';
        }
    })
    .catch(() => {
        document.getElementById('cityDisplay').textContent = 'Bangalore';
    });
}