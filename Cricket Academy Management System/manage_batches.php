<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only logged-in coaches can manage batches
require_coach();

$batches = [];
try {
    // Select only the batches created by the currently logged-in coach
    $stmt = $conn->prepare("SELECT * FROM batches WHERE coach_id = ? ORDER BY start_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $batches = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Batches Error: " . $e->getMessage());
    $error_message = "A system error occurred while fetching your batches.";
}

$page_title = "Manage Batches";
require_once 'header.php';
?>

<div class="container">
    <div class="admin-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h1><i class="fas fa-clipboard-list"></i> Manage Your Batches</h1>
            <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
        </div>
        <p>Here are all the batches you have created. You can manage players and schedules for each batch from here.</p>
        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="alert success">Batch was successfully deleted.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert error">Could not perform the requested action.</div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php elseif (empty($batches)): ?>
            <div class="alert">
                You have not created any batches yet. <a href="create_batch.php">Create your first batch!</a>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($batches as $batch): ?>
                    <div class="dashboard-card">
                        <h3><?= htmlspecialchars($batch['name']) ?></h3>
                        <div class="profile-info">
                            <p><strong><i class="fas fa-calendar-alt"></i> Timings:</strong> <?= htmlspecialchars($batch['timings'] ?: 'Not set') ?></p>
                            <p><strong><i class="fas fa-calendar-check"></i> Starts:</strong> <?= date('M j, Y', strtotime($batch['start_date'])) ?></p>
                            <p><strong><i class="fas fa-calendar-times"></i> Ends:</strong> <?= date('M j, Y', strtotime($batch['end_date'])) ?></p>
                            
                            <p><strong><i class="fas fa-users"></i> Max Students:</strong> <?= $batch['max_students'] ?></p>
                            
                            <p><strong><i class="fas fa-dollar-sign"></i> Fees:</strong> $<?= number_format((float)$batch['fees'], 2) ?></p>
                            <?php if (!empty($batch['description'])): ?>
                                <p><strong><i class="fas fa-info-circle"></i> Description:</strong> <?= nl2br(htmlspecialchars($batch['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer" style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 1rem;">
                            <a href="edit_batch.php?id=<?= $batch['id'] ?>" class="btn btn-sm btn-secondary" title="Edit Batch"><i class="fas fa-edit"></i> Edit</a>
                            <a href="manage_roster.php?id=<?= $batch['id'] ?>" class="btn btn-sm btn-secondary" title="Manage Roster"><i class="fas fa-user-plus"></i> Add Players</a>
                            <a href="delete_batch.php?id=<?= $batch['id'] ?>" class="btn btn-sm btn-danger" title="Delete Batch" onclick="return confirm('Are you sure you want to delete this batch?');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>