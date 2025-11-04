<?php declare(strict_types=1);
require_once 'config.php';

if (!isset($_GET['code'])) {
    header('Location: login.php?error=google_auth_failed');
    exit();
}

try {
    // 1. Exchange the authorization code for an access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    curl_close($ch);

    $token_json = json_decode($token_response, true);
    if (!isset($token_json['access_token'])) {
        throw new Exception('Failed to get access token from Google.');
    }
    $access_token = $token_json['access_token'];

    // 2. Use the access token to get the user's profile information
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $userinfo_response = curl_exec($ch);
    curl_close($ch);

    $user_data = json_decode($userinfo_response, true);

    if (!isset($user_data['id']) || !isset($user_data['email'])) {
        throw new Exception('Failed to get user information from Google.');
    }

    // 3. Check if the user exists in your database
    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->execute([$user_data['id'], $user_data['email']]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists, log them in
        $user_id = $user['id'];
        // If they signed up with email first, link their google_id now for future logins
        if (empty($user['google_id'])) {
            $update_stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $update_stmt->execute([$user_data['id'], $user_id]);
        }
    } else {
        // New user via Google, create an account for them
        // Use their Google name as their username
        $username = $user_data['name'] ?? 'User' . time();
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, google_id, role, password) VALUES (?, ?, ?, 'player', ?)");
        // We add a random, secure password as the field is NOT NULL, though it will never be used.
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $insert_stmt->execute([$username, $user_data['email'], $user_data['id'], $random_password]);
        $user_id = $conn->lastInsertId();
    }

    // 4. Create the session to log the user in
    // We need to fetch the user again to get their potentially new record or role
    $final_user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $final_user_stmt->execute([$user_id]);
    $final_user = $final_user_stmt->fetch();
    
    session_regenerate_id(true);
    $_SESSION['user_id'] = $final_user['id'];
    $_SESSION['username'] = $final_user['username'];
    $_SESSION['role'] = $final_user['role'];
    $_SESSION['last_activity'] = time();

    // 5. Redirect to the appropriate dashboard
    $redirect_page = match ($final_user['role']) {
        'admin' => 'admin.php',
        'coach' => 'coach_dashboard.php',
        default => 'dashboard.php',
    };
    header("Location: " . BASE_URL . $redirect_page);
    exit();

}catch (Exception $e) {
    error_log('Google Login Error: ' . $e->getMessage());
    header('Location: login.php?error=google_error');
    exit();
}