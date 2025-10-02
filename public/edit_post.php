<?php
// 1. Load config and functions first.
require_once __DIR__ . '/../config.php';

// 2. Get post ID and fetch post data
$post_id = (int)($_GET['id'] ?? 0);
if ($post_id <= 0) {
    die('Invalid post ID.');
}
$post = get_post_by_id($post_id);
if (!$post) {
    die('Post not found.');
}

// 3. Security Check: Ensure user is owner or moderator
if (!is_logged_in() || !(is_owner($post['user_id']) || is_moderator())) {
    die('Permission Denied. You do not have permission to edit this post.');
}

// 4. Handle form submission (POST request)
if (isset($_POST['update_post'])) {
    validate_csrf_token();
    $body = trim($_POST['body']);
    if (!empty($body)) {
        update_post($post_id, $body);
        // Redirect back to the topic after editing. This now works.
        redirect('view_topic.php?id=' . $post['topic_id'] . '#post-' . $post_id);
    }
    // If body is empty, we fall through and just re-display the edit form.
}

// 5. If we are here, it's a GET request. Now we can send HTML.
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Edit Post</h2>

<form action="edit_post.php?id=<?php echo $post_id; ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <div>
        <label for="body">Message</label>
        <textarea name="body" id="body" rows="10" required><?php echo htmlspecialchars($post['body']); ?></textarea>
    </div>
    <div>
        <button type="submit" name="update_post">Update Post</button>
        <a href="view_topic.php?id=<?php echo $post['topic_id']; ?>" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
