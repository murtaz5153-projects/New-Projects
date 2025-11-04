<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role_to_check = strtolower(trim($_SESSION['role']));
    $redirect_page = match ($role_to_check) {
        'admin' => 'admin.php',
        'coach' => 'coach_dashboard.php',
        default => 'dashboard.php',
    };
    header("Location: " . BASE_URL . $redirect_page);
    exit();
}

// Logic to change the heading
$login_heading = "Player & Member Login";
$is_coach_login = false;
if (isset($_GET['user']) && $_GET['user'] === 'coach') {
    $login_heading = "Coach Login";
    $is_coach_login = true;
}

$error = '';
$success = '';

if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $success = "Registration successful! Please log in.";
}
if (isset($_GET['reason']) && $_GET['reason'] === 'session_expired') {
    $error = "Your session has expired. Please log in again.";
}

// --- Google Login URL ---
$google_login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'scope' => 'openid email profile',
    'redirect_uri' => 'http://localhost/Cricket_Academy/google-callback.php',
    'response_type' => 'code',
    'client_id' => '96787370218-tbpl4p9a2tlhp8imsnquq2uq7cvp3k22.apps.googleusercontent.com'

]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid request.";
    } else {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];

        if (!$email || empty($password)) {
            $error = "Please enter a valid email and password.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, username, password, role, subscription_status, login_attempts, lockout_time FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS && $user['lockout_time'] && time() < strtotime($user['lockout_time']) + LOCKOUT_DURATION) {
                        $error = "Your account is temporarily locked. Please try again later.";
                    } elseif (password_verify($password, $user['password'])) {
                        $conn->prepare("UPDATE users SET login_attempts = 0, lockout_time = NULL WHERE id = ?")->execute([$user['id']]);
                        
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['subscription_status'] = $user['subscription_status'];
                        $_SESSION['last_activity'] = time();

                        $cleaned_role = strtolower(trim($user['role']));
                        $redirect_page = match ($cleaned_role) {
                            'admin' => 'admin.php',
                            'coach' => 'coach_dashboard.php',
                            default => 'dashboard.php',
                        };
                        header("Location: " . BASE_URL . $redirect_page);
                        exit();

                    } else {
                        $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1, lockout_time = NOW() WHERE id = ?")->execute([$user['id']]);
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } catch (PDOException $e) {
                error_log("Login Error: " . $e->getMessage());
                $error = "A system error occurred. Please try again.";
            }
        }
    }
}

$page_title = $login_heading;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Cricket Academy</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>styles.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Your custom login page styles remain untouched */
        :root {
            --primary: #00b89c;
            --primary-dark: #008872;
            --primary-light: #33c6b0;
            --accent: #ff6b6b;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --success: #28a745;
            --danger: #dc3545;
            --border: #dee2e6;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Login Container Styles */
        .login-page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 950px;
            min-height: 550px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .login-left {
            flex: 1.2;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        /* ... All of your other custom styles are here ... */

        .cricket-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .cricket-icon i {
            font-size: 50px;
            color: white;
        }

        .login-left h2 {
            font-size: 28px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .login-left p {
            opacity: 0.9;
            max-width: 300px;
            position: relative;
            z-index: 1;
            font-size: 16px;
            line-height: 1.5;
        }

        .login-right {
            flex: 1;
            padding: 40px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 26px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .login-header p {
            color: var(--gray);
            font-size: 15px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.error {
            background-color: #ffeaea;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.success {
            background-color: #eaffea;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 15px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 184, 156, 0.2);
        }

        .forgot-password {
            text-align: right;
            margin: -10px 0 20px;
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-right .btn { /* More specific selector */
            display: block;
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-right .btn:hover { /* More specific selector */
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: var(--gray);
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border);
        }

        .divider span {
            padding: 0 15px;
            font-size: 14px;
        }

        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            background: white;
            color: #757575;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .google-btn:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
        }

        .google-btn svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: var(--gray);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .auth-footer hr {
            margin: 20px 0;
            border: 0;
            border-top: 1px solid var(--border);
        }

        .copyright {
            text-align: center;
            margin-top: 30px;
            color: var(--gray);
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 450px;
                min-height: 0;
            }
            
            .login-left {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="login-page-container">
    
    <header class="header">
        <div class="container">
            <a href="<?= BASE_URL ?>index.php" class="logo">
                <i class="fa-solid fa-person-running"></i>
                <span class="logo-text">Cricket Academy</span>
            </a>
            <nav class="main-nav">
                <a href="<?= BASE_URL ?>index.php">Home</a>
                <a href="<?= BASE_URL ?>register.php" class="btn btn-outline">Register</a>
            </nav>
        </div>
    </header>

    <main class="login-main-content">
        <div class="login-container">
            <div class="login-left">
                <div class="cricket-icon">
                    <i class="fa-solid fa-person-running"></i>
                </div>
                <h2>Cricket Academy</h2>
                <p>Develop your skills, master the game, and join our winning team</p>
            </div>
            <div class="login-right">
                <div class="login-header">
                    <h1><?= htmlspecialchars($login_heading) ?></h1>
                    <p>Enter your credentials to access your account</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php<?= $is_coach_login ? '?user=coach' : '' ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>

                <div class="divider">
                    <span>OR</span>
                </div>

                <a href="<?= htmlspecialchars($google_login_url) ?>" style="text-decoration:none;">
                    <button type="button" class="google-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 256 262">
                            <path fill="#4285F4" d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"></path>
                            <path fill="#34A853" d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"></path>
                            <path fill="#FBBC05" d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.820l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"></path>
                            <path fill="#EB4335" d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"></path>
                        </svg>
                        Sign in with Google
                    </button>
                </a>
                
                <div class="auth-footer">
                    <?php if ($is_coach_login): ?>
                        <p>Not a coach? <a href="login.php">Return to Player Login</a></p>
                    <?php else: ?>
                        <p>New Player? <a href="register.php">Create an account</a></p>
                        <hr>
                        <p>Are you a Coach? <a href="login.php?user=coach">Login here</a></p>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; 2025 Cricket Academy. All Rights Reserved.</p>
        </div>
    </footer>
</div>
<script>
    // Mobile menu toggle - This might not be needed if the new header doesn't have a mobile toggle
</script>
</body>
</html>
