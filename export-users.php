<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: sign-in.php");
    exit;
}

// Database connection configuration
$db_host = 'localhost';
$db_user = 'root'; // Change to your DB username
$db_pass = '';     // Change to your DB password
$db_name = 'hirayafitdb'; // Change to your DB name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hirayafit_users_'.date('Y-m-d').'.csv"');

// Create CSV file handle
$output = fopen('php://output', 'w');

// Add BOM for proper UTF-8 encoding in Excel
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Add CSV headers
fputcsv($output, array(
    'ID', 
    'Full Name', 
    'Email', 
    'Username', 
    'Phone', 
    'Address', 
    'Account Status', 
    'Last Login', 
    'Registration Date'
));

// Process search query if it exists
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';
$params = array();
$types = '';

if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $where_clause = " WHERE fullname LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?";
    $params = array($search_term, $search_term, $search_term, $search_term);
    $types = 'ssss';
}

// Prepare SQL query to get all users
$sql = "SELECT id, fullname, email, username, phone, address, is_active, last_login, created_at 
        FROM users" . $where_clause . " 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Output each row of data
while ($row = $result->fetch_assoc()) {
    // Format date fields
    $lastLogin = $row['last_login'] ? date('Y-m-d H:i:s', strtotime($row['last_login'])) : 'Never';
    $createdDate = date('Y-m-d H:i:s', strtotime($row['created_at']));
    
    // Format status
    $status = $row['is_active'] ? 'Active' : 'Inactive';
    
    // Write row to CSV
    fputcsv($output, array(
        $row['id'],
        $row['fullname'],
        $row['email'],
        $row['username'],
        $row['phone'] ?? 'Not provided',
        $row['address'] ?? 'Not provided',
        $status,
        $lastLogin,
        $createdDate
    ));
}

// Close the database connection
$stmt->close();
$conn->close();

// Close the CSV file handle
fclose($output);
exit;