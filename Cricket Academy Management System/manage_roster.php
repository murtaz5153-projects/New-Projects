<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can access this page
require_coach();

// --- 1. Get and Validate Batch ID ---
$batch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$batch_id) {
    header("Location: manage_batches.php");
    exit();
}

// --- 2. Security Check: Verify this batch belongs to the logged-in coach ---
try {
    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ? AND coach_id = ?");
    $stmt->execute([$batch_id, $_SESSION['user_id']]);
    $batch = $stmt->fetch();

    if (!$batch) {
        // If batch doesn't exist or doesn't belong to this coach, redirect
        header("Location: manage_batches.php?error=notfound");
        exit();
    }
} catch (PDOException $e) {
    error_log("Roster Security Check Error: " . $e->getMessage());
    die("A system error occurred.");
}


// --- 3. Handle Add/Remove Player Actions (POST requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);

        if ($player_id) {
            try {
                // If 'add_player' button was clicked
                if (isset($_POST['add_player'])) {
                    $stmt = $conn->prepare("INSERT INTO batch_players (batch_id, player_id) VALUES (?, ?)");
                    $stmt->execute([$batch_id, $player_id]);

                    // Notify the player they've been added to the batch
                    create_notification(
                        $conn,
                        (int)$player_id,
                        "You have been added to the batch: '" . htmlspecialchars($batch['name']) . "'.",
                        "schedule.php"
                    );
                } 
                // If 'remove_player' button was clicked
                elseif (isset($_POST['remove_player'])) {
                    $stmt = $conn->prepare("DELETE FROM batch_players WHERE batch_id = ? AND player_id = ?");
                    $stmt->execute([$batch_id, $player_id]);
                }
            } catch (PDOException $e) {
                // Could be a duplicate entry error, which we can ignore, or another issue
                error_log("Roster modification error: " . $e->getMessage());
            }
        }
    }
    // Redirect to the same page with a GET request to prevent form resubmission
    header("Location: manage_roster.php?id=" . $batch_id);
    exit();
}


// --- 4. Fetch Player Lists for Display ---
try {
    // Get players already IN this batch
    $roster_stmt = $conn->prepare("
        SELECT u.id, u.username FROM users u
        JOIN batch_players bp ON u.id = bp.player_id
        WHERE bp.batch_id = ? ORDER BY u.username ASC
    ");
    $roster_stmt->execute([$batch_id]);
    $roster_players = $roster_stmt->fetchAll();

    // Get players NOT IN this batch (available players)
    $available_stmt = $conn->prepare("
        SELECT u.id, u.username FROM users u
        WHERE u.role = 'player' AND u.id NOT IN (
            SELECT player_id FROM batch_players WHERE batch_id = ?
        ) ORDER BY u.username ASC
    ");
    $available_stmt->execute([$batch_id]);
    $available_players = $available_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Roster Fetch Error: " . $e->getMessage());
    die("A system error occurred fetching player lists.");
}

$page_title = "Manage Roster: " . htmlspecialchars($batch['name']);
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-user-plus"></i> Manage Roster</h1>
            <a href="manage_batches.php" class="btn btn-sm btn-secondary">Back to Batches</a>
        </div>
        <h2>Batch: <?= htmlspecialchars($batch['name']) ?></h2>
        <p>Add or remove players from this batch's roster.</p>
        
        <div class="roster-container" style="display: flex; gap: 30px; margin-top: 2rem;">

            <div class="roster-column" style="flex: 1;">
                <h3><i class="fas fa-check-circle" style="color: var(--success-color);"></i> In This Batch (<?= count($roster_players) ?>)</h3>
                <div class="item-list-box" style="background: #fff; padding: 15px; border-radius: var(--border-radius); box-shadow: var(--shadow);">
                    <?php if (empty($roster_players)): ?>
                        <p>No players have been added to this batch yet.</p>
                    <?php else: ?>
                        <ul class="item-list">
                            <?php foreach ($roster_players as $player): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center;">
                                    <span><?= htmlspecialchars($player['username']) ?></span>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="player_id" value="<?= $player['id'] ?>">
                                        <button type="submit" name="remove_player" class="btn btn-sm btn-danger" title="Remove Player"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="roster-column" style="flex: 1;">
                <h3><i class="fas fa-list-ul"></i> Available Players (<?= count($available_players) ?>)</h3>
                 <div class="item-list-box" style="background: #fff; padding: 15px; border-radius: var(--border-radius); box-shadow: var(--shadow);">
                    <?php if (empty($available_players)): ?>
                        <p>All available players have been added to this batch.</p>
                    <?php else: ?>
                        <ul class="item-list">
                           <?php foreach ($available_players as $player): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center;">
                                    <span><?= htmlspecialchars($player['username']) ?></span>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="player_id" value="<?= $player['id'] ?>">
                                        <button type="submit" name="add_player" class="btn btn-sm btn-primary" title="Add Player" style="background-color: var(--success-color); border-color: var(--success-color);"><i class="fas fa-plus"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>