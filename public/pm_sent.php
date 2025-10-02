<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$messages = get_pms_for_user($user_id, 'sentbox');

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Sent Messages</h2>

<div class="pm-menu">
    <a href="pm_inbox.php">Inbox</a>
    <a href="pm_sent.php" class="active">Sent Messages</a>
    <a href="pm_send.php">New Message</a>
</div>

<table class="forum-table pm-table">
    <thead>
        <tr>
            <th>Subject</th>
            <th>To</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($messages)):
            ?>
            <tr><td colspan="3">You have no sent messages.</td></tr>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <tr>
                    <td data-label="Subject"><a href="pm_read.php?id=<?php echo $msg['id']; ?>"><?php echo htmlspecialchars($msg['title']); ?></a></td>
                    <td data-label="To"><a href="profile.php?id=<?php echo $msg['recipient_id']; ?>"><?php echo htmlspecialchars($msg['recipient_username']); ?></a></td>
                    <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>