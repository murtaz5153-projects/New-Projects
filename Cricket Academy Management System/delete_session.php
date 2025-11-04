<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_coach();

$session_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$session_id) {
    header("Location: manage_sessions.php");
    exit();
}

try {
    // Security Check: Find the session and verify it belongs to the logged-in coach
    $stmt = $conn->prepare("SELECT * FROM training_sessions WHERE id = ? AND coach_id = ?");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if ($session) {
        // First, notify all players in the batch that the session is canceled
        $player_stmt = $conn->prepare("SELECT player_id FROM batch_players WHERE batch_id = ?");
        $player_stmt->execute([$session['batch_id']]);
        $player_ids = $player_stmt->fetchAll(PDO::FETCH_COLUMN);

        $session_date_formatted = date('M j, Y', strtotime($session['session_date']));
        foreach ($player_ids as $player_id) {
            create_notification(
                $conn,
                (int)$player_id,
                "CANCELED: The session '" . htmlspecialchars($session['session_title']) . "' on " . $session_date_formatted . " has been canceled.",
                "schedule.php"
            );
        }

        // Now, delete the session from the database
        $delete_stmt = $conn->prepare("DELETE FROM training_sessions WHERE id = ?");
        $delete_stmt->execute([$session_id]);

        header("Location: manage_sessions.php?status=deleted");
        exit();
    } else {
        // If session doesn't exist or doesn't belong to the coach, redirect
        header("Location: manage_sessions.php?error=unauthorized");
        exit();
    }

} catch (PDOException $e) {
    error_log("Delete Session Error: " . $e->getMessage());
    header("Location: manage_sessions.php?error=dberror");
    exit();
}