<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can delete batches
require_coach();

// 1. Get and validate the batch ID from the URL
$batch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$batch_id) {
    header("Location: manage_batches.php");
    exit();
}

try {
    // 2. Security Check: Find the batch and verify it belongs to the logged-in coach
    $stmt = $conn->prepare("SELECT id FROM batches WHERE id = ? AND coach_id = ?");
    $stmt->execute([$batch_id, $_SESSION['user_id']]);
    $batch = $stmt->fetch();

    // 3. If the batch exists and belongs to the coach, delete it
    if ($batch) {
        $delete_stmt = $conn->prepare("DELETE FROM batches WHERE id = ?");
        $delete_stmt->execute([$batch_id]);

        // Redirect back to the manage page with a success message
        header("Location: manage_batches.php?status=deleted");
        exit();
    } else {
        // If batch doesn't exist or doesn't belong to the coach, redirect with an error
        header("Location: manage_batches.php?error=unauthorized");
        exit();
    }

} catch (PDOException $e) {
    error_log("Delete Batch Error: " . $e->getMessage());
    header("Location: manage_batches.php?error=dberror");
    exit();
}