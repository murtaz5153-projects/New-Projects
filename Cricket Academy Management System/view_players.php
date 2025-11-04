<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Protect the page - only coaches can access this
require_coach();

// Fetch all players from the database
$players = []; 
try {
    // Prepare the SQL query to select only users with the 'player' role
    $stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE role = 'player' ORDER BY username ASC");
    $stmt->execute();
    $players = $stmt->fetchAll();
} catch (PDOException $e) {
    // Log the error and show a generic message to the user
    error_log("View Players Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching player data. Please try again later.";
}

$page_title = "View All Players";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel"> 
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-users"></i> All Players</h1>
            <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        <p>A list of all players currently registered in the academy.</p>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php else: ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Member Since</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($players)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No players have registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td><?= htmlspecialchars($player['username']) ?></td>
                                    <td><?= htmlspecialchars($player['email']) ?></td>
                                    <td><?= date('F j, Y', strtotime($player['created_at'])) ?></td>
                                    <td class="actions">
                                        <a href="coach_edit_player.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-secondary" title="Edit Player"><i class="fas fa-edit"></i></a>
                                        <a href="view_player_stats.php?id=<?= $player['id'] ?>" class="btn btn-sm btn-secondary" title="View Stats"><i class="fas fa-chart-line"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'footer.php';
?>