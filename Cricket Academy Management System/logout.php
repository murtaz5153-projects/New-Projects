<?php declare(strict_types=1);
require_once 'config.php';

// Start the session to access it
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "login.php");
exit();