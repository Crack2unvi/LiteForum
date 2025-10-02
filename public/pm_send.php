<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$errors = [];
$recipient_name = '';
$title = '';
$body = '';

// Handle replying to a message or sending to a user from their profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['reply_to'])) {
        $source_pm = get_pm_by_id((int)$_GET['reply_to'], $_SESSION['user_id']);
        if ($source_pm) {
            $recipient_name = $source_pm['sender_username'];
            // Add Re: prefix if it's not already there
            if (strpos($source_pm['title'], 'Re: ') !== 0) {
                $title = 'Re: ' . $source_pm['title'];
            }
        }
    } elseif (isset($_GET['send_to'])) {
        $user_to = get_user_by_id((int)$_GET['send_to']);
        if ($user_to) {
            $recipient_name = $user_to['username'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token();
    $recipient_name = trim($_POST['recipient'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    $recipient = get_user_by_username($recipient_name);

    if (empty($recipient_name)) $errors[] = 'Recipient username is required.';
    if (!$recipient) $errors[] = 'Recipient not found.';
    if ($recipient && $recipient['id'] == $_SESSION['user_id']) $errors[] = 'You cannot send a message to yourself.';
    if (empty($title)) $errors[] = 'Subject is required.';
    if (empty($body)) $errors[] = 'Message body is required.';

    if (empty($errors)) {
        if (send_pm($_SESSION['user_id'], $recipient['id'], $title, $body)) {
            redirect('pm_sent.php?sent=true');
        } else {
            $errors[] = 'An error occurred while sending the message.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2>New Private Message</h2>

<div class="pm-menu">
    <a href="pm_inbox.php">Inbox</a>
    <a href="pm_sent.php">Sent Messages</a>
    <a href="pm_send.php" class="active">New Message</a>
</div>

<?php if (!empty($errors)):
    ?>
    <div class="errors" style="background-color: #FFD2D2; border: 1px solid #FF0000; color: #D8000C; padding: 10px; margin-bottom: 15px;">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="pm_send.php" method="post">
    <div>
        <label for="recipient">To (Username)</label>
        <input type="text" name="recipient" id="recipient" value="<?php echo htmlspecialchars($recipient_name); ?>" required>
    </div>
    <div>
        <label for="title">Subject</label>
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>
    </div>
    <div>
        <label for="body">Message</label>
        <textarea name="body" id="body" rows="10" required><?php echo htmlspecialchars($body); ?></textarea>
    </div>
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <div>
        <button type="submit">Send Message</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>