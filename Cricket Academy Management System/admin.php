<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_admin();
$admin_id = $_SESSION['user_id'];

// --- Fetch all data for the new combined dashboard ---
try {
    // 1. Data for User Management Table
    $all_users = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();

    // 2. Data for Stats Bar (from coach dashboard)
    $player_count_stmt = $conn->query("SELECT count(id) FROM users WHERE role = 'player'");
    $player_count = $player_count_stmt->fetchColumn();

    $batch_count_stmt = $conn->prepare("SELECT count(id) FROM batches WHERE coach_id = ?");
    $batch_count_stmt->execute([$admin_id]);
    $batch_count = $batch_count_stmt->fetchColumn();
    
    $sessions_stmt = $conn->prepare("SELECT session_title FROM training_sessions WHERE coach_id = ? AND session_date >= CURDATE()");
    $sessions_stmt->execute([$admin_id]);
    $upcoming_sessions_count = $sessions_stmt->rowCount();
    
    // 3. Data for "Recently Joined Players" card
    $recent_players_stmt = $conn->query("SELECT username, created_at FROM users WHERE role = 'player' ORDER BY created_at DESC LIMIT 5");
    $recent_players = $recent_players_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Admin Dashboard Fetch Error: " . $e->getMessage());
    die("A critical error occurred while loading dashboard data.");
}

$page_title = "Admin Dashboard";
require_once 'header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, Coach <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p>This is your unified hub for managing the entire academy.</p>
    </div>

    <div class="stats-bar">
        <div class="stat-box" data-aos="fade-up">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="info">
                <p class="stat-title">Total Players</p>
                <p class="stat-number"><?= $player_count ?></p>
            </div>
        </div>
        <div class="stat-box" data-aos="fade-up" data-aos-delay="100">
            <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="info">
                <p class="stat-title">Your Batches</p>
                <p class="stat-number"><?= $batch_count ?></p>
            </div>
        </div>
        <div class="stat-box" data-aos="fade-up" data-aos-delay="200">
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <div class="info">
                <p class="stat-title">Upcoming Sessions</p>
                <p class="stat-number"><?= $upcoming_sessions_count ?></p>
            </div>
        </div>
    </div>
    <div class="dashboard-grid">
        <div class="dashboard-card" data-aos="fade-up">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <p>Manage your core activities from here.</p>
            <div class="action-buttons vertical">
                 <a href="schedule_session.php" class="btn btn-secondary"><i class="fas fa-calendar-plus"></i> Schedule a New Session</a>
                 <a href="manage_sessions.php" class="btn btn-secondary"><i class="fas fa-calendar-alt"></i> Manage Sessions</a>
                 <a href="add_stats.php" class="btn btn-secondary"><i class="fas fa-chart-line"></i> Add Player Stats</a>
                 <a href="send_announcement.php" class="btn btn-secondary"><i class="fas fa-bullhorn"></i> Send Announcement</a>
            </div>
        </div>
        
        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="100">
            <h3><i class="fas fa-clipboard-list"></i> Batch Management</h3>
            <p>Organize players into training groups.</p>
             <div class="card-footer">
                <a href="manage_batches.php" class="btn btn-primary">Manage All Batches</a>
            </div>
        </div>
        
        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="200">
            <h3><i class="fas fa-user-plus"></i> Recently Joined Players</h3>
            <?php if(empty($recent_players)): ?>
                <p>No new players have joined recently.</p>
            <?php else: ?>
                <ul class="item-list">
                     <?php foreach($recent_players as $player): ?>
                    <li>
                        <span><?= htmlspecialchars($player['username']) ?></span> 
                        <span class="item-meta">Joined on <?= date('M j, Y', strtotime($player['created_at'])) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="card-footer">
                <a href="view_players.php" class="btn btn-sm btn-secondary">View All Players</a>
            </div>
        </div>

        <div class="dashboard-card" data-aos="fade-up" style="grid-column: 1 / -1;">
            <h3><i class="fas fa-users-cog"></i> User Management</h3>
            <?php if (isset($_GET['status']) && $_GET['status'] === 'user_deleted'): ?>
                <div class="alert success" style="margin-top: 1rem;">User was successfully deleted.</div>
            <?php endif; ?>
            <div class="table-container" style="margin-top: 1rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_users)): ?>
                            <tr><td colspan="5">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="role-tag role-<?= htmlspecialchars($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'])) ?></span></td>
                                <td class="actions">
                                     <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash"></i></a>
                                    <?php else: ?>
                                        <span style="color: var(--text-color-light); font-size: 0.9rem;">(You)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>