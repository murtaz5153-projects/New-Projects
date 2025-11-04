<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

$user_id = $_SESSION['user_id'];
$notifications = [];

try {
    // Fetch all notifications for the user, newest first
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    // Mark all notifications as read for this user
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update_stmt->execute([$user_id]);

} catch (PDOException $e) {
    error_log("Notifications Page Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching your notifications.";
}

// --- NEW LOGIC: Determine the correct dashboard link based on user role ---
$dashboard_link = 'dashboard.php'; // Default for players
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'coach') {
        $dashboard_link = 'coach_dashboard.php';
    } elseif ($_SESSION['role'] === 'admin') {
        $dashboard_link = 'admin.php';
    }
}

$page_title = "My Notifications";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <a href="<?= htmlspecialchars($dashboard_link) ?>" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php elseif (empty($notifications)): ?>
            <div class="alert"><p>You have no notifications.</p></div>
        <?php else: ?>
            <div class="item-list-box" style="background: #fff; padding: 0 15px; border-radius: var(--border-radius); box-shadow: var(--shadow);">
                <ul class="item-list">
                    <?php foreach ($notifications as $notification): ?>
                        <li style="padding: 1rem 0; align-items: flex-start;">
                            <div>
                                <?php if ($notification['link']): ?>
                                    <a href="<?= htmlspecialchars($notification['link']) ?>" style="color: var(--primary-color); font-weight: 500; text-decoration: none;">
                                        <?= htmlspecialchars($notification['message']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--primary-color); font-weight: 500;">
                                        <?= htmlspecialchars($notification['message']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="item-meta">
                                <?= date('M j, Y, g:i A', strtotime($notification['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>