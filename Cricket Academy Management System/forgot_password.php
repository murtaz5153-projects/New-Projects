<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Manually include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $error = "Please enter a valid email address.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                $message = "If an account with that email exists, a password reset link has been sent.";

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $hashed_token = password_hash($token, PASSWORD_DEFAULT);
                        
                    // --- THE FIX: Let the database set the expiry time ---
                    $update_stmt = $conn->prepare(
                        "UPDATE users SET reset_token = ?, reset_expires = NOW() + INTERVAL 1 HOUR WHERE email = ?"
                    );
                    $update_stmt->execute([$hashed_token, $email]);
                        
                    $reset_link = BASE_URL . "reset_password.php?token=$token&email=" . urlencode($email);
                    
                    $mail = new PHPMailer(true);
                    try {
                        // Email sending logic remains the same...
                        $mail->isSMTP();
                        $mail->Host       = SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = SMTP_USER;
                        $mail->Password   = SMTP_PASS;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = SMTP_PORT;

                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request';
                        $mail->Body = '
                            <body style="margin: 0; padding: 0; font-family: Poppins, sans-serif; background-color: #f4f4f4;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="padding: 20px 0;">
                                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                                                <tr>
                                                    <td align="center" style="padding: 40px 0 30px 0; background-color: #0a2540; border-radius: 8px 8px 0 0;">
                                                        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Cricket Academy</h1>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 40px 30px;">
                                                        <h2 style="color: #0a2540; margin-top: 0;">Password Reset Request</h2>
                                                        <p style="color: #4a4a4a; line-height: 1.6;">Hello,</p>
                                                        <p style="color: #4a4a4a; line-height: 1.6;">We received a request to reset the password for your account. To proceed, please click the button below. This link will be valid for one hour.</p>
                                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                            <tr>
                                                                <td align="center" style="padding: 20px 0;">
                                                                    <a href="' . $reset_link . '" style="background-color: #00d1b2; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block;">Reset Your Password</a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <p style="color: #4a4a4a; line-height: 1.6;">If you did not request a password reset, you can safely ignore this email. Your account will remain secure.</p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center" style="padding: 20px 30px; background-color: #f4f4f4; border-radius: 0 0 8px 8px;">
                                                        <p style="margin: 0; color: #888; font-size: 12px;">&copy; ' . date('Y') . ' Cricket Academy. All rights reserved.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </body>
                        ';
                        $mail->AltBody = "To reset your password, please visit this link: " . $reset_link;

                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Password reset email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                    }
                }
            } catch(PDOException $e) {
                error_log("Password reset error: " . $e->getMessage());
                $error = "A system error occurred. Please try again later.";
            }
        }
    }
}
$page_title = "Forgot Password";
require_once 'header.php';
?>
<div class="auth-container">
    <div class="auth-box">
        <h2>Reset Your Password</h2>
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" action="forgot_password.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
        <p class="auth-footer">Remembered your password? <a href="login.php">Back to Login</a></p>
    </div>
</div>
<?php require_once 'footer.php'; ?>