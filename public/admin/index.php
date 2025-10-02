<?php
require_once __DIR__ . '/../../includes/header.php';

// Secure this page
if (!is_admin()) {
    die('Permission Denied. You must be an administrator to access this page.');
}
?>

<style>
/* Basic styles for the admin dashboard */
.admin-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    text-align: center;
    border-radius: 5px;
}

.dashboard-card a {
    text-decoration: none;
    color: #333;
}

.dashboard-card .card-icon {
    font-size: 48px;
    color: #003366;
    margin-bottom: 15px;
}

.dashboard-card .card-title {
    font-size: 18px;
    font-weight: bold;
    color: #003366;
}
</style>

<h2>Admin Panel</h2>
<p>Welcome to the administration control center. Use the tools below to manage the site.</p>

<div class="admin-dashboard" style="margin-top: 20px;">
    <!-- Site Management -->
    <div class="dashboard-card">
        <a href="manage_categories.php">
            <div class="card-icon"><i class="fa-solid fa-sitemap"></i></div>
            <div class="card-title">Manage Categories</div>
        </a>
    </div>
    <div class="dashboard-card">
        <a href="manage_forums.php">
            <div class="card-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="card-title">Manage Forums</div>
        </a>
    </div>
    <div class="dashboard-card">
        <a href="manage_users.php">
            <div class="card-icon"><i class="fa-solid fa-users"></i></div>
            <div class="card-title">Manage Users</div>
        </a>
    </div>

    <!-- Content Management -->
    <div class="dashboard-card">
        <a href="manage_announcements.php">
            <div class="card-icon"><i class="fa-solid fa-bullhorn"></i></div>
            <div class="card-title">Manage Announcements</div>
        </a>
    </div>

    <!-- Settings -->
    <div class="dashboard-card">
        <div class="card-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <div class="card-title">Site Maintenance Mode</div>
        <?php
        $is_maintenance = file_exists(__DIR__ . '/../../maintenance.flag');
        ?>
        <p>Status: <strong><?php echo $is_maintenance ? 'ACTIVE' : 'INACTIVE'; ?></strong></p>
        <form action="toggle_maintenance.php" method="post" style="margin-top: 15px;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" class="<?php echo $is_maintenance ? 'button-danger' : ''; ?>">
                <?php echo $is_maintenance ? 'Disable Maintenance' : 'Enable Maintenance'; ?>
            </button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
