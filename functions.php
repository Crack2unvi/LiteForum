<?php
// functions.php

/**
 * Generates and stores a CSRF token in the session.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the submitted CSRF token.
 * Dies with a fatal error if validation fails.
 */
function validate_csrf_token() {
    // Check if tokens are even present before comparing them.
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        die('CSRF token validation failed: Token not found. Please try submitting the form again.');
    }

    // Now that we know they exist, check if they match.
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF token validation failed: Token mismatch. Please go back and try again.');
    }
    
    // Invalidate the token after use to prevent replay attacks.
    unset($_SESSION['csrf_token']);
}

/**
 * Checks if a user is currently logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirects the user to a specified URL and exits the script.
 * @param string $url The URL to redirect to.
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Fetches a user from the database by their username.
 * @param string $username
 * @return array|false The user array or false if not found.
 */
function get_user_by_username($username) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Creates a new user in the database.
 * @param string $username
 * @param string $password
 * @param string|null $display_name
 * @return bool True on success, false on failure.
 */
function create_user($username, $password, $display_name = null) {
    $pdo = get_pdo();
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // Set display name to username if it's empty
    if (empty($display_name)) {
        $display_name = $username;
    }
    
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, display_name) VALUES (?, ?, ?)'
        );
        return $stmt->execute([$username, $hashed_password, $display_name]);
    } catch (PDOException $e) {
        // Could be a unique constraint violation if username is taken
        return false;
    }
}

/**
 * Fetches all categories and their forums from the database, including statistics.
 * @return array An array of categories, each containing an array of forums with stats.
 */
function get_categories_and_forums() {
    $pdo = get_pdo();
    $sql = '
        SELECT
            c.id AS category_id,
            c.title AS category_title,
            f.id AS forum_id,
            f.title AS forum_title,
            f.description AS forum_description,
            (SELECT COUNT(t.id) FROM topics t WHERE t.forum_id = f.id) AS topic_count,
            (SELECT COUNT(p.id) FROM posts p JOIN topics t ON p.topic_id = t.id WHERE t.forum_id = f.id) AS post_count,
            (SELECT p.created_at FROM posts p JOIN topics t ON p.topic_id = t.id WHERE t.forum_id = f.id ORDER BY p.created_at DESC LIMIT 1) AS last_post_timestamp,
            (SELECT t.id FROM posts p JOIN topics t ON p.topic_id = t.id WHERE t.forum_id = f.id ORDER BY p.created_at DESC LIMIT 1) AS last_post_topic_id,
            (SELECT u.username FROM posts p JOIN users u ON p.user_id = u.id JOIN topics t ON p.topic_id = t.id WHERE t.forum_id = f.id ORDER BY p.created_at DESC LIMIT 1) AS last_post_author
        FROM
            categories c
        LEFT JOIN
            forums f ON c.id = f.category_id
        ORDER BY
            c.sort_order, c.title, f.sort_order, f.title
    ';
    $stmt = $pdo->query($sql);
    
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category_id = $row['category_id'];
        if (!isset($categories[$category_id])) {
            $categories[$category_id] = [
                'id' => $category_id,
                'title' => $row['category_title'],
                'forums' => []
            ];
        }
        
        if ($row['forum_id']) {
            $categories[$category_id]['forums'][] = [
                'id' => $row['forum_id'],
                'title' => $row['forum_title'],
                'description' => $row['forum_description'],
                'topic_count' => $row['topic_count'],
                'post_count' => $row['post_count'],
                'last_post_timestamp' => $row['last_post_timestamp'],
                'last_post_topic_id' => $row['last_post_topic_id'],
                'last_post_author' => $row['last_post_author']
            ];
        }
    }
    
    return $categories;
}

/**
 * Fetches a single category's details from the database.
 * @param int $category_id
 * @return array|false The category array or false if not found.
 */
