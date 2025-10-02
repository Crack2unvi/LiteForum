<?php
// The config file contains all our functions and session start, but does not output HTML.
require_once __DIR__ . '/../config.php';

// 1. Get topic ID from URL. This is needed for both GET and POST.
$topic_id = (int)($_GET['id'] ?? 0);
if ($topic_id <= 0) {
    die('Invalid topic ID.');
}

// 2. Handle the POST request for a new reply BEFORE any HTML is sent.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    validate_csrf_token();

    $body = trim($_POST['body'] ?? '');

    if (!empty($body)) {
        $new_post_id = create_post($topic_id, $_SESSION['user_id'], $body);
        if ($new_post_id) {
            // Redirect to the new post's anchor to prevent page jump
            redirect('view_topic.php?id=' . $topic_id . '#post-' . $new_post_id);
        }
        // Optionally, handle the error case, e.g., by setting an error message.
        // For now, we just redirect on success.
    }
}

// 3. Handle GET request for quoting a post.
$quote_text = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['quote'])) {
    $quote_post_id = (int)$_GET['quote'];
    if ($quote_post_id > 0) {
        $quoted_post = get_post_by_id($quote_post_id);
        if ($quoted_post && $quoted_post['topic_id'] == $topic_id) { // Ensure post is in the same topic
            $quoted_author = get_user_by_id($quoted_post['user_id']);
            if ($quoted_author) {
                // Strip any existing quotes from the body to prevent messy nesting.
                $stripped_body = preg_replace('/[quote=.*?](.*?)[\]/is', '$1', $quoted_post['body']);
                // Now, create the new quote with the cleaned body.
                $quote_text = "[quote=" . htmlspecialchars($quoted_author['username']) . "]" . htmlspecialchars(trim($stripped_body)) . "[/quote]\n";
            }
        }
    }
}

// 4. If we are still here, it's a GET request. Now we can display the page.
require_once __DIR__ . '/../includes/header.php';

// 5. Fetch topic and post data for display.
$topic = get_topic_by_id($topic_id);
$posts = get_posts_by_topic($topic_id);

// 5. Handle topic not found.
if (!$topic) {
    // We can show a proper error message now that the header is included.
    echo "<h2>Error</h2><p>Topic not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

?>

<h2><?php echo htmlspecialchars($topic['title']); ?></h2>

<?php if (is_moderator()): ?>
    <div class="moderation-controls" style="margin-bottom: 1em;">
        <form action="delete_topic.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this topic and all its posts? This action cannot be undone.');" style="display: inline;">
            <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
            <input type="hidden" name="forum_id" value="<?php echo $topic['forum_id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" class="button-danger">Delete Topic</button>
        </form>
    </div>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
<div class="post-container" id="post-<?php echo $post['id']; ?>">
    <div class="post-user-info">
        <strong><a href="profile.php?id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a></strong>
        <br>
        <small>Role: <?php echo htmlspecialchars($post['role']); ?></small>
        <br>
        <small>Joined: <?php echo date('M Y', strtotime($post['user_joined'])); ?></small>
    </div>
    <div class="post-body">
        <div class="post-meta">
            Posted: <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?>
            <div class="post-mod-links" style="float: right;">
                <?php
                $links = [];
                if (is_logged_in()) {
                    $links[] = '<a href="view_topic.php?id=' . $topic['id'] . '&quote=' . $post['id'] . '#reply-form" title="Quote"><i class="fa-solid fa-quote-left"></i></a>';
                }
                if (is_logged_in() && (is_owner($post['user_id']) || is_moderator())) {
                    $links[] = '<a href="edit_post.php?id=' . $post['id'] . '" title="Edit"><i class="fa-solid fa-pencil"></i></a>';
                    $links[] = '<a href="delete_post.php?id=' . $post['id'] . '&token=' . generate_csrf_token() . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this post?\');"><i class="fa-solid fa-trash"></i></a>';
                }
                echo implode(' | ', $links);
                ?>
            </div>
        </div>
        <div class="post-content">
            <?php echo format_post_body($post['body']); ?>
        </div>

        <div class="post-actions">
            <?php 
            $reactions = get_reactions_for_post($post['id']);
            // Emojis available for reacting
            $available_emojis = ['👍', '❤️', '😂', '😮', '😢', '😠', '🤔', '🔥', '🎉', '🚀'];
            ?>
            <div class="reactions-display">
                <?php if (!empty($reactions)):
                    foreach ($reactions as $emoji => $data):
                        // Each reaction bubble is a form to toggle it off
                ?>
                        <form action="react.php" method="post" class="reaction-bubble-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" name="emoji" value="<?php echo htmlspecialchars($emoji); ?>" class="reaction-bubble" title="<?php echo implode(', ', $data['users']); ?>">
                                <?php echo htmlspecialchars($emoji); ?> <?php echo $data['count']; ?>
                            </button>
                        </form>
                    <?php 
                    endforeach;
                endif; 
                ?>
            </div>

            <?php if (is_logged_in()): ?>
                <div class="react-form-container">
                    <?php
                    $is_picker_open = (isset($_GET['react_on']) && $_GET['react_on'] == $post['id']);
                    if ($is_picker_open) {
                        // Link to close the picker
                        $react_url = '?id=' . $topic_id . '#post-' . $post['id'];
                        $react_title = 'Close';
                    } else {
                        // Link to open the picker
                        $react_url = '?id=' . $topic_id . '&react_on=' . $post['id'] . '#post-' . $post['id'];
                        $react_title = 'React';
                    }
                    ?>
                    <a href="<?php echo $react_url; ?>" class="reaction-button" title="<?php echo $react_title; ?>"><i class="fa-solid fa-face-smile"></i></a>
                </div>
            <?php endif; ?>
        </div>

        <?php 
        // Show the emoji picker panel if this is the post we are reacting to
        if (isset($_GET['react_on']) && $_GET['react_on'] == $post['id'] && is_logged_in()): 
        ?>
        <div class="emoji-picker">
            <?php foreach ($available_emojis as $emoji): ?>
                <form action="react.php" method="post">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" name="emoji" value="<?php echo $emoji; ?>" class="reaction-button"><?php echo $emoji; ?></button>
                </form>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>


<h3 id="reply-form">Post Reply</h3>
<?php if (is_logged_in()): ?>
    <form action="view_topic.php?id=<?php echo $topic_id; ?>" method="post">
        <div>
            <label for="body">Your Message</label>
            <textarea name="body" id="body" rows="8" required><?php echo $quote_text; ?></textarea>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div>
            <button type="submit">Submit Reply</button>
        </div>
    </form>
<?php else: ?>
    <p>You must <a href="login.php">login</a> to post a reply.</p>
<?php endif; ?>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>
