<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can access this page
require_coach();

// --- 1. Get and Validate Player ID from URL ---
$player_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$player_id) {
    header("Location: view_players.php");
    exit();
}

// --- 2. Fetch Player's Name and Verify they are a player ---
try {
    $player_stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role = 'player'");
    $player_stmt->execute([$player_id]);
    $player = $player_stmt->fetch();

    if (!$player) {
        // If the user is not found or is not a player, redirect
        header("Location: view_players.php?error=notfound");
        exit();
    }
} catch (PDOException $e) {
    error_log("View Player Stats Fetch Name Error: " . $e->getMessage());
    die("A system error occurred.");
}


// --- 3. Fetch all stat records for this specific player ---
$stats_records = [];
try {
    $stmt = $conn->prepare("
        SELECT ps.match_date, ps.runs_scored, ps.wickets_taken, ps.notes, u.username as coach_name
        FROM player_stats ps
        JOIN users u ON ps.coach_id = u.id
        WHERE ps.player_id = ? 
        ORDER BY ps.match_date DESC
    ");
    $stmt->execute([$player_id]);
    $stats_records = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("View Player Stats Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching performance history.";
}

$page_title = "Stats for " . htmlspecialchars($player['username']);
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-chart-line"></i> Performance History</h1>
            <a href="view_players.php" class="btn btn-sm btn-secondary">Back to Player List</a>
        </div>
        <h2>Player: <?= htmlspecialchars($player['username']) ?></h2>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php else: ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Match Date</th>
                            <th>Runs Scored</th>
                            <th>Wickets Taken</th>
                            <th>Coach's Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats_records)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No performance data has been recorded for this player yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats_records as $record): ?>
                                <tr>
                                    <td><?= date('F j, Y', strtotime($record['match_date'])) ?></td>
                                    <td><?= $record['runs_scored'] ?></td>
                                    <td><?= $record['wickets_taken'] ?></td>
                                    <td><?= nl2br(htmlspecialchars($record['notes'] ?: 'No notes provided.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>