<?php
// LiteForum Installer
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 1;
$base_path = __DIR__;
$db_file = $base_path . '/forum.sqlite';

// --- Helper Functions ---
function check_requirements() {
    $errors = [];
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = 'PHP version 8.0.0 or higher is required. You are running ' . PHP_VERSION . '.';
    }
    if (!extension_loaded('sqlite3')) {
        $errors[] = 'The `php-sqlite3` extension is not enabled.';
    }
    if (!is_writable($base_path)) {
        $errors[] = 'The script needs write permissions for the directory: ' . htmlspecialchars($base_path) . ' to create the database file.';
    }
    return $errors;
}

function run_database_script() {
    if (file_exists($base_path . '/database.php')) {
        try {
            require_once $base_path . '/database.php';
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    return false;
}

function run_seed_script() {
    if (file_exists($base_path . '/seed.php')) {
        try {
            require_once $base_path . '/seed.php';
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    return false;
}

function cleanup_files() {
    $deleted = [];
    if (file_exists($base_path . '/database.php')) {
        if (@unlink($base_path . '/database.php')) $deleted[] = 'database.php';
    }
    if (file_exists($base_path . '/seed.php')) {
        if (@unlink($base_path . '/seed.php')) $deleted[] = 'seed.php';
    }
    // Self-delete
    if (@unlink(__FILE__)) $deleted[] = 'install.php';
    return $deleted;
}

// --- Step Logic ---
if ($step == 2) {
    if (!file_exists($db_file)) {
        run_database_script();
    }
}
if ($step == 3) {
    run_seed_script();
}
if ($step == 4) {
    $deleted_files = cleanup_files();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LiteForum Installer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.6; color: #333; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #05386b; }
        .btn { display: inline-block; padding: 12px 25px; background-color: #0a4c99; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn:hover { background-color: #05386b; }
        .btn.disabled { background-color: #ccc; cursor: not-allowed; }
        .error, .success, .info { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .error { background-color: #ffd2d2; border: 1px solid #d8000c; color: #d8000c; }
        .success { background-color: #dff2bf; border: 1px solid #4f8a10; color: #4f8a10; }
        .info { background-color: #e7f3ff; border: 1px solid #a3c9f5; color: #0a4c99; }
        ul { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>LiteForum Installer</h1>

        <?php if ($step == 1): ?>
            <h2>Step 1: Pre-installation Check</h2>
            <?php $errors = check_requirements(); ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <strong>Please fix the following issues before proceeding:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <a href="install.php" class="btn disabled">Proceed</a>
            <?php else: ?>
                <div class="success">All requirements are met. You are ready to install.</div>
                <p>This process will create the `forum.sqlite` database file and set up the necessary tables.</p>
                <a href="install.php?step=2" class="btn">Create Database</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($step == 2): ?>
            <h2>Step 2: Database Creation</h2>
            <?php if (file_exists($db_file)): ?>
                <div class="success">Database file `forum.sqlite` created successfully.</div>
                <p>Next, you can optionally seed the database with a default admin user and sample content.</p>
                <a href="install.php?step=3" class="btn">Seed Database (Optional)</a>
                <a href="install.php?step=4" style="margin-left: 10px;">Skip and Finalize</a>
            <?php else: ?>
                <div class="error">Failed to create the database file. Please check file permissions.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($step == 3): ?>
            <h2>Step 3: Database Seeding</h2>
            <div class="success">Database seeded successfully. Default admin credentials are <strong>user:</strong> admin / <strong>pass:</strong> password123</div>
            <p>Now, let's clean up the installation files.</p>
            <a href="install.php?step=4" class="btn">Finalize Installation</a>
        <?php endif; ?>

        <?php if ($step == 4): ?>
            <h2>Installation Complete!</h2>
            <div class="success">Installation is complete. The following files have been deleted for security: <?php echo implode(', ', $deleted_files); ?></div>
            <div class="info">
                <strong>Important:</strong> If `install.php` was not deleted automatically, please remove it from your server manually.
            </div>
            <p>You can now visit your forum homepage.</p>
            <a href="index.php" class="btn">Go to Forum</a>
        <?php endif; ?>

    </div>
</body>
</html>
