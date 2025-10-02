<?php
// includes/header.php

// The config file is needed on all pages
require_once __DIR__ . '/../config.php';

// Enforce maintenance mode
$maintenance_flag_path = __DIR__ . '/../maintenance.flag';
if (file_exists($maintenance_flag_path) && !is_admin() && !str_ends_with($_SERVER['SCRIPT_NAME'], 'login.php')) {
    http_response_code(503);
    die('<!DOCTYPE html><html><head><title>Maintenance</title><style>body{font-family: sans-serif; text-align: center; padding-top: 50px;}</style></head><body><h1>Our Site is Currently Under Maintenance</h1><p>We will be back online shortly. Thank you for your patience.</p></body></html>');
}

// Update user's last seen timestamp on every page load if they are logged in.
if (is_logged_in()) {
    update_user_last_seen($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>

<div class="container">
    <header class="main-header">
        <div class="header-left">
            <h1><a href="/index.php"><?php echo SITE_NAME; ?></a></h1>
        </div>
    
        <div class="header-center">
            <form action="/search.php" method="get" class="header-search-form">
                <input type="search" name="q" placeholder="Search forums..." required>
                <button type="submit" title="Search"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>
    
        <div class="header-right">
            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="/admin/index.php" title="Admin Panel"><i class="fa-solid fa-user-shield"></i></a>
                <?php endif; ?>
                <?php 
                    $unread_count = get_unread_notification_count($_SESSION['user_id']);
                    $display_count = ($unread_count > 0) ? '<span class="notification-count">' . $unread_count . '</span>' : '';
                ?>
                <a href="/notifications.php" title="Notifications" class="notification-link"><i class="fa-solid fa-bell"></i><?php echo $display_count; ?></a>
                <?php 
                    $unread_pm_count = get_unread_pm_count($_SESSION['user_id']);
                    $pm_display_count = ($unread_pm_count > 0) ? '<span class="notification-count">' . $unread_pm_count . '</span>' : '';
                ?>
                <a href="/pm_inbox.php" title="Private Messages" class="notification-link"><i class="fa-solid fa-envelope"></i><?php echo $pm_display_count; ?></a>
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="/logout.php" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a href="/register.php">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main>