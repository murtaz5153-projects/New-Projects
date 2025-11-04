<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can schedule sessions
require_coach();

$errors = [];
$success = '';
$coach_id = $_SESSION['user_id'];

// Fetch the batches managed by this coach to populate a dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM batches WHERE coach_id = ? ORDER BY name ASC");
    $stmt->execute([$coach_id]);
    $coach_batches = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Schedule Session Fetch Batches Error: " . $e->getMessage());
    die("A system error occurred while fetching your batches.");
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and validate inputs
        $batch_id = filter_input(INPUT_POST, 'batch_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['session_title'] ?? '');
        $description = trim($_POST['session_description'] ?? '');
        $date = trim($_POST['session_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (!$batch_id) { $errors[] = "You must select a batch."; }
        if (empty($title)) { $errors[] = "Session Title is required."; }
        if (empty($date)) { $errors[] = "Session Date is required."; }
        if (empty($start_time) || empty($end_time)) { $errors[] = "Start and End times are required."; }
        if ($end_time <= $start_time) { $errors[] = "End time must be after the start time."; }

        if (empty($errors)) {
            try {
                $sql = "INSERT INTO training_sessions (batch_id, coach_id, session_title, session_description, session_date, start_time, end_time, location)
                        VALUES (:batch_id, :coach_id, :title, :description, :date, :start_time, :end_time, :location)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':batch_id' => $batch_id,
                    ':coach_id' => $coach_id,
                    ':title' => $title,
                    ':description' => $description,
                    ':date' => $date,
                    ':start_time' => $start_time,
                    ':end_time' => $end_time,
                    ':location' => $location
                ]);

                $success = "New session scheduled successfully!";

                // Notify all players in the batch
                $player_stmt = $conn->prepare("SELECT player_id FROM batch_players WHERE batch_id = ?");
                $player_stmt->execute([$batch_id]);
                $player_ids = $player_stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($player_ids as $player_id) {
                    create_notification(
                        $conn,
                        (int)$player_id,
                        "A new session '" . htmlspecialchars($title) . "' has been scheduled.",
                        "schedule.php"
                    );
                }

            } catch (PDOException $e) {
                error_log("Schedule Session Insert Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not schedule the session.";
            }
        }
    }
}

$page_title = "Schedule a New Session";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-calendar-plus"></i> Schedule Session</h2>
                <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error"><?php foreach ($errors as $error) echo "<p>" . htmlspecialchars($error) . "</p>"; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="schedule_session.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="batch_id">Select Batch</label>
                    <select id="batch_id" name="batch_id" required>
                        <option value="">-- Choose a Batch --</option>
                        <?php foreach ($coach_batches as $batch): ?>
                            <option value="<?= $batch['id'] ?>"><?= htmlspecialchars($batch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="session_title">Session Title (e.g., Batting Practice)</label>
                    <input type="text" id="session_title" name="session_title" required>
                </div>

                <div class="form-group">
                    <label for="session_date">Date</label>
                    <input type="date" id="session_date" name="session_date" required>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location (e.g., Main Ground)</label>
                    <input type="text" id="location" name="location">
                </div>

                <div class="form-group">
                    <label for="session_description">Session Description (Optional)</label>
                    <textarea id="session_description" name="session_description" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Schedule Session</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>