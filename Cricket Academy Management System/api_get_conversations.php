<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

$current_user_id = $_SESSION['user_id'];
$current_user_role = strtolower($_SESSION['role']);
$contacts = [];

try {
    if ($current_user_role === 'player') {
        // Player sees their coach(es) AND all admins
        $sql_coaches = "SELECT DISTINCT u.id, u.username 
                        FROM users u
                        JOIN batches b ON u.id = b.coach_id
                        JOIN batch_players bp ON b.id = bp.batch_id
                        WHERE bp.player_id = ? AND u.role = 'coach'";
        $stmt_coaches = $conn->prepare($sql_coaches);
        $stmt_coaches->execute([$current_user_id]);
        $coaches = $stmt_coaches->fetchAll();

        $sql_admins = "SELECT id, username FROM users WHERE role = 'admin'";
        $stmt_admins = $conn->query($sql_admins);
        $admins = $stmt_admins->fetchAll();

        $contacts_raw = array_merge($coaches, $admins);
        
        $unique_ids = [];
        foreach ($contacts_raw as $contact) {
            if (!in_array($contact['id'], $unique_ids)) {
                $contacts[] = $contact;
                $unique_ids[] = $contact['id'];
            }
        }

    } elseif ($current_user_role === 'coach') {
        // Coach sees their players
        $sql = "SELECT DISTINCT u.id, u.username
                FROM users u
                JOIN batch_players bp ON u.id = bp.player_id
                JOIN batches b ON bp.batch_id = b.id
                WHERE b.coach_id = ? AND u.role = 'player'
                ORDER BY u.username ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$current_user_id]);
        $contacts = $stmt->fetchAll();
        
    } elseif ($current_user_role === 'admin') {
        // Admin sees only players
        $sql = "SELECT id, username FROM users WHERE role = 'player' AND id != ? ORDER BY username ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$current_user_id]);
        $contacts = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Get Conversations Error: " . $e->getMessage());
    $contacts = [];
}

header('Content-Type: application/json');
echo json_encode($contacts);