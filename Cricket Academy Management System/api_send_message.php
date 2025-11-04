<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
$body = trim($_POST['message'] ?? '');

if (!$receiver_id || empty($body)) {
    http_response_code(400);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id FROM Conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $conversation = $stmt->fetch();

    if ($conversation) {
        $conversation_id = $conversation['id'];
    } else {
        $insert_conv_stmt = $conn->prepare("INSERT INTO Conversations (user1_id, user2_id) VALUES (?, ?)");
        $insert_conv_stmt->execute([$sender_id, $receiver_id]);
        $conversation_id = $conn->lastInsertId();
    }
    
    $insert_msg_stmt = $conn->prepare("INSERT INTO Messages (conversation_id, sender_id, receiver_id, body) VALUES (?, ?, ?, ?)");
    $insert_msg_stmt->execute([$conversation_id, $sender_id, $receiver_id, $body]);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Send Message Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'A server error occurred in send_message.']);
}