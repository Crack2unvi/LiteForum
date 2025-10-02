<?php
require_once __DIR__ . '/../includes/header.php';

$query = '';
$results = [];

if (isset($_GET['q'])) {
    $query = trim($_GET['q']);
    if (!empty($query)) {
        $results = search_posts($query);
    }
}

/**
 * Highlights a search term in a string of text.
 * @param string $text The text to search within.
 * @param string $term The term to highlight.
 * @return string The text with the term highlighted.
 */
function highlight_term($text, $term) {
    if (empty($term)) {
        return htmlspecialchars($text);
    }
    // Use a regex to find the term case-insensitively (i).
    // preg_quote is essential to escape any special regex characters in the user's search term.
    $pattern = '/(' . preg_quote($term, '/') . ')/i';
    $replacement = '<span class="highlight">$1</span>';
    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}

?>

<h2>Arama</h2>

<form action="search.php" method="get" class="search-form-page">
    <input type="text" name="q" placeholder="Forumda ara..." value="<?php echo htmlspecialchars($query); ?>" required>
    <button type="submit">Ara</button>
</form>

<?php if (!empty($query)): ?>
    <h3 style="margin-top: 20px;">"<?php echo htmlspecialchars($query); ?>" için arama sonuçları</h3>

    <div class="search-results">
        <?php if (empty($results)): ?>
            <p>Aramanızla eşleşen hiçbir sonuç bulunamadı.</p>
        <?php else: ?>
            <p><?php echo count($results); ?> sonuç bulundu.</p>
            <?php foreach ($results as $post): ?>
                <div class="result-item">
                    <h4><a href="view_topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['topic_title']); ?></a></h4>
                    <small>
                        <strong><a href="profile.php?id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['author_username']); ?></a></strong> tarafından
                        <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?> tarihinde gönderildi
                    </small>
                    <div class="snippet">
                        <?php
                        // Create a snippet and highlight the term
                        $body_text = $post['body'];
                        $snippet = (mb_strlen($body_text) > 250) ? mb_substr($body_text, 0, 250) . '...' : $body_text;
                        echo nl2br(highlight_term($snippet, $query));
                        ?>
                    </div>
                    <a class="view-post-link" href="view_topic.php?id=<?php echo $post['topic_id']; ?>#post-<?php echo $post['post_id']; ?>">Gönderiye Git &rarr;</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>