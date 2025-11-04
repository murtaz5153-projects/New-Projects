<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_coach();

$errors = [];
$success = '';
$coach_id = $_SESSION['user_id'];

// Fetch the batches managed by this coach to populate a dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM batches WHERE coach_id = ? ORDER BY name ASC");
    $stmt->execute([$coach_id]);
    $coach_batches = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Send Announcement Fetch Batches Error: " . $e->getMessage());
    die("A system error occurred while fetching your batches.");
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        $target = $_POST['target'] ?? '';
        $message = trim($_POST['message'] ?? '');

        if (empty($target)) { $errors[] = "You must select a target audience."; }
        if (empty($message)) { $errors[] = "The announcement message cannot be empty."; }

        if (empty($errors)) {
            try {
                $player_ids = [];
                // Determine the list of players to notify
                if ($target === 'all') {
                    // Get all unique players from all of the coach's batches
                    $stmt = $conn->prepare("
                        SELECT DISTINCT bp.player_id 
                        FROM batch_players bp
                        JOIN batches b ON bp.batch_id = b.id
                        WHERE b.coach_id = ?
                    ");
                    $stmt->execute([$coach_id]);
                    $player_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    // Get players from a specific batch, ensuring the coach owns the batch
                    $batch_id = (int)$target;
                    $stmt = $conn->prepare("
                        SELECT bp.player_id 
                        FROM batch_players bp
                        JOIN batches b ON bp.batch_id = b.id
                        WHERE bp.batch_id = ? AND b.coach_id = ?
                    ");
                    $stmt->execute([$batch_id, $coach_id]);
                    $player_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }

                if (empty($player_ids)) {
                    $errors[] = "There are no players in the selected group to notify.";
                } else {
                    // Send notification to each player
                    foreach ($player_ids as $player_id) {
                        create_notification(
                            $conn,
                            (int)$player_id,
                            "Announcement: " . $message,
                            "notifications.php"
                        );
                    }
                    $success = "Announcement sent successfully to " . count($player_ids) . " player(s)!";
                }
            } catch (PDOException $e) {
                error_log("Send Announcement Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not send the announcement.";
            }
        }
    }
}

$page_title = "Send Announcement";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-bullhorn"></i> Send Announcement</h2>
                <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error"><?php foreach ($errors as $error) echo "<p>" . htmlspecialchars($error) . "</p>"; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <p>Send a notification to your players. The message will appear in their notification panel.</p>

            <form method="POST" action="send_announcement.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="target">Send To:</label>
                    <select id="target" name="target" required>
                        <option value="">-- Choose an Audience --</option>
                        <option value="all">All My Players</option>
                        <option disabled>--- Batches ---</option>
                        <?php foreach ($coach_batches as $batch): ?>
                            <option value="<?= $batch['id'] ?>"><?= htmlspecialchars($batch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" required placeholder="e.g., Practice is canceled tomorrow due to rain."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>