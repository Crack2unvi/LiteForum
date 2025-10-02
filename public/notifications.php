<?php
require_once __DIR__ . '/../config.php';

// Must be logged in to see notifications
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch notifications BEFORE marking them as read, so we can style the unread ones.
$notifications = get_notifications_by_user($user_id);

// Now, mark them all as read.
mark_notifications_as_read($user_id);

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Notifications</h2>

<div class="notification-list">
    <?php if (empty($notifications)): ?>
        <p>You have no notifications.</p>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <?php
            // Determine the link and text based on notification type
            $link = 'view_topic.php?id=' . $notification['topic_id'] . '#post-' . $notification['post_id'];
            $text = '';
            if ($notification['type'] === 'quote') {
                $text = '<strong>' . htmlspecialchars($notification['actor_username']) . '</strong> quoted you in the topic <strong>' . htmlspecialchars($notification['topic_title']) . '</strong>.';
            } elseif ($notification['type'] === 'reaction') {
                $text = '<strong>' . htmlspecialchars($notification['actor_username']) . '</strong> reacted to your post in the topic <strong>' . htmlspecialchars($notification['topic_title']) . '</strong>.';
            }

            // Add a class if the notification was unread when the page loaded
            $unread_class = $notification['is_read'] == 0 ? 'notification-unread' : '';
            ?>
            <div class="notification-item <?php echo $unread_class; ?>">
                <a href="<?php echo $link; ?>">
                    <p><?php echo $text; ?></p>
                    <small><?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>