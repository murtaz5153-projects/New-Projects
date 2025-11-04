<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can access this page
require_coach();

$errors = [];
$success = '';

// Get and Validate Player ID from URL
$player_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$player_id) {
    header("Location: view_players.php");
    exit();
}

// Fetch the player's current data, ensuring they are a 'player'
try {
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE id = ? AND role = 'player'");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch();

    if (!$player) {
        header("Location: view_players.php?error=notfound");
        exit();
    }
} catch (PDOException $e) {
    error_log("Coach Edit Player Fetch Error: " . $e->getMessage());
    die("A system error occurred.");
}

// Handle form submission for UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        }

        // Check if new username is taken by ANOTHER user
        if (empty($errors)) {
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->execute([$username, $player_id]);
            if ($check_stmt->fetch()) {
                $errors[] = "This username is already in use by another account.";
            }
        }

        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET username = :username WHERE id = :id AND role = 'player'";
                $update_stmt = $conn->prepare($sql);
                $update_stmt->execute([
                    ':username' => $username,
                    ':id' => $player_id
                ]);
                
                $success = "Player's username updated successfully!";
                // Refresh player data to show updated values in the form
                $stmt->execute([$player_id]);
                $player = $stmt->fetch();

            } catch (PDOException $e) {
                error_log("Coach Update Player Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not update player.";
            }
        }
    }
}

$page_title = "Edit Player";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-user-edit"></i> Edit Player</h2>
                <a href="view_players.php" class="btn btn-sm btn-secondary">Back to Player List</a>
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

            <form method="POST" action="coach_edit_player.php?id=<?= $player_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="username">Player's Username</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($player['username']) ?>">
                </div>

                <div class="form-group">
                    <label for="email">Player's Email (Read-only)</label>
                    <input type="email" id="email" name="email" readonly disabled value="<?= htmlspecialchars($player['email']) ?>" style="background-color: #f0f0f0;">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>