<?php
$page_title = $page_title ?? 'Cricket Academy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Cricket Academy</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <?php
            // --- UPDATED LOGO LINK LOGIC ---
            if (is_logged_in() && isset($_SESSION['role'])) {
                $dashboard_url = match (strtolower($_SESSION['role'])) {
                    'admin' => BASE_URL . 'admin.php',
                    'coach' => BASE_URL . 'coach_dashboard.php',
                    'player' => BASE_URL . 'dashboard.php',
                    default => BASE_URL . 'index.php',
                };
                echo '<a href="' . htmlspecialchars($dashboard_url) . '" class="logo">';
            } else {
                echo '<a href="' . BASE_URL . 'index.php" class="logo">';
            }
            ?>
                <i class="fa-solid fa-person-running"></i>
                <span class="logo-text">Cricket Academy</span>
            </a>

            <nav class="main-nav">
                <?php if (is_logged_in()): ?>
                    <?php
                    $is_active_member = false;
                    if (isset($_SESSION['role'])) {
                        $role = strtolower($_SESSION['role']);
                        if ($role === 'coach' || $role === 'admin') {
                            $is_active_member = true;
                        } elseif ($role === 'player' && isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'active') {
                            $is_active_member = true;
                        }
                    }

                    $unread_notifications = 0;
                    $unread_chats = 0;
                    if ($is_active_member) {
                        try {
                            $notify_stmt = $conn->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0");
                            $notify_stmt->execute([$_SESSION['user_id']]);
                            $unread_notifications = $notify_stmt->fetchColumn();

                            $chat_stmt = $conn->prepare("SELECT COUNT(id) FROM Messages WHERE receiver_id = ? AND is_read = 0");
                            $chat_stmt->execute([$_SESSION['user_id']]);
                            $unread_chats = $chat_stmt->fetchColumn();
                        } catch (PDOException $e) {
                            error_log("Header notification/chat count failed: " . $e->getMessage());
                        }
                    }
                    ?>
                    
                    <?php if (strtolower($_SESSION['role']) === 'player'): ?>
                        <a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    
                    <?php if ($is_active_member): ?>
                        <a href="<?= BASE_URL ?>chat.php">
                            Chat
                            <?php if ($unread_chats > 0): ?>
                                <span class="nav-badge"><?= $unread_chats ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <a href="<?= BASE_URL ?>notifications.php" class="<?= ($unread_notifications > 0) ? 'has-notifications' : '' ?>">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="nav-badge" style="top: -5px; right: -10px;"><?= $unread_notifications ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <?php if (strtolower($_SESSION['role']) === 'coach'): ?>
                        <a href="<?= BASE_URL ?>coach_dashboard.php">Coach Panel</a>
                    <?php endif; ?>
                     <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
                        <a href="<?= BASE_URL ?>admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php">Login</a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-outline">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php if (!isset($no_main_container)): ?>
    <main class="container">
    <?php endif; 
    ?>