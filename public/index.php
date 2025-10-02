<?php
require_once __DIR__ . '/../includes/header.php';

// Fetch all categories and forums
$categories = get_categories_and_forums();

// Fetch latest announcement
$announcements = get_all_announcements();
$latest_announcement = !empty($announcements) ? $announcements[0] : null;
?>

<?php if ($latest_announcement): ?>
<div class="announcement-box">
    <h3><i class="fa-solid fa-bullhorn"></i> Announcement: <?php echo htmlspecialchars($latest_announcement['title']); ?></h3>
    <p><?php echo nl2br(htmlspecialchars($latest_announcement['body'])); ?></p>
    <small>Published: <?php echo date('Y-m-d', strtotime($latest_announcement['created_at'])); ?></small>
</div>
<?php endif; ?>

<?php
$global_topics = get_global_topics();
if (!empty($global_topics)):
?>
    <h2 style="margin-top: 20px;">Global Topics</h2>
    <table class="forum-table">
        <thead>
            <tr>
                <th>Topic</th>
                <th>Author</th>
                <th>Replies</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($global_topics as $topic): ?>
                <tr>
                    <td data-label="Topic">
                        <strong><a href="view_topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></strong>
                        <br>
                        <small>Created: <?php echo date('Y-m-d', strtotime($topic['created_at'])); ?></small>
                    </td>
                    <td data-label="Author"><?php echo htmlspecialchars($topic['author_username']); ?></td>
                    <td data-label="Replies"><?php echo $topic['post_count'] - 1; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Forums</h2>

<table class="forum-table">
    <thead>
        <tr>
            <th>Forum</th>
            <th>Last Post</th>
            <th>Topics</th>
            <th>Posts</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($categories)):
            ?>
            <tr>
                <td colspan="4">No forums have been created yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <tr class="category-header">
                    <td colspan="4"><?php echo htmlspecialchars($category['title']); ?></td>
                </tr>
                <?php if (empty($category['forums'])):
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">-- No forums in this category --</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($category['forums'] as $forum): ?>
                        <tr>
                            <td data-label="Forum">
                                <strong><a href="view_forum.php?id=<?php echo $forum['id']; ?>"><?php echo htmlspecialchars($forum['title']); ?></a></strong>
                                <br>
                                <small><?php echo htmlspecialchars($forum['description']); ?></small>
                            </td>
                            <td data-label="Last Post">
                                <?php if ($forum['last_post_timestamp']): ?>
                                    <a href="view_topic.php?id=<?php echo $forum['last_post_topic_id']; ?>">Last Post</a>
                                    <br>
                                    by: <?php echo htmlspecialchars($forum['last_post_author']); ?>
                                    <br>
                                    <small><?php echo date('Y-m-d H:i', strtotime($forum['last_post_timestamp'])); ?></small>
                                <?php else: ?>
                                    No posts yet
                                <?php endif; ?>
                            </td>
                            <td data-label="Topics">
                                <?php echo $forum['topic_count']; ?>
                            </td>
                            <td data-label="Posts">
                                <?php echo $forum['post_count']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="forum-stats" style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border: 1px solid #ccc;">
    <h2>Forum Statistics</h2>
    <?php
    $total_users = get_total_user_count();
    $latest_user = get_latest_registered_user();
    $latest_topic = get_latest_topic();
    ?>
    <p>Total members: <strong><?php echo $total_users; ?></strong></p>
    <?php if ($latest_user): ?>
        <p>Our newest member: <strong><a href="profile.php?id=<?php echo $latest_user['id']; ?>"><?php echo htmlspecialchars($latest_user['username']); ?></a></strong></p>
    <?php endif; ?>
    <?php if ($latest_topic): ?>
        <p>Latest topic: <strong><a href="view_topic.php?id=<?php echo $latest_topic['id']; ?>"><?php echo htmlspecialchars($latest_topic['title']); ?></a></strong></p>
    <?php endif; ?>
</div>

<div class="online-users" style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border: 1px solid #ccc;">
    <h2>Who is Online</h2>
    <?php
    $active_users = get_active_users();
    $active_user_count = count($active_users);
    ?>
    <p>There are currently <strong><?php echo $active_user_count; ?></strong> active users. (Users active in the last 5 minutes)</p>
    <?php if ($active_user_count > 0): ?>
        <p>
            <?php
            $user_links = [];
            foreach ($active_users as $user) {
                $user_links[] = '<a href="profile.php?id=' . $user['id'] . '">' . htmlspecialchars($user['username']) . '</a>';
            }
            echo implode(', ', $user_links);
            ?>
        </p>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
