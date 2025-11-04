<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only admins can delete users
require_admin();

// 1. Get and validate the user ID from the URL
$user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id_to_delete) {
    header("Location: admin.php");
    exit();
}

// 2. Security Check: Prevent an admin from deleting their own account
if ($user_id_to_delete === $_SESSION['user_id']) {
    header("Location: admin.php?error=self_delete");
    exit();
}

// 3. Perform the deletion
try {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);

    // Check if any row was actually deleted
    if ($stmt->rowCount() > 0) {
        header("Location: admin.php?status=user_deleted");
    } else {
        // This can happen if the user ID doesn't exist
        header("Location: admin.php?error=notfound");
    }
    exit();

} catch (PDOException $e) {
    error_log("Delete User Error: " . $e->getMessage());
    header("Location: admin.php?error=dberror");
    exit();
}