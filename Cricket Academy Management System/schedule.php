<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Any logged-in user can view this page
require_login();

$player_id = $_SESSION['user_id'];
$upcoming_sessions = [];
$past_sessions = [];

try {
    // Find all batches the player is enrolled in
    $batch_ids_stmt = $conn->prepare("SELECT batch_id FROM batch_players WHERE player_id = ?");
    $batch_ids_stmt->execute([$player_id]);
    $batch_ids = $batch_ids_stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($batch_ids)) {
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($batch_ids), '?'));

        // Fetch all sessions for those batches
        $sql = "
            SELECT ts.*, b.name as batch_name, u.username as coach_name
            FROM training_sessions ts
            JOIN batches b ON ts.batch_id = b.id
            JOIN users u ON ts.coach_id = u.id
            WHERE ts.batch_id IN ($placeholders)
            ORDER BY ts.session_date DESC, ts.start_time DESC
        ";
        $sessions_stmt = $conn->prepare($sql);
        $sessions_stmt->execute($batch_ids);
        $all_sessions = $sessions_stmt->fetchAll();

        // Separate sessions into upcoming and past
        $today = new DateTime('today');
        foreach ($all_sessions as $session) {
            $session_date = new DateTime($session['session_date']);
            if ($session_date >= $today) {
                $upcoming_sessions[] = $session;
            } else {
                $past_sessions[] = $session;
            }
        }
        // Reverse upcoming sessions to show nearest first
        $upcoming_sessions = array_reverse($upcoming_sessions);
    }
} catch (PDOException $e) {
    error_log("View Schedule Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching your schedule.";
}

$page_title = "My Full Schedule";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-calendar-alt"></i> My Schedule</h1>
            <a href="dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        <p>A complete log of your upcoming and past training sessions.</p>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php else: ?>
            
            <h2 style="margin-top: 2rem;">Upcoming Sessions</h2>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Session</th>
                            <th>Batch</th>
                            <th>Coach</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcoming_sessions)): ?>
                            <tr><td colspan="6">You have no upcoming sessions scheduled.</td></tr>
                        <?php else: ?>
                            <?php foreach ($upcoming_sessions as $session): ?>
                            <tr>
                                <td><?= date('D, M j, Y', strtotime($session['session_date'])) ?></td>
                                <td><?= date('g:i A', strtotime($session['start_time'])) ?> - <?= date('g:i A', strtotime($session['end_time'])) ?></td>
                                <td><?= htmlspecialchars($session['session_title']) ?></td>
                                <td><?= htmlspecialchars($session['batch_name']) ?></td>
                                <td><?= htmlspecialchars($session['coach_name']) ?></td>
                                <td><?= htmlspecialchars($session['location'] ?: 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 2rem;">Past Sessions</h2>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Session</th>
                            <th>Batch</th>
                            <th>Coach</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($past_sessions)): ?>
                            <tr><td colspan="4">You have no past session history.</td></tr>
                        <?php else: ?>
                            <?php foreach ($past_sessions as $session): ?>
                            <tr>
                                <td><?= date('D, M j, Y', strtotime($session['session_date'])) ?></td>
                                <td><?= htmlspecialchars($session['session_title']) ?></td>
                                <td><?= htmlspecialchars($session['batch_name']) ?></td>
                                <td><?= htmlspecialchars($session['coach_name']) ?></td>
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