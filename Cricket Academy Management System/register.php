<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

// --- Google Login URL ---
$google_login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'scope' => 'openid email profile',
    'redirect_uri' => 'http://localhost/Cricket_Academy/google-callback.php',
    'response_type' => 'code',
    'client_id' => '96787370218-tbpl4p9a2tlhp8imsnquq2uq7cvp3k22.apps.googleusercontent.com'
]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username)) { $errors[] = "Username is required."; }
        if (empty($email)) { $errors[] = "A valid email is required."; }
        if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters long."; }
        if ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email is already in use.";
            }
        }

        if (empty($errors)) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'player')"
                );
                $stmt->execute([$username, $email, $hashed_password]);

                header("Location: login.php?registration=success");
                exit();
            } catch (PDOException $e) {
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Please try again later.";
            }
        }
    }
}

$page_title = "Register";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Cricket Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
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
            /* Added for standard header */
            --primary-color: #0a2540;
            --secondary-color: #00d1b2;
            --border-radius: 8px;
            --font-family: 'Poppins', sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%); min-height: 100vh; line-height: 1.6; }

        /* --- STYLES FOR STANDARD HEADER --- */
        .header { background: rgba(255, 255, 255, 0.95); box-shadow: var(--shadow); position: sticky; top: 0; z-index: 1000; padding: 1rem 0; font-family: var(--font-family); }
        .header .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .header .logo { font-size: 1.5rem; font-weight: 600; color: var(--primary-color); display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .header .logo i { color: var(--secondary-color); }
        .header .main-nav { display: flex; align-items: center; }
        .header .main-nav a { color: var(--dark); text-decoration: none; font-weight: 500; transition: var(--transition); padding: 8px 0; position: relative; margin: 0 15px; }
        .header .main-nav a:not(.btn)::after { content: ''; position: absolute; width: 0; height: 2px; bottom: -5px; left: 0; background-color: var(--secondary-color); transition: var(--transition); }
        .header .main-nav a:not(.btn):hover::after { width: 100%; }
        .header .btn { display: inline-block; padding: 12px 28px; border-radius: var(--border-radius); border: 2px solid transparent; font-weight: 600; font-size: 1rem; cursor: pointer; text-align: center; }
        .header .btn-outline { color: var(--secondary-color); border-color: var(--secondary-color); }
        .header .btn-outline:hover { background-color: var(--secondary-color); color: white; }
        .logo-text { position: relative; overflow: hidden; }
        .logo-text::after { content: ''; position: absolute; top: 0; left: 0; width: 30px; height: 100%; background: rgba(255, 255, 255, 0.6); transform: translateX(-50px) skewX(-25deg); animation: shine-sweep 4s infinite 2s; }
        @keyframes shine-sweep { 100% { transform: translateX(250px) skewX(-25deg); } }
        /* --- END OF HEADER STYLES --- */

        /* Register Container Styles */
        .register-page-container { display: flex; flex-direction: column; min-height: 100vh; }
        .register-main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .register-container { display: flex; width: 100%; max-width: 950px; min-height: 550px; background: white; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); }
        .register-left { flex: 1.2; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 40px; display: none; flex-direction: column; justify-content: center; align-items: center; text-align: center; position: relative; overflow: hidden; }
        .cricket-icon { width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin-bottom: 25px; position: relative; z-index: 1; }
        .cricket-icon i { font-size: 50px; color: white; }
        .register-left h2 { font-size: 28px; margin-bottom: 15px; position: relative; z-index: 1; color: white;}
        .register-left p { opacity: 0.9; max-width: 300px; position: relative; z-index: 1; font-size: 16px; line-height: 1.5; }
        .register-right { flex: 1; padding: 40px 35px; display: flex; flex-direction: column; justify-content: center; }
        .register-header { text-align: center; margin-bottom: 30px; }
        .register-header h1 { color: var(--primary); font-size: 26px; margin-bottom: 10px; font-weight: 600; }
        .register-header p { color: var(--gray); font-size: 15px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert.error { background-color: #ffeaea; color: var(--danger); border-left: 4px solid var(--danger); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark); font-size: 14px; }
        .form-group input { width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 6px; font-size: 15px; transition: var(--transition); }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 184, 156, 0.2); }
        .register-right .btn { display: block; width: 100%; padding: 14px; background-color: var(--primary); color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .register-right .btn:hover { background-color: var(--primary-dark); transform: translateY(-2px); }
        .divider { display: flex; align-items: center; margin: 25px 0; color: var(--gray); }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background-color: var(--border); }
        .divider span { padding: 0 15px; font-size: 14px; }
        .google-btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 12px; background: white; color: #757575; border: 1px solid var(--border); border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; transition: var(--transition); text-decoration: none; }
        .google-btn:hover { background-color: #f5f5f5; transform: translateY(-2px); }
        .google-btn svg { width: 20px; height: 20px; margin-right: 10px; }
        .auth-footer { text-align: center; margin-top: 30px; font-size: 14px; color: var(--gray); }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; transition: var(--transition); }
        .auth-footer a:hover { text-decoration: underline; }
        .copyright { text-align: center; margin-top: 30px; color: var(--gray); font-size: 13px; }
        .footer { background: var(--dark); color: white; padding: 20px 0; text-align: center; }
        .footer-bottom { opacity: 0.7; font-size: 14px; }
        @media (min-width: 768px) {
            .register-left { display: flex; }
        }
        @media (max-width: 768px) {
            .register-container { flex-direction: column; max-width: 450px; min-height: 0; }
            .register-left { padding: 30px 20px; }
        }
    </style>
</head>
<body>
<div class="register-page-container">
    
    <header class="header">
        <div class="container">
            <a href="<?= BASE_URL ?>index.php" class="logo">
                <i class="fa-solid fa-person-running"></i>
                <span class="logo-text">Cricket Academy</span>
            </a>
            <nav class="main-nav">
                <a href="index.php">Home</a>
                <a href="login.php" class="btn btn-outline">‎ ‎‎ ‎  ‎ Login‎‎ ‎‎ ‎ ‎ ‎   </a>
            </nav>
        </div>
    </header>

    <main class="register-main-content">
        <div class="register-container">
            <div class="register-left">
                <div class="cricket-icon">
                    <i class="fa-solid fa-person-running"></i>
                </div>
                <h2>Cricket Academy</h2>
                <p>Join our community of cricket enthusiasts and take your skills to the next level</p>
            </div>
            <div class="register-right">
                <div class="register-header">
                    <h1>Create a Player Account</h1>
                    <p>Join our cricket community and start your journey</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert error">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password (min. 8 characters)</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Register with Email</button>
                </form>

                <div class="divider">
                    <span>OR</span>
                </div>

                <a href="<?= htmlspecialchars($google_login_url) ?>" class="google-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 256 262">
                        <path fill="#4285F4" d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027"></path>
                        <path fill="#34A853" d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1"></path>
                        <path fill="#FBBC05" d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.820l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782"></path>
                        <path fill="#EB4335" d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251"></path>
                    </svg>
                    Sign up with Google
                </a>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
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
    // This script is for the old header's mobile menu, which is not used here.
    // document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    //     document.getElementById('navMenu').classList.toggle('active');
    // });
</script>
</body>
</html>