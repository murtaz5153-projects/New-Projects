<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

$errors = [];
$success = '';
$token_is_valid = false;
$user_id = null;

$token = $_GET['token'] ?? null;
$email = $_GET['email'] ?? null;

if ($token && $email) {
    try {
        $stmt = $conn->prepare("SELECT id, reset_token FROM users WHERE email = ? AND reset_expires > NOW()");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($token, $user['reset_token'])) {
            $token_is_valid = true;
            $user_id = $user['id'];
        }
    } catch (PDOException $e) {
        error_log("Reset Password token check error: " . $e->getMessage());
        $errors[] = "A system error occurred. Please try again later.";
    }
}

if ($token_is_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters long."; }
        if ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }

        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
                );
                $update_stmt->execute([$hashed_password, $user_id]);
                $success = "Your password has been successfully reset! You can now log in.";
            } catch (PDOException $e) {
                error_log("Reset Password update error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not update your password.";
            }
        }
    }
}

$page_title = "Reset Password";
require_once 'header.php';
?>
<div class="auth-container">
    <div class="auth-box">
        <h2>Set Your New Password</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert error">
                <?php foreach ($errors as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success">
                <p><?= htmlspecialchars($success) ?></p>
                <a href="login.php" class="btn btn-primary" style="margin-top: 1rem;">Go to Login</a>
            </div>
        <?php elseif ($token_is_valid): ?>
            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>&email=<?= htmlspecialchars($email) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="password">New Password (min. 8 characters)</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Set New Password</button>
            </form>
        <?php else: ?>
            <div class="alert error">
                <p>This password reset link is invalid or has expired.</p>
                <a href="forgot_password.php" style="text-decoration: underline;">Request a new one here.</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>