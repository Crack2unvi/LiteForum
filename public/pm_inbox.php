<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$messages = get_pms_for_user($user_id, 'inbox');

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Inbox</h2>

<div class="pm-menu">
    <a href="pm_inbox.php" class="active">Inbox</a>
    <a href="pm_sent.php">Sent Messages</a>
    <a href="pm_send.php">New Message</a>
</div>

<table class="forum-table pm-table">
    <thead>
        <tr>
            <th>Subject</th>
            <th>From</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($messages)):
            ?>
            <tr><td colspan="3">You have no messages in your inbox.</td></tr>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <tr class="<?php echo $msg['is_read'] ? 'pm-read' : 'pm-unread'; ?>">
                    <td data-label="Subject"><a href="pm_read.php?id=<?php echo $msg['id']; ?>"><?php echo htmlspecialchars($msg['title']); ?></a></td>
                    <td data-label="From"><a href="profile.php?id=<?php echo $msg['sender_id']; ?>"><?php echo htmlspecialchars($msg['sender_username']); ?></a></td>
                    <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>