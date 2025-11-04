<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

$sender_id = $_SESSION['user_id'];
$receiver_id = filter_input(INPUT_GET, 'receiver_id', FILTER_VALIDATE_INT);
$messages = [];

if (!$receiver_id) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id FROM Conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $conversation = $stmt->fetch();

    if ($conversation) {
        $conversation_id = $conversation['id'];
        $msg_stmt = $conn->prepare("SELECT * FROM Messages WHERE conversation_id = ? ORDER BY created_at ASC");
        $msg_stmt->execute([$conversation_id]);
        $messages = $msg_stmt->fetchAll();
        $read_stmt = $conn->prepare("UPDATE Messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?");
        $read_stmt->execute([$conversation_id, $sender_id]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Get Messages Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred in get_messages.']);
    exit();
}

header('Content-Type: application/json');
echo json_encode($messages);