<?php
// loyalty-points.php
session_start();
require_once 'connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Function to get user's total points
function getUserTotalPoints($conn, $user_id) {
    $query = "SELECT SUM(lp.points_earned) as total_points 
              FROM loyalty_points lp
              JOIN cart c ON lp.order_id = c.id
              WHERE c.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total_points'] ?? 0;
}

// Function to get user's achievements
function getUserAchievements($conn, $user_id) {
    $query = "SELECT lm.* FROM user_achievements ua 
              JOIN loyalty_milestones lm ON ua.milestone_id = lm.milestone_id
              WHERE ua.user_id = ? AND lm.is_active = TRUE
              ORDER BY lm.required_points";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get all active milestones
function getActiveMilestones($conn) {
    $query = "SELECT * FROM loyalty_milestones 
              WHERE is_active = TRUE
              ORDER BY required_points";
    return $conn->query($query)->fetch_all(MYSQLI_ASSOC);
}

// Function to get user's point history
function getPointHistory($conn, $user_id) {
    $query = "SELECT lp.*, c.pickup_date, c.pickup_status 
              FROM loyalty_points lp
              JOIN cart c ON lp.order_id = c.id
              WHERE c.user_id = ?
              ORDER BY lp.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all data
$total_points = getUserTotalPoints($conn, $user_id);
$achievements = getUserAchievements($conn, $user_id);
$milestones = getActiveMilestones($conn);
$point_history = getPointHistory($conn, $user_id);

// Function to award points (to be called when order is completed)
function awardLoyaltyPoints($conn, $order_id, $points, $reason) {
    $query = "INSERT INTO loyalty_points (order_id, points_earned, points_reason)
              VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $order_id, $points, $reason);
    return $stmt->execute();
}

// Function to check and award milestones
function checkAndAwardMilestones($conn, $user_id, $total_points) {
    // Get milestones that user hasn't achieved yet but qualifies for
    $query = "SELECT lm.* FROM loyalty_milestones lm
              WHERE lm.is_active = TRUE 
              AND lm.required_points <= ?
              AND NOT EXISTS (
                  SELECT 1 FROM user_achievements ua 
                  WHERE ua.user_id = ? AND ua.milestone_id = lm.milestone_id
              )";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $total_points, $user_id);
    $stmt->execute();
    $new_milestones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $new_achievement = null;
    foreach ($new_milestones as $milestone) {
        // Award the milestone
        $query = "INSERT INTO user_achievements (user_id, milestone_id)
                  VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $milestone['milestone_id']);
        $stmt->execute();
        
        // Remember the first new achievement for celebration
        if (!$new_achievement) {
            $new_achievement = $milestone;
        }
    }
    
    return $new_achievement;
}

// Check if we should celebrate a new achievement
$new_achievement = null;
if (isset($_GET['check_milestones'])) {
    $new_achievement = checkAndAwardMilestones($conn, $user_id, $total_points);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Points | JunkGenie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .badge-card {
            transition: transform 0.3s;
        }
        .badge-card:hover {
            transform: translateY(-5px);
        }
        .badge-img {
            filter: grayscale(100%);
            opacity: 0.5;
        }
        .badge-img.unlocked {
            filter: grayscale(0);
            opacity: 1;
        }
        .progress-bar {
            background: linear-gradient(90deg, #FFD700, #2E7D32);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0">Your Loyalty Points</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <h1 class="display-4"><?= $total_points ?> pts</h1>
                    <a href="?check_milestones" class="btn btn-outline-success">Check for New Achievements</a>
                </div>
                
                <!-- Progress to next milestone -->
                <?php 
                $next_milestone = null;
                foreach ($milestones as $milestone) {
                    if ($milestone['required_points'] > $total_points) {
                        $next_milestone = $milestone;
                        break;
                    }
                }
                
                if ($next_milestone): 
                    $progress = ($total_points / $next_milestone['required_points']) * 100;
                ?>
                <div class="mb-4">
                    <h5>Progress to <?= $next_milestone['milestone_name'] ?></h5>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $progress ?>%" 
                             aria-valuenow="<?= $progress ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted">
                        <?= $next_milestone['required_points'] - $total_points ?> more points needed
                    </small>
                </div>
                <?php endif; ?>
                
                <!-- Milestone badges -->
                <h4 class="mb-3">Achievements</h4>
                <div class="row">
                    <?php foreach ($milestones as $milestone): 
                        $unlocked = false;
                        foreach ($achievements as $achievement) {
                            if ($achievement['milestone_id'] == $milestone['milestone_id']) {
                                $unlocked = true;
                                break;
                            }
                        }
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card badge-card h-100">
                            <div class="card-body text-center">
                                <img src="<?= $milestone['badge_image'] ?>" 
                                     class="img-fluid mb-3 badge-img <?= $unlocked ? 'unlocked' : '' ?>" 
                                     style="height: 100px; width: auto;">
                                <h5><?= $milestone['milestone_name'] ?></h5>
                                <p class="text-muted"><?= $milestone['required_points'] ?> points</p>
                                <?php if ($unlocked): ?>
                                    <span class="badge bg-success">Unlocked!</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Point history -->
                <h4 class="mb-3">Point History</h4>
                <?php if (!empty($point_history)): ?>
                    <div class="list-group">
                        <?php foreach ($point_history as $history): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>Order #<?= $history['order_id'] ?></strong>
                                    <div class="text-muted">
                                        <?= date('M d, Y', strtotime($history['pickup_date'])) ?>
                                    </div>
                                    <small><?= $history['points_reason'] ?></small>
                                </div>
                                <div class="text-success fw-bold">
                                    +<?= $history['points_earned'] ?> pts
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No point history yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Celebration Modal -->
    <?php if ($new_achievement): ?>
    <div class="modal fade show" id="achievementModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Achievement Unlocked!</h5>
                </div>
                <div class="modal-body text-center">
                    <img src="<?= $new_achievement['badge_image'] ?>" class="img-fluid mb-3" style="height: 150px;">
                    <h3><?= $new_achievement['milestone_name'] ?></h3>
                    <p><?= $new_achievement['description'] ?></p>
                    <button class="btn btn-success" onclick="window.location.href='loyalty-points.php'">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Close modal and redirect
        function closeModal() {
            window.location.href = 'loyalty-points.php';
        }
        
        // Auto-close modal after 5 seconds
        <?php if ($new_achievement): ?>
        setTimeout(closeModal, 5000);
        <?php endif; ?>
    </script>
</body>
</html>