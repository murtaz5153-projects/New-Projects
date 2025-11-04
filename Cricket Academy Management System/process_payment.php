<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

require_login();

$success = true;
$error = "An unknown error occurred.";

if (empty($_POST['razorpay_payment_id']) === false) {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $success = false;
        $error = "Invalid request. Please try again.";
    } else {
        $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        
        try {
            // 2. Verify Razorpay's signature to confirm payment is authentic
            $attributes = [
                'razorpay_order_id' => $_POST['razorpay_order_id'],
                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                'razorpay_signature' => $_POST['razorpay_signature']
            ];
            $api->utility->verifyPaymentSignature($attributes);
            
            $user_id = $_SESSION['user_id'];
            $amount = 4999.00; // Match the amount from the order
            $transaction_id = $_POST['razorpay_payment_id'];

            $conn->beginTransaction();

            $payment_stmt = $conn->prepare("INSERT INTO payments (enrollment_id, amount, payment_date, transaction_id, status) VALUES (?, ?, NOW(), ?, 'completed')");
            $payment_stmt->execute([$user_id, $amount, $transaction_id]);

            $user_stmt = $conn->prepare("UPDATE users SET subscription_status = 'active', subscription_expires_at = NOW() + INTERVAL 1 MONTH WHERE id = ?");
            $user_stmt->execute([$user_id]);
            
            $conn->commit();
            $_SESSION['subscription_status'] = 'active';
            
            header("Location: dashboard.php?status=subscribed");
            exit();

        } catch(SignatureVerificationError $e) {
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
            error_log($error);
        } catch (PDOException $e) {
            $conn->rollBack();
            $success = false;
            $error = "A database error occurred. Your payment was successful but we could not activate your account. Please contact support.";
            error_log("DB Error after Razorpay success: " . $e->getMessage());
        }
    }
} else {
    $success = false;
    $error = "Invalid payment details.";
}

// If we reach here, something went wrong. Show an error page.
$page_title = "Payment Failed";
require_once 'header.php';
?>
<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <h2>Payment Failed</h2>
            <div class="alert error">
                <p><?= htmlspecialchars($error) ?></p>
                <p>Please try again or contact support if the problem persists.</p>
            </div>
            <a href="subscribe.php" class="btn btn-secondary">Try Again</a>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>