<?php
require_once __DIR__ . '/../includes/header.php';

// 1. Get user ID and active tab from URL
$user_id = (int)($_GET['id'] ?? 0);
$active_tab = $_GET['view'] ?? 'summary'; // Default to summary tab

if ($user_id <= 0) {
    die('Invalid user ID.');
}

// 2. Fetch all necessary data
$user = get_user_by_id($user_id);
if (!$user) {
    die('User not found.');
}

$post_count = get_post_count_for_user($user_id);
$topic_count = get_topic_count_for_user($user_id);
$posts = ($active_tab === 'posts') ? get_posts_by_user($user_id) : [];
$reactions = ($active_tab === 'reactions') ? get_reactions_given_by_user($user_id) : [];

?>
<style>
    .profile-tabs {
        display: flex;
        border-bottom: 1px solid #ccc;
        margin-bottom: 20px;
    }
    .profile-tabs a {
        padding: 10px 15px;
        text-decoration: none;
        color: #666;
        font-weight: bold;
    }
    .profile-tabs a.active {
        border-bottom: 3px solid #003366;
        color: #003366;
    }
</style>

<h2><?php echo htmlspecialchars($user['username']); ?>'s Profile</h2>

<?php if (is_logged_in() && $_SESSION['user_id'] != $user['id']): ?>
<p><a href="pm_send.php?send_to=<?php echo $user['id']; ?>"><i class="fa-solid fa-envelope"></i> Send a private message to this user</a></p>
<?php endif; ?>

<div class="profile-tabs">
    <a href="?id=<?php echo $user_id; ?>&view=summary" class="<?php if ($active_tab === 'summary') echo 'active'; ?>">Summary</a>
    <a href="?id=<?php echo $user_id; ?>&view=posts" class="<?php if ($active_tab === 'posts') echo 'active'; ?>">Posts (<?php echo $post_count; ?>)</a>
    <a href="?id=<?php echo $user_id; ?>&view=reactions" class="<?php if ($active_tab === 'reactions') echo 'active'; ?>">Reactions</a>
</div>

<div class="profile-content">
    <?php if ($active_tab === 'summary'): ?>
        <h3>Statistics</h3>
        <div class="profile-info" style="background-color: #f5f5f5; padding: 15px; border: 1px solid #ccc;">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
            <p><strong>Join Date:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            <p><strong>Total Topics:</strong> <?php echo $topic_count; ?></p>
            <p><strong>Total Posts:</strong> <?php echo $post_count; ?></p>
        </div>

    <?php elseif ($active_tab === 'posts'): ?>
        <h3>User's Posts</h3>
        <?php if (empty($posts)): ?>
            <p><?php echo htmlspecialchars($user['username']); ?> has not made any posts yet.</p>
        <?php else: ?>
            <table class="forum-table">
                <thead><tr><th>Post Preview</th><th>Topic</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td data-label="Post Preview"><?php echo htmlspecialchars(substr($post['body'], 0, 100)) . (strlen($post['body']) > 100 ? '...' : ''); ?></td>
                            <td data-label="Topic"><a href="view_topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['topic_title']); ?></a></td>
                            <td data-label="Date"><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($active_tab === 'reactions'): ?>
        <h3>Reactions Given by User</h3>
        <?php if (empty($reactions)): ?>
            <p><?php echo htmlspecialchars($user['username']); ?> has not given any reactions yet.</p>
        <?php else: ?>
            <table class="forum-table">
                <thead><tr><th>Reaction</th><th>Message Preview</th><th>Topic</th></tr></thead>
                <tbody>
                    <?php foreach ($reactions as $reaction): ?>
                        <tr>
                            <td data-label="Reaction" style="font-size: 20px;"><?php echo htmlspecialchars($reaction['emoji']); ?></td>
                            <td data-label="Message Preview"><?php echo htmlspecialchars(substr($reaction['body'], 0, 100)) . (strlen($reaction['body']) > 100 ? '...' : ''); ?></td>
                            <td data-label="Topic"><a href="view_topic.php?id=<?php echo $reaction['topic_id']; ?>#post-<?php echo $reaction['post_id']; ?>"><?php echo htmlspecialchars($reaction['topic_title']); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
