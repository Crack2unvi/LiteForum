<?php
require_once __DIR__ . '/../config.php';

// 1. Security: Check if user is a moderator/admin
if (!is_moderator()) {
    die('Permission denied.');
}

// 2. Security: Validate CSRF token
validate_csrf_token();

// 3. Validate topic_id input
$topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);

if ($topic_id === false) {
    die('Invalid Topic ID.');
}

// Fetch the topic to check if it's global before deleting
$topic = get_topic_by_id($topic_id);
if (!$topic) {
    die('Topic not found.');
}

// 4. Perform deletion
if (delete_topic($topic_id)) {
    // 5. Redirect on success
    if ($topic['is_global']) {
        // If it was a global topic, redirect to the main index
        redirect('index.php?deleted=true');
    } else {
        // If it was a normal topic, redirect to its forum
        // The forum_id should be present in the POST for non-global topics
        $forum_id = filter_input(INPUT_POST, 'forum_id', FILTER_VALIDATE_INT);
        if ($forum_id === false) {
            // Fallback redirect if forum_id is somehow invalid
            redirect('index.php');
        } else {
            redirect('view_forum.php?id=' . $forum_id);
        }
    }
} else {
    // Handle error
    die('An error occurred while deleting the topic.');
}