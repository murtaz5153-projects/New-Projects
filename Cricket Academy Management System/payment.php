<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

require_login();

if (isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'active') {
    header("Location: dashboard.php");
    exit();
}

$page_title = "Confirm Subscription";
require_once 'header.php';
?>

<div class="container" style="max-width: 500px; padding-top: 40px;">
    <div class="auth-box" style="max-width: 100%;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-shield-alt" style="font-size: 3rem; color: var(--primary-color);"></i>
            <h2 style="margin-top: 1rem;">Secure Payment via Razorpay</h2>
            <p style="color: var(--text-color-light);">Complete your subscription to join the academy.</p>
        </div>

        <div class="order-summary" style="background-color: #f5f7fa; padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem;">
                <span>Total Due Today</span>
                <span>Rs4999.00</span>
            </div>
        </div>
        
        <button id="rzp-button1" class="btn btn-primary" style="width: 100%;">Pay with Razorpay</button>

    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('rzp-button1').onclick = async function (e) {
    e.preventDefault();

    // 1. Create a Razorpay Order on the server
    const orderResponse = await fetch('create_order.php');
    const orderData = await orderResponse.json();

    if (orderData.error || !orderData.order_id) {
        alert('Could not create a payment order. Please try again.');
        return;
    }

    var options = {
        "key": "<?= RAZORPAY_KEY_ID ?>",
        "amount": orderData.amount,
        "currency": orderData.currency,
        "name": "Cricket Academy",
        "description": "Monthly Subscription",
        "order_id": orderData.order_id,
        "handler": function (response){
            // 3. This function is called after successful payment.
            // We now send the payment details to our server for verification.
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_payment.php';

            var fields = {
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            };

            for (var key in fields) {
                var hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = fields[key];
                form.appendChild(hiddenField);
            }

            document.body.appendChild(form);
            form.submit();
        },
        "prefill": {
            "name": "<?= htmlspecialchars($_SESSION['username']) ?>",
            "email": "<?= htmlspecialchars($user['email'] ?? '') ?>"
        },
        "theme": {
            "color": "#00b89c"
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function (response){
        alert("Payment failed: " + response.error.description);
    });
    
    // 2. Open the Razorpay payment window
    rzp1.open();
}
</script>

<?php require_once 'footer.php'; ?>