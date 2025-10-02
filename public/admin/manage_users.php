<?php
require_once __DIR__ . '/../../config.php';

// 1. Secure this page
if (!is_admin()) {
    die('Permission Denied.');
}

// 2. Handle Actions

// Handle User Deletion
if (isset($_GET['delete'])) {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Invalid or expired CSRF token.');
    }
    unset($_SESSION['csrf_token']);
    $user_id_to_delete = (int)$_GET['delete'];
    delete_user($user_id_to_delete);
    redirect('manage_users.php?deleted=true');
}

// Handle Role Update
if (isset($_POST['update_role'])) {
    validate_csrf_token();
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    update_user_role($user_id_to_update, $new_role);
    redirect('manage_users.php?updated=true');
}


// 3. Fetch data for display
$users = get_all_users();

// 4. Display Page
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Users</h2>

<?php if (isset($_GET['deleted'])): ?>
    <div class="success" style="background-color: #DFF2BF; padding: 10px; margin-bottom: 15px;">User deleted successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div class="success" style="background-color: #DFF2BF; padding: 10px; margin-bottom: 15px;">User role updated successfully.</div>
<?php endif; ?>

<table class="forum-table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Role</th>
            <th>Join Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td data-label="Username"><a href="../profile.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></a></td>
                <td data-label="Role">
                    <form action="manage_users.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="role">
                            <option value="member" <?php if ($user['role'] === 'member') echo 'selected'; ?>>Member</option>
                            <option value="moderator" <?php if ($user['role'] === 'moderator') echo 'selected'; ?>>Moderator</option>
                            <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <button type="submit" name="update_role" title="Save Role"><i class="fa-solid fa-save"></i></button>
                    </form>
                </td>
                <td data-label="Join Date"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                <td data-label="Actions">
                    <?php if ($_SESSION['user_id'] != $user['id']): // Admins can't delete themselves ?>
                        <a href="manage_users.php?delete=<?php echo $user['id']; ?>&token=<?php echo generate_csrf_token(); ?>" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This will orphan all their posts and topics.');"><i class="fa-solid fa-trash"></i></a>
                    <?php else: ?>
                        (Cannot delete self)
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>