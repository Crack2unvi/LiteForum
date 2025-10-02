<?php
// This script handles deletion, so it should not output any HTML.
// We need config.php for the session, database, and functions.
require_once __DIR__ . '/../config.php';

// 1. Validate CSRF token from GET parameter.
// This check is now more robust to prevent errors on refresh.
if (!isset($_GET['token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    // We don't need to unset the token here because it either doesn't exist or is invalid.
    die('Invalid or expired CSRF token. Please go back and try again.');
}

// Invalidate the token immediately after a successful validation to prevent reuse.
unset($_SESSION['csrf_token']);

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
    die('Permission Denied. You do not have permission to delete this post.');
}

// 4. Delete the post
delete_post($post_id);

// 5. Redirect back to the topic
redirect('view_topic.php?id=' . $post['topic_id']);

// No HTML output is needed.
?>
