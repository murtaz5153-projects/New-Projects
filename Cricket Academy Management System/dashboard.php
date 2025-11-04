<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

try {
    // Fetch the user's full data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: logout.php");
        exit();
    }
    
    $_SESSION['subscription_status'] = $user['subscription_status'];

} catch (PDOException $e) {
    error_log("Dashboard user fetch error: " . $e->getMessage());
    die("Error loading user data.");
}

// === THE PAYWALL LOGIC ===
if ($user['role'] === 'player' && $user['subscription_status'] !== 'active') {
    header("Location: subscribe.php");
    exit();
}

// --- Fetch player-specific data only if they are an active player ---
$stats = [];
$player_info = null;
if ($user['role'] === 'player') {
    // Fetch career stats
    $stats_stmt = $conn->prepare("SELECT SUM(runs_scored) as total_runs, SUM(wickets_taken) as total_wickets, COUNT(id) as matches_played FROM player_stats WHERE player_id = ?");
    $stats_stmt->execute([$user['id']]);
    $stats = $stats_stmt->fetch();

    // Fetch batch and coach info
    $player_info_stmt = $conn->prepare("
        SELECT b.name as batch_name, u.username as coach_name
        FROM batch_players bp
        JOIN batches b ON bp.batch_id = b.id
        JOIN users u ON b.coach_id = u.id
        WHERE bp.player_id = ?
        LIMIT 1
    ");
    $player_info_stmt->execute([$user['id']]);
    $player_info = $player_info_stmt->fetch();
}

$page_title = "Player Dashboard";
require_once 'header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, <?= htmlspecialchars($user['username']) ?>!</h1>
        <p>This is your central hub for tracking your performance and schedule.</p>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'subscribed'): ?>
            <div class="alert success" style="margin-top: 1rem;">Welcome aboard! Your subscription is now active.</div>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3><i class="fas fa-user-circle"></i> My Profile</h3>
            <div class="profile-info">
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                 <?php if ($player_info): ?>
                    <p><strong>Current Batch:</strong> <?= htmlspecialchars($player_info['batch_name']) ?></p>
                    <p><strong>Head Coach:</strong> <?= htmlspecialchars($player_info['coach_name']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="edit_profile.php" class="btn btn-sm btn-secondary">Edit Profile</a>
            </div>
        </div>

        <div class="dashboard-card">
            <h3><i class="fas fa-star"></i> Membership Status</h3>
             <div class="stat-item" style="text-align: left; margin: auto 0;">
                <span class="stat-value" style="color: var(--success-color); font-size: 1.8rem;">Active</span>
                <?php if ($user['subscription_expires_at']): ?>
                    <span class="stat-label">Renews on <?= date('F j, Y', strtotime($user['subscription_expires_at'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-buttons vertical" style="margin-top: auto;">
                 <a href="schedule.php" class="btn btn-secondary"><i class="fas fa-calendar-alt"></i> View Full Schedule</a>
                 <a href="view_my_stats.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Detailed Performance</a>
            </div>
        </div>

        <div class="dashboard-card stats-card" style="grid-column: 1 / -1;">
            <h3><i class="fas fa-trophy"></i> Career Snapshot</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['matches_played'] ?? 0 ?></span>
                    <span class="stat-label">Matches Played</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total_runs'] ?? 0 ?></span>
                    <span class="stat-label">Total Runs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $stats['total_wickets'] ?? 0 ?></span>
                    <span class="stat-label">Total Wickets</span>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once 'footer.php'; ?>