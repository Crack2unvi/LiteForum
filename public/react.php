<?php
require_once __DIR__ . '/../config.php';

// 1. User must be logged in to react
if (!is_logged_in()) {
    die('Tepki vermek için giriş yapmalısınız.');
}

// 2. Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3. Security: Validate CSRF token
    validate_csrf_token();

    // 4. Validate inputs
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
    $emoji = trim($_POST['emoji'] ?? '');

    // A basic check to ensure the emoji is a simple emoji character.
    // This is not a perfect validation but prevents obvious abuse.
    if ($post_id && $topic_id && !empty($emoji) && mb_strlen($emoji) <= 2) {
        // 5. Call the toggle function
        toggle_reaction($post_id, $_SESSION['user_id'], $emoji, $topic_id);

        // 6. Redirect back to the post
        redirect('view_topic.php?id=' . $topic_id . '#post-' . $post_id);
    } else {
        die('Geçersiz istek.');
    }
} else {
    // Redirect if accessed directly via GET
    redirect('index.php');
}
