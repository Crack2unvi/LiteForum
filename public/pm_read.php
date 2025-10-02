<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$pm_id = (int)($_GET['id'] ?? 0);

$pm = get_pm_by_id($pm_id, $user_id);

if (!$pm) {
    die('Message not found or you do not have permission to view it.');
}

// Mark the message as read if the current user is the recipient
if ($pm['recipient_id'] == $user_id) {
    mark_pm_as_read($pm_id);
}

// Handle message deletion
if (isset($_GET['delete']) && $_GET['delete'] == $pm_id) {
    if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Invalid CSRF token.');
    }
    unset($_SESSION['csrf_token']);
    if (delete_pm($pm_id, $user_id)) {
        redirect('pm_inbox.php?deleted=true');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Read Private Message</h2>

<div class="pm-menu">
    <a href="pm_inbox.php">Inbox</a>
    <a href="pm_sent.php">Sent Messages</a>
    <a href="pm_send.php">New Message</a>
</div>

<div class="pm-view">
    <h3><?php echo htmlspecialchars($pm['title']); ?></h3>
    <div class="pm-meta">
        <strong>From:</strong> <a href="profile.php?id=<?php echo $pm['sender_id']; ?>"><?php echo htmlspecialchars($pm['sender_username']); ?></a><br>
        <strong>To:</strong> <a href="profile.php?id=<?php echo $pm['recipient_id']; ?>"><?php echo htmlspecialchars($pm['recipient_username']); ?></a><br>
        <strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($pm['created_at'])); ?>
    </div>
    <div class="pm-body">
        <?php echo nl2br(htmlspecialchars($pm['body'])); ?>
    </div>
    <div class="pm-actions">
        <a href="pm_send.php?reply_to=<?php echo $pm['id']; ?>">Reply</a>
        <a href="pm_read.php?id=<?php echo $pm['id']; ?>&delete=<?php echo $pm['id']; ?>&token=<?php echo generate_csrf_token(); ?>" onclick="return confirm('Are you sure you want to delete this message?');">Delete</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>