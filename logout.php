<?php
// Initialize the session if it hasn't been started
session_start();

// Unset all of the session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to the sign-in page
header("Location: sign-in.php");
exit;
?>