function get_category_by_id($category_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches a single forum's details from the database.
 * @param int $forum_id
 * @return array|false The forum array or false if not found.
 */
function get_forum_by_id($forum_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM forums WHERE id = ?');
    $stmt->execute([$forum_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches all topics for a given forum.
 * Includes author's username and reply/post counts.
 * @param int $forum_id
 * @return array An array of topics.
 */
function get_topics_by_forum($forum_id) {
    $pdo = get_pdo();
    $sql = '
        SELECT
            t.id,
            t.title,
            t.created_at,
            u.username AS author_username,
            (SELECT COUNT(p.id) FROM posts p WHERE p.topic_id = t.id) AS post_count
        FROM
            topics t
        JOIN
            users u ON t.user_id = u.id
        WHERE
            t.forum_id = ?
        ORDER BY
            t.created_at DESC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$forum_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Creates a new topic and its initial post in a transaction.
 * @param int $forum_id
 * @param int $user_id
 * @param string $title
 * @param string $body
 * @return int|false The new topic ID on success, or false on failure.
 */
function create_topic($forum_id, $user_id, $title, $body, $is_global = 0) {
    $pdo = get_pdo();
    
    try {
        $pdo->beginTransaction();

        // If the topic is global, the forum_id should be NULL.
        $actual_forum_id = $is_global ? null : $forum_id;

        // Insert the topic
        $topic_sql = "INSERT INTO topics (forum_id, user_id, title, is_global) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($topic_sql);
        $stmt->execute([$actual_forum_id, $user_id, $title, $is_global]);
        $topic_id = $pdo->lastInsertId();

        // Insert the initial post
        $post_sql = "INSERT INTO posts (topic_id, user_id, body) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($post_sql);
        $stmt->execute([$topic_id, $user_id, $body]);

        $pdo->commit();
        
        return $topic_id;

    } catch (PDOException $e) {
        $pdo->rollBack();
        // In a real app, you'd log this error.
        return false;
    }
}

/**
 * Fetches a single topic's details from the database.
 * @param int $topic_id
 * @return array|false The topic array or false if not found.
 */
function get_topic_by_id($topic_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM topics WHERE id = ?');
    $stmt->execute([$topic_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches all posts for a given topic.
 * Includes author's details.
 * @param int $topic_id
 * @return array An array of posts.
 */
function get_posts_by_topic($topic_id) {
    $pdo = get_pdo();
    $sql = '
        SELECT
            p.id,
            p.user_id,
            p.body,
            p.created_at,
            u.username,
            u.role,
            u.created_at AS user_joined
        FROM
            posts p
        JOIN
            users u ON p.user_id = u.id
        WHERE
            p.topic_id = ?
        ORDER BY
            p.created_at ASC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$topic_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Creates a new post (a reply) in a topic.
 * @param int $topic_id
 * @param int $user_id
 * @param string $body
 * @return bool True on success, false on failure.
 */
function create_post($topic_id, $user_id, $body) {
    $pdo = get_pdo();
    
    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO posts (topic_id, user_id, body) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$topic_id, $user_id, $body]);
        $post_id = $pdo->lastInsertId();

        // --- Notification Logic ---
        preg_match_all('/\[quote=(.*?)\]/s', $body, $matches);
        if (!empty($matches[1])) {
            $quoted_usernames = array_unique($matches[1]);
            foreach ($quoted_usernames as $username) {
                // Trim the username to handle any accidental whitespace
                $quoted_user = get_user_by_username(trim($username));
                if ($quoted_user) {
                    create_notification($quoted_user['id'], $user_id, $topic_id, $post_id, 'quote');
                }
            }
        }
        // --- End Notification Logic ---

        $pdo->commit();
        return $post_id; // Return the new post ID

    } catch (PDOException $e) {
        $pdo->rollBack();
        // In a real app, you'd log this error.
        return false;
    }
}

/**
 * Fetches a user from the database by their ID.
 * @param int $user_id
 * @return array|false The user array or false if not found.
 */
function get_user_by_id($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, username, display_name, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches all posts for a given user.
 * Includes topic title for context.
 * @param int $user_id
 * @return array An array of posts.
 */
function get_posts_by_user($user_id) {
    $pdo = get_pdo();
    $sql = '
        SELECT
            p.id,
            p.body,
            p.created_at,
            t.id AS topic_id,
            t.title AS topic_title
        FROM
            posts p
        JOIN
            topics t ON p.topic_id = t.id
        WHERE
            p.user_id = ?
        ORDER BY
            p.created_at DESC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Checks if the current logged-in user is an admin.
 * @return bool
 */
function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

/**
 * Checks if the current logged-in user is a moderator or an admin.
 * @return bool
 */
function is_moderator() {
    if (!is_logged_in()) {
        return false;
    }
    return $_SESSION['role'] === 'moderator' || $_SESSION['role'] === 'admin';
}

/**
 * Checks if the current logged-in user is the owner of a given resource.
 * @param int $owner_user_id The user ID of the resource owner.
 * @return bool
 */
function is_owner($owner_user_id) {
    return is_logged_in() && $_SESSION['user_id'] == $owner_user_id;
}

/**
 * Creates a new category.
 * @param string $title The title of the category.
 * @return bool True on success, false on failure.
 */
function create_category($title) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (title) VALUES (?)");
        return $stmt->execute([$title]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Updates an existing category's title.
 * @param int $category_id The ID of the category to update.
 * @param string $title The new title.
 * @return bool True on success, false on failure.
 */
function update_category($category_id, $title) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("UPDATE categories SET title = ? WHERE id = ?");
        return $stmt->execute([$title, $category_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Deletes a category.
 * @param int $category_id The ID of the category to delete.
 * @return bool True on success, false on failure.
 */
function delete_category($category_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$category_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Creates a new forum.
 * @param string $title
 * @param string $description
 * @param int $category_id
 * @return bool
 */
function create_forum($title, $description, $category_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("INSERT INTO forums (title, description, category_id) VALUES (?, ?, ?)");
        return $stmt->execute([$title, $description, $category_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Updates an existing forum.
 * @param int $forum_id
 * @param string $title
 * @param string $description
 * @param int $category_id
 * @return bool
 */
function update_forum($forum_id, $title, $description, $category_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("UPDATE forums SET title = ?, description = ?, category_id = ? WHERE id = ?");
        return $stmt->execute([$title, $description, $category_id, $forum_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Deletes a forum.
 * @param int $forum_id
 * @return bool
 */
function delete_forum($forum_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("DELETE FROM forums WHERE id = ?");
        return $stmt->execute([$forum_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Fetches a single post's details from the database.
 * @param int $post_id
 * @return array|false The post array or false if not found.
 */
function get_post_by_id($post_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$post_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Updates an existing post's body.
 * @param int $post_id
 * @param string $body
 * @return bool
 */
function update_post($post_id, $body) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("UPDATE posts SET body = ? WHERE id = ?");
        return $stmt->execute([$body, $post_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Deletes a post.
 * @param int $post_id
 * @return bool
 */
function delete_post($post_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        return $stmt->execute([$post_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Gets the total number of registered users.
 * @return int
 */
function get_total_user_count() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT COUNT(id) FROM users');
    return (int)$stmt->fetchColumn();
}

/**
 * Fetches the latest registered user.
 * @return array|false
 */
function get_latest_registered_user() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, username FROM users ORDER BY created_at DESC LIMIT 1');
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches the latest topic.
 * @return array|false
 */
function get_latest_topic() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, title FROM topics ORDER BY created_at DESC LIMIT 1');
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetches all users from the database.
 * @return array
 */
function get_all_users() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, username, display_name, role, created_at FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Updates a user's role.
 * @param int $user_id
 * @param string $role
 * @return bool
 */
function update_user_role($user_id, $role) {
    $pdo = get_pdo();
    // Basic validation for role
    if (!in_array($role, ['member', 'moderator', 'admin'])) {
        return false;
    }
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    return $stmt->execute([$role, $user_id]);
}

/**
 * Deletes a user from the database.
 * Their posts and topics will be orphaned (user_id set to NULL).
 * @param int $user_id
 * @return bool
 */
function delete_user($user_id) {
    $pdo = get_pdo();
    // To prevent an admin from deleting themselves
    if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
        return false;
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Deletes a topic and all its posts (via CASCADE).
 * @param int $topic_id
 * @return bool
 */
function delete_topic($topic_id) {
    $pdo = get_pdo();
    try {
        $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
        return $stmt->execute([$topic_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Formats post body content for display.
 * Converts BBCode quotes to HTML and applies nl2br.
 * @param string $body
 * @return string
 */
function format_post_body($body) {
    // 1. Escape HTML entities to prevent XSS. This is the raw, safe text.
    $safe_body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

    // --- BBCode Processing ---
    $bbcode_body = $safe_body;

    // 2. Simple, non-attribute BBCodes: [b], [i], [u]
    $simple_bbcode = [
        '/\[b\](.*?)\[\/b\]/is',
        '/ \[i\](.*?)\[\/i\]/is',
        '/ \[u\](.*?)\[\/u\]/is',
    ];
    $simple_html = [
        '<strong>$1</strong>',
        '<em>$1</em>',
        '<span style="text-decoration: underline;">$1</span>',
    ];
    $bbcode_body = preg_replace($simple_bbcode, $simple_html, $bbcode_body);

    // 3. Image tag: [img]https://...[/img]
    $img_pattern = '/\[img\](https?:[^\s<]+\.(?:jpg|jpeg|png|gif))\[\/img\]/i';
    $bbcode_body = preg_replace_callback($img_pattern, function($matches) {
        $url = html_entity_decode($matches[1]);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<img src="' . $url . '" alt="Kullanıcı resmi" style="max-width: 100%; height: auto; border-radius: 5px;">';
        }
        return $matches[0]; // Return original tag if URL is invalid
    }, $bbcode_body);

    // 4. URL with attribute: [url=https://...]Text[/url]
    $url_attr_pattern = '/\[url=(https?:[^\s<]+)\](.*?)\[\/url\]/i';
    $bbcode_body = preg_replace_callback($url_attr_pattern, function($matches) {
        $url = html_entity_decode($matches[1]);
        $text = $matches[2];
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<a href="' . $url . '" target="_blank" rel="nofollow">' . $text . '</a>';
        }
        return $matches[0];
    }, $bbcode_body);

    // 5. URL without attribute: [url]https://...[/url]
    $url_no_attr_pattern = '/\[url\](https?:[^\s<]+)\[\/url\]/i';
    $bbcode_body = preg_replace_callback($url_no_attr_pattern, function($matches) {
        $url = html_entity_decode($matches[1]);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<a href="' . $url . '" target="_blank" rel="nofollow">' . $url . '</a>';
        }
        return $matches[0];
    }, $bbcode_body);

    // 6. Quote tag: [quote=username]...[/quote]
    $quote_pattern = '/\[quote=(.*?)\](.*?)\[\/quote\]/s';
    $quote_replacement = '<blockquote class="quote"><cite><strong>$1</strong> said:</cite><p>$2</p></blockquote>';
    $formatted_body = preg_replace($quote_pattern, $quote_replacement, $bbcode_body);

    // 7. Convert newlines to <br> tags. This should be last.
    return nl2br($formatted_body, false);
}

/**
 * Creates a new notification in the database.
 * @param int $user_id The ID of the user to notify.
 * @param int $actor_id The ID of the user who triggered the notification.
 * @param int $topic_id The ID of the topic where the event occurred.
 * @param int $post_id The ID of the post that triggered the event.
 * @param string $type The type of notification (e.g., 'quote').
 * @return bool True on success, false on failure.
 */
function create_notification($user_id, $actor_id, $topic_id, $post_id, $type) {
    // Don't create a notification if a user is quoting themselves.
    if ($user_id == $actor_id) {
        return false;
    }

    $pdo = get_pdo();
    $sql = "INSERT INTO notifications (user_id, actor_id, topic_id, post_id, type) VALUES (?, ?, ?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $actor_id, $topic_id, $post_id, $type]);
    } catch (PDOException $e) {
        // In a real app, you would log this error.
        return false;
    }
}

/**
 * Gets the count of unread notifications for a user.
 * @param int $user_id
 * @return int
 */
function get_unread_notification_count($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Fetches all notifications for a user, joining with relevant tables.
 * @param int $user_id
 * @return array
 */
function get_notifications_by_user($user_id) {
    $pdo = get_pdo();
    $sql = "
        SELECT
            n.id,
            n.actor_id,
            n.topic_id,
            n.post_id,
            n.type,
            n.is_read,
            n.created_at,
            a.username AS actor_username,
            t.title AS topic_title
        FROM
            notifications n
        JOIN
            users a ON n.actor_id = a.id
        JOIN
            topics t ON n.topic_id = t.id
        WHERE
            n.user_id = ?
        ORDER BY
            n.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marks all unread notifications for a user as read.
 * @param int $user_id
 * @return bool
 */
function mark_notifications_as_read($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$user_id]);
}

/**
 * Adds, updates, or removes a reaction for a post by a user.
 * - If the user reacts with the same emoji, the reaction is removed (toggled off).
 * - If the user reacts with a different emoji, the reaction is updated.
 * - If the user has not reacted before, the reaction is added.
 * @param int $post_id
 * @param int $user_id
 * @param string $emoji
 * @return bool
 */
function toggle_reaction($post_id, $user_id, $emoji, $topic_id) {
    $pdo = get_pdo();

    // Check for an existing reaction from this user on this post
    $stmt = $pdo->prepare("SELECT * FROM post_reactions WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $existing_reaction = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        if ($existing_reaction) {
            // Reaction exists
            if ($existing_reaction['emoji'] === $emoji) {
                // Same emoji, so delete the reaction (toggle off)
                $stmt = $pdo->prepare("DELETE FROM post_reactions WHERE id = ?");
                return $stmt->execute([$existing_reaction['id']]);
            } else {
                // Different emoji, so update the reaction
                $stmt = $pdo->prepare("UPDATE post_reactions SET emoji = ? WHERE id = ?");
                return $stmt->execute([$emoji, $existing_reaction['id']]);
            }
        } else {
            // No reaction exists, so insert a new one
            $stmt = $pdo->prepare("INSERT INTO post_reactions (post_id, user_id, emoji) VALUES (?, ?, ?)");
            $success = $stmt->execute([$post_id, $user_id, $emoji]);

            if ($success) {
                // Create a notification for the post author
                $post = get_post_by_id($post_id);
                if ($post) {
                    $post_author_id = $post['user_id'];
                    create_notification($post_author_id, $user_id, $topic_id, $post_id, 'reaction');
                }
            }
            return $success;
        }
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Gets all reactions for a post, grouped by emoji.
 * @param int $post_id
 * @return array An array where keys are emojis and values are arrays of user info.
 */
function get_reactions_for_post($post_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("
        SELECT emoji, GROUP_CONCAT(u.username) as usernames
        FROM post_reactions pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.post_id = ?
        GROUP BY emoji
        ORDER BY COUNT(pr.id) DESC
    ");
    $stmt->execute([$post_id]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reactions = [];
    foreach ($results as $row) {
        $reactions[$row['emoji']] = [
            'count' => count(explode(',', $row['usernames'])),
            'users' => explode(',', $row['usernames'])
        ];
    }
    return $reactions;
}

/**
 * Updates the last_seen timestamp for a user.
 * @param int $user_id
 * @return bool
 */
function update_user_last_seen($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?");
    return $stmt->execute([$user_id]);
}

function get_active_users() {
    $pdo = get_pdo();
    // SQLite compatible query to get users active in the last 5 minutes (300 seconds)
    $stmt = $pdo->prepare("\n        SELECT id, username\n        FROM users\n        WHERE last_seen IS NOT NULL AND (strftime('%s', 'now') - strftime('%s', last_seen)) < 300\n        ORDER BY username ASC\n    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches all announcements, newest first.
 * @return array
 */
function get_all_announcements() {
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches a single announcement by its ID.
 * @param int $id
 * @return array|false
 */
function get_announcement_by_id($id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Creates a new announcement.
 * @param int $user_id
 * @param string $title
 * @param string $body
 * @return bool
 */
function create_announcement($user_id, $title, $body) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO announcements (user_id, title, body) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $title, $body]);
}

/**
 * Updates an existing announcement.
 * @param int $id
 * @param string $title
 * @param string $body
 * @return bool
 */
function update_announcement($id, $title, $body) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE announcements SET title = ?, body = ? WHERE id = ?");
    return $stmt->execute([$title, $body, $id]);
}

/**
 * Deletes an announcement.
 * @param int $id
 * @return bool
 */
function delete_announcement($id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Fetches all global topics.
 * Includes author's username and reply/post counts.
 * @return array An array of topics.
 */
function get_global_topics() {
    $pdo = get_pdo();
    $sql = ' 
        SELECT
            t.id,
            t.title,
            t.created_at,
            u.username AS author_username,
            (SELECT COUNT(p.id) FROM posts p WHERE p.topic_id = t.id) AS post_count
        FROM
            topics t
        JOIN
            users u ON t.user_id = u.id
        WHERE
            t.is_global = 1
        ORDER BY
            t.created_at DESC
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Private Messaging Functions ---

/**
 * Sends a private message.
 * @param int $sender_id
 * @param int $recipient_id
 * @param string $title
 * @param string $body
 * @return bool
 */
function send_pm($sender_id, $recipient_id, $title, $body) {
    $pdo = get_pdo();
    $sql = "INSERT INTO private_messages (sender_id, recipient_id, title, body) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$sender_id, $recipient_id, $title, $body]);
}

/**
 * Gets the count of unread private messages for a user.
 * @param int $user_id
 * @return int
 */
function get_unread_pm_count($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM private_messages WHERE recipient_id = ? AND is_read = 0 AND recipient_deleted = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Fetches PMs for a user's inbox or sentbox.
 * @param int $user_id
 * @param string $mailbox 'inbox' or 'sentbox'
 * @return array
 */
function get_pms_for_user($user_id, $mailbox = 'inbox') {
    $pdo = get_pdo();
    if ($mailbox === 'inbox') {
        $sql = "SELECT pm.*, u.username AS sender_username FROM private_messages pm JOIN users u ON pm.sender_id = u.id WHERE pm.recipient_id = ? AND pm.recipient_deleted = 0 ORDER BY pm.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else { // sentbox
        $sql = "SELECT pm.*, u.username AS recipient_username FROM private_messages pm JOIN users u ON pm.recipient_id = u.id WHERE pm.sender_id = ? AND pm.sender_deleted = 0 ORDER BY pm.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches a single PM and ensures the user is authorized to read it.
 * @param int $pm_id
 * @param int $user_id
 * @return array|false
 */
function get_pm_by_id($pm_id, $user_id) {
    $pdo = get_pdo();
    $sql = "SELECT pm.*, s.username AS sender_username, r.username AS recipient_username FROM private_messages pm JOIN users s ON pm.sender_id = s.id JOIN users r ON pm.recipient_id = r.id WHERE pm.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pm_id]);
    $pm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pm && ($pm['sender_id'] == $user_id || $pm['recipient_id'] == $user_id)) {
        return $pm;
    }
    return false;
}

/**
 * Marks a private message as read.
 * @param int $pm_id
 * @return bool
 */
function mark_pm_as_read($pm_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$pm_id]);
}

/**
 * Deletes a PM for a user (soft delete).
 * @param int $pm_id
 * @param int $user_id
 * @return bool
 */
function delete_pm($pm_id, $user_id) {
    $pdo = get_pdo();
    $pm = get_pm_by_id($pm_id, $user_id);
    if (!$pm) return false; // User not authorized or PM doesn't exist

    if ($pm['sender_id'] == $user_id) {
        $sql = "UPDATE private_messages SET sender_deleted = 1 WHERE id = ?";
    } elseif ($pm['recipient_id'] == $user_id) {
        $sql = "UPDATE private_messages SET recipient_deleted = 1 WHERE id = ?";
    } else {
        return false; // Should not happen due to check above
    }
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$pm_id]);
}

// --- Profile Page Functions ---

/**
 * Gets the total post count for a user.
 * @param int $user_id
 * @return int
 */
function get_post_count_for_user($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM posts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Gets the total topic count for a user.
 * @param int $user_id
 * @return int
 */
function get_topic_count_for_user($user_id) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM topics WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Gets all reactions given by a user.
 * @param int $user_id
 * @return array
 */
function get_reactions_given_by_user($user_id) {
    $pdo = get_pdo();
    $sql = "
        SELECT pr.emoji, pr.post_id, p.body, t.id as topic_id, t.title as topic_title
        FROM post_reactions pr
        JOIN posts p ON pr.post_id = p.id
        JOIN topics t ON p.topic_id = t.id
        WHERE pr.user_id = ?
        ORDER BY pr.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Searches posts for a given query.
 * @param string $query The search term.
 * @return array An array of matching posts.
 */
function search_posts($query) {
    $pdo = get_pdo();
    $sql = "
        SELECT
            p.id AS post_id,
            p.body,
            p.created_at,
            t.id AS topic_id,
            t.title AS topic_title,
            u.id AS user_id,
            u.username AS author_username
        FROM
            posts p
        JOIN
            topics t ON p.topic_id = t.id
        JOIN
            users u ON p.user_id = u.id
        WHERE
            p.body LIKE ?
        ORDER BY
            p.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $query . '%']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}