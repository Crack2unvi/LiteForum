<?php
require_once __DIR__ . '/../includes/header.php';

// 1. Get forum ID from URL
$forum_id = (int)($_GET['id'] ?? 0);

if ($forum_id <= 0) {
    // Or redirect to a 404 page
    die('Invalid forum ID.');
}

// 2. Fetch forum and topic data
$forum = get_forum_by_id($forum_id);
$topics = get_topics_by_forum($forum_id);

// 3. Handle forum not found
if (!$forum) {
    die('Forum not found.');
}
?>

<h2><?php echo htmlspecialchars($forum['title']); ?></h2>
<p><?php echo htmlspecialchars($forum['description']); ?></p>

<div>
    <a href="new_topic.php?forum_id=<?php echo $forum_id; ?>" class="button" style="display: inline-block; padding: 10px 15px; background-color: #003366; color: #FFFFFF; text-decoration: none; font-weight: bold; margin-bottom: 15px;">New Topic</a>
</div>

<table class="forum-table topic-list-table">
    <thead>
        <tr>
            <th>Topic</th>
            <th>Author</th>
            <th>Replies</th>
            <th>Last Post</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($topics)):
            ?>
            <tr>
                <td colspan="4">There are no topics in this forum yet.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($topics as $topic):
                // The number of replies is the total number of posts minus the original post
                $replies = $topic['post_count'] > 0 ? $topic['post_count'] - 1 : 0;
                ?>
                <tr>
                    <td data-label="Topic">
                        <strong><a href="view_topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></strong>
                    </td>
                    <td data-label="Author"><?php echo htmlspecialchars($topic['author_username']); ?></td>
                    <td data-label="Replies"><?php echo $replies; ?></td>
                    <td data-label="Last Post">
                        <?php // Last post info will go here ?>
                        Not Implemented Yet
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>