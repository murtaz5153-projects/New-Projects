<?php declare(strict_types=1);
require_once 'config.php';

function is_logged_in(): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "login.php?reason=session_expired");
        exit();
    }

    $_SESSION['last_activity'] = time();
    return true;
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

function require_role(string $role): void {
    require_login();
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== strtolower($role)) {
        header("Location: " . BASE_URL . "dashboard.php?error=unauthorized");
        exit();
    }
}

function require_admin(): void {
    require_role('admin');
}

// --- UPDATED FUNCTION ---
// This function will now allow BOTH 'coach' and 'admin' roles to access coach pages.
function require_coach(): void {
    require_login();
    $user_role = strtolower($_SESSION['role'] ?? '');
    
    if ($user_role !== 'coach' && $user_role !== 'admin') {
        header("Location: " . BASE_URL . "dashboard.php?error=unauthorized");
        exit();
    }
}

// --- Helper function for notifications (if you placed it here) ---
function create_notification(PDO $conn, int $user_id, string $message, ?string $link = null): void {
    try {
        $sql = "INSERT INTO notifications (user_id, message, link) VALUES (:user_id, :message, :link)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':message' => $message,
            ':link' => $link
        ]);
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }
}