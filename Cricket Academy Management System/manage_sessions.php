<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_coach();

$coach_id = $_SESSION['user_id'];
$upcoming_sessions = [];
$past_sessions = [];

try {
    // Fetch all sessions created by this coach
    $stmt = $conn->prepare("
        SELECT ts.id, ts.session_title, ts.session_date, ts.start_time, ts.end_time, b.name as batch_name
        FROM training_sessions ts
        JOIN batches b ON ts.batch_id = b.id
        WHERE ts.coach_id = ?
        ORDER BY ts.session_date DESC, ts.start_time DESC
    ");
    $stmt->execute([$coach_id]);
    $all_sessions = $stmt->fetchAll();

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
    $upcoming_sessions = array_reverse($upcoming_sessions);

} catch (PDOException $e) {
    error_log("Manage Sessions Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching your sessions.";
}

$page_title = "Manage Sessions";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-calendar-alt"></i> Manage Your Sessions</h1>
            <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        <p>Here you can edit or cancel your upcoming training sessions.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="alert success">Session was successfully deleted.</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div class="alert success">Session was successfully updated.</div>
        <?php endif; ?>

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
                            <th>Session Title</th>
                            <th>Batch</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcoming_sessions)): ?>
                            <tr><td colspan="5">You have no upcoming sessions scheduled.</td></tr>
                        <?php else: ?>
                            <?php foreach ($upcoming_sessions as $session): ?>
                            <tr>
                                <td><?= date('D, M j, Y', strtotime($session['session_date'])) ?></td>
                                <td><?= date('g:i A', strtotime($session['start_time'])) ?> - <?= date('g:i A', strtotime($session['end_time'])) ?></td>
                                <td><?= htmlspecialchars($session['session_title']) ?></td>
                                <td><?= htmlspecialchars($session['batch_name']) ?></td>
                                <td class="actions">
                                    <a href="edit_session.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-secondary" title="Edit Session"><i class="fas fa-edit"></i></a>
                                    <a href="delete_session.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-danger" title="Delete Session" onclick="return confirm('Are you sure you want to delete this session? This will notify all players in the batch.');"><i class="fas fa-trash"></i></a>
                                </td>
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
                            <th>Session Title</th>
                            <th>Batch</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($past_sessions)): ?>
                            <tr><td colspan="3">You have no past session history.</td></tr>
                        <?php else: ?>
                            <?php foreach ($past_sessions as $session): ?>
                            <tr>
                                <td><?= date('D, M j, Y', strtotime($session['session_date'])) ?></td>
                                <td><?= htmlspecialchars($session['session_title']) ?></td>
                                <td><?= htmlspecialchars($session['batch_name']) ?></td>
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