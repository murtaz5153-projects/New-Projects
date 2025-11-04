<?php declare(strict_types=1);
date_default_timezone_set('UTC');
require_once 'vendor/autoload.php'; // Or your manual PHPMailer includes

// Automatic URL Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_path = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . $script_path);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cricket_academy');

// Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes in seconds
define('SESSION_TIMEOUT', 1800);    // 30 minutes in seconds

// Create PDO connection
try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A system error has occurred. We are working on it. Please try again later.");
}

// Secure session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// --- Google API Credentials ---
define('GOOGLE_CLIENT_ID', '96787370218-tbpl4p9a2tlhp8imsnquq2uq7cvp3k22.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', BASE_URL . 'google-callback.php');

// SMTP Configuration for Email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'murtazanahargarhwala5151@gmail.com');
define('SMTP_PASS', 'varq vuhf pgei ftsr');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'murtazanahargarhwala5151@gmail.com');
define('SMTP_FROM_NAME', 'Cricket Academy');

// Razorpay API Keys (Test Mode)
define('RAZORPAY_KEY_ID', 'rzp_test_REehuT6lTDHUEQ');
define('RAZORPAY_KEY_SECRET', 'BytazGM7b6x4AWRbsG9V2r6v');