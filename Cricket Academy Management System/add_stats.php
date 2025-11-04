<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can access this page
require_coach();

$errors = [];
$success = '';

// --- 1. Fetch all players to populate the dropdown menu ---
try {
    $player_stmt = $conn->query("SELECT id, username FROM users WHERE role = 'player' ORDER BY username ASC");
    $players = $player_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Add Stats Player Fetch Error: " . $e->getMessage());
    // If we can't fetch players, we can't do anything else, so stop.
    die("A system error occurred while fetching the player list.");
}


// --- 2. Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and validate inputs
        $player_id = filter_input(INPUT_POST, 'player_id', FILTER_VALIDATE_INT);
        $match_date = trim($_POST['match_date'] ?? '');
        $runs_scored = filter_input(INPUT_POST, 'runs_scored', FILTER_VALIDATE_INT, ["options" => ["default" => 0]]);
        $wickets_taken = filter_input(INPUT_POST, 'wickets_taken', FILTER_VALIDATE_INT, ["options" => ["default" => 0]]);
        $notes = trim($_POST['notes'] ?? '');

        // Validation checks
        if (!$player_id) { $errors[] = "You must select a player."; }
        if (empty($match_date)) { $errors[] = "Match Date is required."; }
        if ($runs_scored === false || $runs_scored < 0) { $errors[] = "Runs Scored must be a valid, non-negative number."; }
        if ($wickets_taken === false || $wickets_taken < 0) { $errors[] = "Wickets Taken must be a valid, non-negative number."; }

        // If validation passes, insert the stats
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO player_stats (player_id, coach_id, match_date, runs_scored, wickets_taken, notes)
                        VALUES (:player_id, :coach_id, :match_date, :runs_scored, :wickets_taken, :notes)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':player_id' => $player_id,
                    ':coach_id' => $_SESSION['user_id'],
                    ':match_date' => $match_date,
                    ':runs_scored' => $runs_scored,
                    ':wickets_taken' => $wickets_taken,
                    ':notes' => $notes
                ]);

                $success = "Stats successfully added for the selected player!";
                
                // Notify the specific player
                create_notification(
                    $conn,
                    (int)$player_id,
                    "Your coach has added new performance stats for you.",
                    "view_my_stats.php"
                );


            } catch (PDOException $e) {
                error_log("Add Stats Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not save the stats.";
            }
        }
    }
}

$page_title = "Add Player Stats";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-chart-line"></i> Add Player Stats</h2>
                <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="add_stats.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="player_id">Select Player</label>
                    <select id="player_id" name="player_id" required>
                        <option value="">-- Choose a Player --</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="match_date">Match Date</label>
                    <input type="date" id="match_date" name="match_date" required>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="runs_scored">Runs Scored</label>
                        <input type="number" id="runs_scored" name="runs_scored" min="0" value="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="wickets_taken">Wickets Taken</label>
                        <input type="number" id="wickets_taken" name="wickets_taken" min="0" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Coach's Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Stats</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>