<?php
// seed.php
// A simple script to populate the database with initial data.

require_once __DIR__ . '/config.php';

try {
    $pdo = get_pdo();

    // --- Create a sample category ---
    $category_sql = "INSERT INTO categories (title) VALUES ('General')";
    $pdo->exec($category_sql);
    $category_id = $pdo->lastInsertId();
    echo "Created category 'General'.\n";

    // --- Create a sample forum ---
    $forum_sql = "INSERT INTO forums (category_id, title, description) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($forum_sql);
    $stmt->execute([
        $category_id,
        'General Discussion',
        'A place to talk about anything and everything.'
    ]);
    echo "Created forum 'General Discussion'.\n";

    // --- Create a sample user ---
    $username = 'admin';
    $password = 'password123'; // In a real app, use a stronger password
    $display_name = 'Admin';
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $user_sql = "INSERT INTO users (username, password, display_name, role) VALUES (?, ?, ?, 'admin')";
    $stmt = $pdo->prepare($user_sql);
    $stmt->execute([$username, $hashed_password, $display_name]);
    echo "Created user 'admin' with password 'password123'.\n";


    // --- Create a sample topic and post ---
    $admin_user = get_user_by_username('admin');
    
    // We need the forum ID. Let's fetch it.
    $stmt = $pdo->query("SELECT id FROM forums WHERE title = 'General Discussion'");
    $general_forum = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin_user && $general_forum) {
        $topic_title = 'Welcome to the forum!';
        $topic_body = 'This is the first topic and post in the General Discussion forum. Feel free to look around!';

        // Insert topic
        $topic_sql = "INSERT INTO topics (forum_id, user_id, title) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($topic_sql);
        $stmt->execute([$general_forum['id'], $admin_user['id'], $topic_title]);
        $topic_id = $pdo->lastInsertId();
        echo "Created topic '$topic_title'.\n";

        // Insert post
        $post_sql = "INSERT INTO posts (topic_id, user_id, body) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($post_sql);
        $stmt->execute([$topic_id, $admin_user['id'], $topic_body]);
        echo "Created initial post for the welcome topic.\n";
    }

    echo "Database seeding complete.\n";

} catch (PDOException $e) {
    die("Seeding failed: " . $e->getMessage() . "\n");
}

