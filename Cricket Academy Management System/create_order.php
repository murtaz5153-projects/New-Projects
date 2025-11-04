<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';
use Razorpay\Api\Api;

require_login();

header('Content-Type: application/json');

try {
    $api = new Api('rzp_test_REehuT6lTDHUEQ', 'BytazGM7b6x4AWRbsG9V2r6v');
    
    // Amount should be in the smallest currency unit (e.g., paise for INR)
    $amount_in_paise = 4999 * 100;
    $currency = 'INR';

    $orderData = [
        'receipt'         => 'rcptid_' . time(),
        'amount'          => $amount_in_paise,
        'currency'        => $currency,
        'payment_capture' => 1 // Auto capture payment
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];

    echo json_encode(['order_id' => $razorpayOrderId, 'amount' => $amount_in_paise, 'currency' => $currency]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Razorpay Order Creation Error: " . $e->getMessage());
    echo json_encode(['error' => 'Could not create order.']);
}