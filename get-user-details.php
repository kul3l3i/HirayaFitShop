<?php
// Start the session
session_start();

session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';


// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    echo '<p class="text-danger">Unauthorized access</p>';
    exit;
}


// Check connection
if ($conn->connect_error) {
    echo '<p class="text-danger">Connection failed: ' . $conn->connect_error . '</p>';
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p class="text-danger">Invalid user ID</p>';
    exit;
}

$user_id = (int)$_GET['id'];

// Get user details
$stmt = $conn->prepare("SELECT 
    id, fullname, email, username, address, phone, profile_image, 
    is_active, last_login, created_at, updated_at
    FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="text-danger">User not found</p>';
    exit;
}

// Get user data
$user = $result->fetch_assoc();

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png"; // Default image path
    }
}

// Get profile image URL
$profileImageUrl = getProfileImageUrl($user['profile_image']);

// Format the date
$createdDate = date('F d, Y', strtotime($user['created_at']));
$updatedDate = date('F d, Y', strtotime($user['updated_at']));
$lastLoginDate = $user['last_login'] ? date('F d, Y h:i A', strtotime($user['last_login'])) : 'Never';
?>

<div class="user-profile-header">
    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="<?php echo htmlspecialchars($user['fullname']); ?>" class="user-profile-img">
    <div class="user-profile-info">
        <h4><?php echo htmlspecialchars($user['fullname']); ?></h4>
        <p><?php echo $user['is_active'] ? '<span class="user-status status-active">Active</span>' : '<span class="user-status status-inactive">Inactive</span>'; ?></p>
    </div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">User ID</div>
    <div class="user-detail-value"><?php echo $user['id']; ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Full Name</div>
    <div class="user-detail-value"><?php echo htmlspecialchars($user['fullname']); ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Username</div>
    <div class="user-detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Email</div>
    <div class="user-detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Phone</div>
    <div class="user-detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Address</div>
    <div class="user-detail-value"><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Account Status</div>
    <div class="user-detail-value"><?php echo $user['is_active'] ? '<span class="user-status status-active">Active</span>' : '<span class="user-status status-inactive">Inactive</span>'; ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Last Login</div>
    <div class="user-detail-value"><?php echo $lastLoginDate; ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Registered On</div>
    <div class="user-detail-value"><?php echo $createdDate; ?></div>
</div>

<div class="user-detail-row">
    <div class="user-detail-label">Last Updated</div>
    <div class="user-detail-value"><?php echo $updatedDate; ?></div>
</div>