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
    delete_category($id_to_delete);
    redirect('manage_categories.php');
}

// Handle Add
if (isset($_POST['add_category'])) {
    validate_csrf_token();
    $title = trim($_POST['title']);
    if (!empty($title)) {
        create_category($title);
    }
    redirect('manage_categories.php');
}

// Handle Update
if (isset($_POST['update_category'])) {
    validate_csrf_token();
    $id_to_update = (int)$_POST['category_id'];
    $title = trim($_POST['title']);
    if (!empty($title) && $id_to_update > 0) {
        update_category($id_to_update, $title);
    }
    redirect('manage_categories.php');
}

// 4. If we are still here, prepare data for displaying the page.
$edit_mode = false;
$category_to_edit = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id_to_edit = (int)$_GET['edit'];
    $category_to_edit = get_category_by_id($id_to_edit);
    if (!$category_to_edit) {
        $edit_mode = false; // Category not found, exit edit mode.
    }
}
$categories_with_forums = get_categories_and_forums();

// 5. NOW we can send HTML.
require_once __DIR__ . '/../../includes/header.php';
?>

<h2>Manage Categories</h2>

<h3>Current Categories</h3>
<table class="forum-table">
    <thead>
        <tr>
            <th>Category Title</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($categories_with_forums)):
            ?>
            <tr><td colspan="2">No categories found.</td></tr>
        <?php else: ?>
            <?php foreach ($categories_with_forums as $category): ?>
                <?php if(empty($category['title'])) continue; // Skip if a category somehow has no title ?>
                <tr>
                    <td data-label="Category Title"><?php echo htmlspecialchars($category['title']); ?></td>
                    <td data-label="Actions">
                        <a href="manage_categories.php?edit=<?php echo $category['id']; ?>" title="Edit"><i class="fa-solid fa-pencil"></i></a> |
                        <a href="manage_categories.php?delete=<?php echo $category['id']; ?>&token=<?php echo generate_csrf_token(); ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.');"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;"><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h3>

<?php if ($edit_mode && $category_to_edit): ?>
    <form action="manage_categories.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="category_id" value="<?php echo $category_to_edit['id']; ?>">
        <div>
            <label for="title">Category Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($category_to_edit['title']); ?>" required>
        </div>
        <div>
            <button type="submit" name="update_category">Update Category <i class="fa-solid fa-save"></i></button>
            <a href="manage_categories.php" style="margin-left: 10px;">Cancel</a>
        </div>
    </form>
<?php else: ?>
    <form action="manage_categories.php" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div>
            <label for="title">Category Title</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div>
            <button type="submit" name="add_category">Add Category <i class="fa-solid fa-plus"></i></button>
        </div>
    </form>
<?php endif; ?>


<?php
require_once __DIR__ . '/../../includes/footer.php';
?>