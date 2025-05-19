<?php
/**
 * HirayaFit User Management System
 * 
 * This file contains the core functionality for user management in the admin panel,
 * including viewing all users, user details, and deleting users with admin authentication.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'hirayafitdb'; // Update with your actual database name
    $username = 'root'; // Update with your database username
    $password = ''; // Update with your database password

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Admin authentication check
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect to login if not authenticated
function redirectToLogin() {
    header("Location: admin_login.php");
    exit();
}

// Get all users from database
function getAllUsers() {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, fullname, email, username, address, phone, profile_image, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get user details by ID
function getUserDetails($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, fullname, email, username, address, phone, profile_image, is_active, last_login, created_at, updated_at FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verify admin password
function verifyAdminPassword($adminId, $password) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = :admin_id");
    $stmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        return password_verify($password, $admin['password']);
    }
    
    return false;
}

// Delete user by ID
function deleteUser($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Set user active status
function setUserActiveStatus($userId, $status) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE users SET is_active = :status WHERE id = :id");
    $stmt->bindParam(':status', $status, PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Process user deletion with admin password verification
function processUserDeletion($userId, $adminPassword) {
    if (!isAdminLoggedIn()) {
        return ['success' => false, 'message' => 'Admin authentication required'];
    }
    
    $adminId = $_SESSION['admin_id'];
    
    if (!verifyAdminPassword($adminId, $adminPassword)) {
        return ['success' => false, 'message' => 'Invalid admin password'];
    }
    
    if (deleteUser($userId)) {
        return ['success' => true, 'message' => 'User deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
}

// Helper function to format date time
function formatDateTime($dateTime) {
    if (empty($dateTime) || $dateTime == '0000-00-00 00:00:00') {
        return 'Never';
    }
    return date('M d, Y h:i A', strtotime($dateTime));
}
?>

<?php
/**
 * User List Page
 * 
 * Displays all registered users with options to view details or delete users.
 */

// Include core functionality
require_once 'user_management_system.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirectToLogin();
}

// Process user deletion if form is submitted
$deleteMessage = '';
$deleteSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    $adminPassword = $_POST['admin_password'];
    
    $result = processUserDeletion($userId, $adminPassword);
    $deleteSuccess = $result['success'];
    $deleteMessage = $result['message'];
}

// Process user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = $_POST['user_id'];
    $newStatus = ($_POST['current_status'] == '1') ? 0 : 1;
    
    if (setUserActiveStatus($userId, $newStatus)) {
        $statusMessage = $newStatus ? 'User activated successfully' : 'User deactivated successfully';
    }
}

// Get all users
$users = getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit Admin - User Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<!-- Admin Header/Navbar (Include your existing admin header) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">HirayaFit Admin</a>
        <!-- Add your navbar items here -->
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar (Include your existing admin sidebar) -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <!-- Your sidebar content -->
        </div>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Management</h1>
            </div>
            
            <?php if (!empty($deleteMessage)): ?>
                <div class="alert alert-<?php echo $deleteSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $deleteMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($statusMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $statusMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-users me-2"></i> Registered Users
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Profile</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <?php if (!empty($user['profile_image']) && file_exists('uploads/profiles/' . $user['profile_image'])): ?>
                                                    <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile" class="user-avatar">
                                                <?php else: ?>
                                                    <img src="assets/images/default-avatar.png" alt="Default Profile" class="user-avatar">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success status-badge">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger status-badge">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($user['last_login']); ?></td>
                                            <td><?php echo formatDateTime($user['created_at']); ?></td>
                                            <td class="action-btns">
                                                <div class="btn-group">
                                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-warning toggle-status-btn" data-bs-toggle="modal" data-bs-target="#toggleStatusModal" 
                                                            data-user-id="<?php echo $user['id']; ?>" 
                                                            data-status="<?php echo $user['is_active']; ?>"
                                                            data-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                                        <i class="fas <?php echo $user['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                            data-user-id="<?php echo $user['id']; ?>" 
                                                            data-name="<?php echo htmlspecialchars($user['fullname']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm User Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to delete user: <strong id="delete-user-name"></strong>?
                        <p class="mt-2 mb-0">This action cannot be undone. All user data will be permanently removed.</p>
                    </div>
                    <input type="hidden" name="user_id" id="delete-user-id">
                    
                    <div class="mb-3">
                        <label for="admin-password" class="form-label">Enter Admin Password to Confirm:</label>
                        <input type="password" class="form-control" id="admin-password" name="admin_password" required>
                        <div class="form-text">For security purposes, please enter your admin password to confirm this action.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="toggleStatusModalLabel">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You are about to <span id="status-action"></span> user: <strong id="status-user-name"></strong>
                    </div>
                    <input type="hidden" name="user_id" id="status-user-id">
                    <input type="hidden" name="current_status" id="current-status">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="toggle_status" class="btn btn-warning">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap and jQuery JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Set user data in delete modal
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-name');
            
            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = userName;
        });
    });
    
    // Set user data in toggle status modal
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-name');
            const currentStatus = this.getAttribute('data-status');
            
            document.getElementById('status-user-id').value = userId;
            document.getElementById('status-user-name').textContent = userName;
            document.getElementById('current-status').value = currentStatus;
            
            const actionText = currentStatus === '1' ? 'deactivate' : 'activate';
            document.getElementById('status-action').textContent = actionText;
        });
    });
</script>

</body>
</html>

