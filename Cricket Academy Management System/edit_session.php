<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_coach();

$errors = [];
$coach_id = $_SESSION['user_id'];
$session_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$session_id) {
    header("Location: manage_sessions.php");
    exit();
}

// --- 1. Security Check & Fetch current session data ---
try {
    // Ensure the session exists and belongs to the logged-in coach
    $stmt = $conn->prepare("SELECT * FROM training_sessions WHERE id = ? AND coach_id = ?");
    $stmt->execute([$session_id, $coach_id]);
    $session = $stmt->fetch();

    if (!$session) {
        header("Location: manage_sessions.php?error=notfound");
        exit();
    }
} catch (PDOException $e) {
    error_log("Edit Session Fetch Error: " . $e->getMessage());
    die("A system error occurred.");
}

// --- 2. Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and validate inputs
        $title = trim($_POST['session_title'] ?? '');
        $description = trim($_POST['session_description'] ?? '');
        $date = trim($_POST['session_date'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (empty($title)) { $errors[] = "Session Title is required."; }
        if (empty($date)) { $errors[] = "Session Date is required."; }
        if (empty($start_time) || empty($end_time)) { $errors[] = "Start and End times are required."; }
        if ($end_time <= $start_time) { $errors[] = "End time must be after the start time."; }

        if (empty($errors)) {
            try {
                $sql = "UPDATE training_sessions SET 
                            session_title = :title, 
                            session_description = :description, 
                            session_date = :date, 
                            start_time = :start_time, 
                            end_time = :end_time, 
                            location = :location
                        WHERE id = :id AND coach_id = :coach_id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':date' => $date,
                    ':start_time' => $start_time,
                    ':end_time' => $end_time,
                    ':location' => $location,
                    ':id' => $session_id,
                    ':coach_id' => $coach_id
                ]);

                // Notify all players in the batch about the update
                $player_stmt = $conn->prepare("SELECT player_id FROM batch_players WHERE batch_id = ?");
                $player_stmt->execute([$session['batch_id']]);
                $player_ids = $player_stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($player_ids as $player_id) {
                    create_notification(
                        $conn,
                        (int)$player_id,
                        "The session '" . htmlspecialchars($title) . "' has been updated. Please check the schedule for new details.",
                        "schedule.php"
                    );
                }

                header("Location: manage_sessions.php?status=updated");
                exit();

            } catch (PDOException $e) {
                error_log("Update Session Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not update the session.";
            }
        }
    }
}

$page_title = "Edit Session";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-edit"></i> Edit Session</h2>
                <a href="manage_sessions.php" class="btn btn-sm btn-secondary">Back to Sessions</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error"><?php foreach ($errors as $error) echo "<p>" . htmlspecialchars($error) . "</p>"; ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_session.php?id=<?= $session_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="session_title">Session Title</label>
                    <input type="text" id="session_title" name="session_title" required value="<?= htmlspecialchars($session['session_title']) ?>">
                </div>

                <div class="form-group">
                    <label for="session_date">Date</label>
                    <input type="date" id="session_date" name="session_date" required value="<?= htmlspecialchars($session['session_date']) ?>">
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required value="<?= htmlspecialchars($session['start_time']) ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required value="<?= htmlspecialchars($session['end_time']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($session['location']) ?>">
                </div>

                <div class="form-group">
                    <label for="session_description">Session Description (Optional)</label>
                    <textarea id="session_description" name="session_description" rows="3"><?= htmlspecialchars($session['session_description']) ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>