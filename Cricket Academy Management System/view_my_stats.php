<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only logged-in users can see this page
require_login();

// Although any role can view this page, it will only show stats for the logged-in user.
// We'll fetch stats regardless of role, but the table will only be useful for players.
$stats_records = [];
try {
    // Select all individual stat records for the currently logged-in user
    $stmt = $conn->prepare("
        SELECT ps.match_date, ps.runs_scored, ps.wickets_taken, ps.notes, u.username as coach_name
        FROM player_stats ps
        JOIN users u ON ps.coach_id = u.id
        WHERE ps.player_id = ? 
        ORDER BY ps.match_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats_records = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("View My Stats Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching your performance history.";
}

$page_title = "My Performance History";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel"> <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-history"></i> My Performance History</h1>
            <a href="dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        <p>A detailed log of your performance in every recorded match.</p>

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
                                <td colspan="4" style="text-align: center;">No performance data has been recorded for you yet.</td>
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