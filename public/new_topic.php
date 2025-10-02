<?php
// The config file contains all our functions and session start, but does not output HTML.
// We need it first to access our helper functions.
require_once __DIR__ . '/../config.php';

// 1. Check if user is logged in. This check must happen early.
if (!is_logged_in()) {
    redirect('login.php');
}

// 2. Get and validate forum ID from the URL.
$forum_id = (int)($_GET['forum_id'] ?? 0);
$forum = get_forum_by_id($forum_id);

if (!$forum) {
    // We can't include header.php here yet, so we just die.
    die('Forum not found.');
}

$errors = [];
$title = '';
$body = '';

// 3. Handle the POST request BEFORE any HTML is sent.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();

    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if (empty($title)) {
        $errors[] = 'Topic title is required.';
    }
    if (empty($body)) {
        $errors[] = 'Message body is required.';
    }

    if (empty($errors)) {
        $is_global = (is_admin() && isset($_POST['is_global'])) ? 1 : 0;
        $topic_id = create_topic($forum_id, $_SESSION['user_id'], $title, $body, $is_global);

        if ($topic_id) {
            // This redirect will now work because no HTML has been sent.
            redirect('view_topic.php?id=' . $topic_id);
        } else {
            $errors[] = 'An error occurred while creating the topic.';
        }
    }
}

// 4. If we are still here, it means it's a GET request or there were errors.
// Now we can safely include the header and display the page.
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Create New Topic in <?php echo htmlspecialchars($forum['title']); ?></h2>

<?php if (!empty($errors)):
    ?>
    <div class="errors" style="background-color: #FFD2D2; border: 1px solid #FF0000; color: #D8000C; padding: 10px; margin-bottom: 15px;">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error):
                ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="new_topic.php?forum_id=<?php echo $forum_id; ?>" method="post">
    <div>
        <label for="title">Topic Title</label>
        <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars($title); ?>">
    </div>
    <div>
        <label for="body">Message</label>
        <textarea name="body" id="body" rows="10" required><?php echo htmlspecialchars($body); ?></textarea>
    </div>

    <?php if (is_admin()): ?>
    <div>
        <label for="is_global">
            <input type="checkbox" name="is_global" id="is_global" value="1">
            Make this a Global Topic (appears above all forums)
        </label>
    </div>
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <div>
        <button type="submit">Create Topic</button>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
