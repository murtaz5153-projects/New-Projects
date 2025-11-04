<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Any logged-in user can access this page
require_login();

$errors = [];
$success = '';
$user_id = $_SESSION['user_id'];

// --- 1. Fetch the user's current data ---
try {
    $stmt = $conn->prepare("SELECT username, email, password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: logout.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Edit Profile Fetch Error: " . $e->getMessage());
    die("A system error occurred while fetching your profile.");
}


// --- 2. Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        // --- Username Validation ---
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            $errors[] = "Username cannot be empty.";
        }
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->execute([$username, $user_id]);
        if ($check_stmt->fetch()) {
            $errors[] = "This username is already in use by another account.";
        }

        // --- Password Validation (only if new password is provided) ---
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $update_password = false;

        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "To change your password, you must enter your current password.";
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = "The current password you entered is incorrect.";
            }
            if (strlen($new_password) < 8) {
                $errors[] = "Your new password must be at least 8 characters long.";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "The new passwords do not match.";
            }
            if (empty($errors)) {
                $update_password = true;
            }
        }
        
        // --- 3. If validation passes, update the database ---
        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET username = :username";
                $params = [':username' => $username, ':id' => $user_id];

                if ($update_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql .= ", password = :password";
                    $params[':password'] = $hashed_password;
                }
                $sql .= " WHERE id = :id";
                
                $update_stmt = $conn->prepare($sql);
                $update_stmt->execute($params);

                if ($user['username'] !== $username) {
                    $_SESSION['username'] = $username;
                }
                
                $success = "Your profile has been updated successfully!";
                
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

            } catch (PDOException $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not update your profile.";
            }
        }
    }
}


$page_title = "Edit Profile";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box" style="max-width: 650px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-user-edit"></i> Edit Your Profile</h2>
                <a href="dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
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

            <form method="POST" action="edit_profile.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="font-size: 1.2rem; margin-top: 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem;">Profile Details</h3>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($user['username']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" readonly disabled value="<?= htmlspecialchars($user['email']) ?>" style="background-color: #f0f0f0; cursor: not-allowed;">
                        <small style="color: var(--text-color-light); font-size: 0.85rem; margin-top: 5px; display: block;">Your email address cannot be changed.</small>
                    </div>
                </div>

                <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 2rem;">
                     <h3 style="font-size: 1.2rem; margin-top: 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem;">Change Password</h3>
                    <p style="font-size: 0.9rem; color: var(--text-color-light); margin-top: -1rem; margin-bottom: 1.5rem;">Leave all password fields blank to keep your current password.</p>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password (min. 8 characters)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>