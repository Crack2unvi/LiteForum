<?php
// 1. Load config and functions first. No HTML output.
require_once __DIR__ . '/../../config.php';

// 2. Secure this page
if (!is_admin()) {
    die('Permission Denied.');
}

// 3. Handle all actions that might cause a redirect.
// Handle Delete
if (isset($_GET['delete'])) {
    // CSRF Protection for GET request
    if (!isset($_GET['token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Invalid or expired CSRF token.');
    }
    unset($_SESSION['csrf_token']); // Invalidate token after use

    $id_to_delete = (int)$_GET['delete'];
    delete_forum($id_to_delete);
    redirect('manage_forums.php');
}

// Handle Add
if (isset($_POST['add_forum'])) {
    validate_csrf_token();
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    if (!empty($title) && $category_id > 0) {
        create_forum($title, $description, $category_id);
    }
    redirect('manage_forums.php');
}

// Handle Update
if (isset($_POST['update_forum'])) {
    validate_csrf_token();
    $id_to_update = (int)$_POST['forum_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    if (!empty($title) && $category_id > 0 && $id_to_update > 0) {
        update_forum($id_to_update, $title, $description, $category_id);
    }
    redirect('manage_forums.php');
}

// 4. If we are still here, prepare data for displaying the page.
$edit_mode = false;
$forum_to_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['edit'];
    $forum_to_edit = get_forum_by_id($id_to_edit);
    if (!$forum_to_edit) {
        $edit_mode = false; // Forum not found, exit edit mode.
    }
}

$all_categories = get_categories_and_forums();
$category_list = [];
foreach($all_categories as $cat) {
    if (isset($cat['id'])) {
        $category_list[] = ['id' => $cat['id'], 'title' => $cat['title']];
    }
}

// 5. NOW we can send HTML.
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Forums</h2>

<h3>Current Forums</h3>
<table class="forum-table">
    <thead>
        <tr>
            <th>Forum Title</th>
            <th>Category</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($all_categories as $category): ?>
            <?php if (empty($category['forums'])) continue; ?>
            <?php foreach ($category['forums'] as $forum): ?>
                <tr>
                    <td data-label="Forum Title"><?php echo htmlspecialchars($forum['title']); ?></td>
                    <td data-label="Category"><?php echo htmlspecialchars($category['title']); ?></td>
                    <td data-label="Actions">
                        <a href="manage_forums.php?edit=<?php echo $forum['id']; ?>" title="Edit"><i class="fa-solid fa-pencil"></i></a> |
                        <a href="manage_forums.php?delete=<?php echo $forum['id']; ?>&token=<?php echo generate_csrf_token(); ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this forum?');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;"><?php echo $edit_mode ? 'Edit Forum' : 'Add New Forum'; ?></h3>

<?php if ($edit_mode && $forum_to_edit): ?>
    <form action="manage_forums.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="forum_id" value="<?php echo $forum_to_edit['id']; ?>">
        <div>
            <label for="title">Forum Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($forum_to_edit['title']); ?>" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea name="description" id="description"><?php echo htmlspecialchars($forum_to_edit['description']); ?></textarea>
        </div>
        <div>
            <label for="category_id">Parent Category</label>
            <select name="category_id" id="category_id" required>
                <option value="">-- Select a Category --</option>
                <?php foreach ($category_list as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php if ($category['id'] == $forum_to_edit['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" name="update_forum">Update Forum <i class="fa-solid fa-save"></i></button>
            <a href="manage_forums.php" style="margin-left: 10px;">Cancel</a>
        </div>
    </form>
<?php else: ?>
    <form action="manage_forums.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div>
            <label for="title">Forum Title</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea name="description" id="description"></textarea>
        </div>
        <div>
            <label for="category_id">Parent Category</label>
            <select name="category_id" id="category_id" required>
                <option value="">-- Select a Category --</option>
                <?php foreach ($category_list as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" name="add_forum">Add Forum <i class="fa-solid fa-plus"></i></button>
        </div>
    </form>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>