<?php
require_once __DIR__ . '/../../config.php';

if (!is_admin()) {
    die('Permission Denied.');
}

// Handle Delete
if (isset($_GET['delete'])) {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Invalid CSRF token.');
    }
    unset($_SESSION['csrf_token']);
    delete_announcement((int)$_GET['delete']);
    redirect('manage_announcements.php?deleted=true');
}

// Handle Add
if (isset($_POST['add_announcement'])) {
    validate_csrf_token();
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    if (!empty($title) && !empty($body)) {
        create_announcement($_SESSION['user_id'], $title, $body);
    }
    redirect('manage_announcements.php?added=true');
}

// Handle Update
if (isset($_POST['update_announcement'])) {
    validate_csrf_token();
    $id = (int)$_POST['announcement_id'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    if (!empty($title) && !empty($body) && $id > 0) {
        update_announcement($id, $title, $body);
    }
    redirect('manage_announcements.php?updated=true');
}

$edit_mode = false;
$announcement_to_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $announcement_to_edit = get_announcement_by_id((int)$_GET['edit']);
    if (!$announcement_to_edit) $edit_mode = false;
}

$announcements = get_all_announcements();

require_once __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Announcements</h2>

<h3>Current Announcements</h3>
<table class="forum-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Date Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($announcements)): ?>
            <tr><td colspan="3">No announcements found.</td></tr>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <tr>
                    <td data-label="Title"><?php echo htmlspecialchars($announcement['title']); ?></td>
                    <td data-label="Date Created"><?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></td>
                    <td data-label="Actions">
                        <a href="manage_announcements.php?edit=<?php echo $announcement['id']; ?>" title="Edit"><i class="fa-solid fa-pencil"></i></a> |
                        <a href="manage_announcements.php?delete=<?php echo $announcement['id']; ?>&token=<?php echo generate_csrf_token(); ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this announcement?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;"><?php echo $edit_mode ? 'Edit Announcement' : 'Add New Announcement'; ?></h3>

<form action="manage_announcements.php" method="post">
    <?php if ($edit_mode && $announcement_to_edit): ?>
        <input type="hidden" name="announcement_id" value="<?php echo $announcement_to_edit['id']; ?>">
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <div>
        <label for="title">Title</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($announcement_to_edit['title'] ?? ''); ?>" required>
    </div>
    <div>
        <label for="body">Content</label>
        <textarea name="body" id="body" rows="8" required><?php echo htmlspecialchars($announcement_to_edit['body'] ?? ''); ?></textarea>
    </div>
    <div>
        <?php if ($edit_mode): ?>
            <button type="submit" name="update_announcement">Update Announcement <i class="fa-solid fa-save"></i></button>
            <a href="manage_announcements.php" style="margin-left: 10px;">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add_announcement">Add Announcement <i class="fa-solid fa-plus"></i></button>
        <?php endif; ?>
    </div>
</form>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>