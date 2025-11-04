<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_coach();
$coach_id = $_SESSION['user_id'];

// --- Fetch all the data for the new dashboard ---
try {
    // 1. Get total number of players
    $player_count_stmt = $conn->query("SELECT count(id) FROM users WHERE role = 'player'");
    $player_count = $player_count_stmt->fetchColumn();

    // 2. Get number of batches for this coach
    $batch_count_stmt = $conn->prepare("SELECT count(id) FROM batches WHERE coach_id = ?");
    $batch_count_stmt->execute([$coach_id]);
    $batch_count = $batch_count_stmt->fetchColumn();
    
    // 3. Get the next 3 upcoming sessions for this coach
    $sessions_stmt = $conn->prepare("
        SELECT session_title, session_date, start_time 
        FROM training_sessions 
        WHERE coach_id = ? AND session_date >= CURDATE() 
        ORDER BY session_date ASC, start_time ASC 
        LIMIT 3
    ");
    $sessions_stmt->execute([$coach_id]);
    $upcoming_sessions = $sessions_stmt->fetchAll();
    
    // 4. Get the 5 most recently joined players
    $recent_players_stmt = $conn->query("
        SELECT username, created_at 
        FROM users 
        WHERE role = 'player' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_players = $recent_players_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Coach Dashboard Fetch Error: " . $e->getMessage());
    // Set default values on error
    $player_count = 0;
    $batch_count = 0;
    $upcoming_sessions = [];
    $recent_players = [];
}

$page_title = "Coach Dashboard";
require_once 'header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, Coach <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p>This is your central hub for managing players and batches.</p>
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
                <p class="stat-number"><?= count($upcoming_sessions) ?></p>
            </div>
        </div>
    </div>
    <div class="dashboard-grid">
        <div class="dashboard-card" data-aos="fade-up">
            <h3><i class="fas fa-clipboard-list"></i> Batch Management</h3>
            <p>Organize players into training groups.</p>
             <div class="card-footer">
                <a href="manage_batches.php" class="btn btn-primary">Manage All Batches</a>
            </div>
        </div>
        
        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="100">
            <h3><i class="fas fa-user-check"></i> Player Management</h3>
            <p>View player profiles and update performance stats.</p>
            <div class="action-buttons">
                <a href="view_players.php" class="btn btn-primary">View All Players</a>
                <a href="add_stats.php" class="btn btn-secondary">Add Player Stats</a>
            </div>
        </div>

        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="200">
            <h3><i class="fas fa-calendar-alt"></i> Next Upcoming Sessions</h3>
            <?php if(empty($upcoming_sessions)): ?>
                <p>No upcoming sessions scheduled.</p>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach($upcoming_sessions as $session): ?>
                    <li>
                        <span><?= htmlspecialchars($session['session_title']) ?></span> 
                        <span class="item-meta"><?= date('M j, Y', strtotime($session['session_date'])) ?> at <?= date('g:i A', strtotime($session['start_time'])) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="card-footer">
                <a href="manage_sessions.php" class="btn btn-sm btn-secondary">View All Sessions</a>
            </div>
        </div>

        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="300">
            <h3><i class="fas fa-bolt"></i> Other Actions</h3>
            <p>Additional tools for managing your work.</p>
            <div class="action-buttons vertical">
                 <a href="schedule_session.php" class="btn btn-secondary"><i class="fas fa-calendar-plus"></i> Schedule a New Session</a>
                 <a href="manage_sessions.php" class="btn btn-secondary"><i class="fas fa-calendar-alt"></i> Manage Sessions</a>
            </div>
        </div>
        
        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="400">
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

        <div class="dashboard-card" data-aos="fade-up" data-aos-delay="500">
            <h3><i class="fas fa-bullhorn"></i> Send Announcement</h3>
            <p>Send a notification to your players or a specific batch.</p>
            <div class="card-footer">
                <a href="send_announcement.php" class="btn btn-primary">Send Now</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>