<?php
// seed_advanced.php
// Populates the database with rich, informative sample data for the open-source version.

require_once __DIR__ . '/config.php';

echo "Starting advanced database seeding...\n";

try {
    $pdo = get_pdo();

    // --- Create Users ---
    $users = [
        ['username' => 'admin', 'password' => 'password123', 'role' => 'admin'],
        ['username' => 'moderator', 'password' => 'password123', 'role' => 'moderator'],
        ['username' => 'member', 'password' => 'password123', 'role' => 'member'],
    ];
    $user_ids = [];
    foreach ($users as $user) {
        create_user($user['username'], $user['password']);
        $db_user = get_user_by_username($user['username']);
        update_user_role($db_user['id'], $user['role']);
        $user_ids[$user['username']] = $db_user['id'];
        echo "Created user: {$user['username']}\n";
    }

    // --- Create Categories & Forums ---
    $structure = [
        'Welcome' => [
            'Announcements' => 'Official news and announcements from the staff.',
            'General Discussion' => 'A place to talk about anything and everything.',
        ],
        'Help & Support' => [
            'How-to & Guides' => 'Learn how to use the forum and its features.',
            'Feedback & Suggestions' => 'Got feedback or a suggestion? Post it here.',
        ],
        'Community' => [
            'Introductions' => 'New to the forum? Say hello!',
        ]
    ];

    $forum_ids = [];
    foreach ($structure as $category_title => $forums) {
        $pdo->prepare("INSERT INTO categories (title) VALUES (?)")->execute([$category_title]);
        $category_id = $pdo->lastInsertId();
        echo "Created category: $category_title\n";
        foreach ($forums as $forum_title => $forum_desc) {
            create_forum($forum_title, $forum_desc, $category_id);
            $forum_ids[$forum_title] = $pdo->lastInsertId();
            echo "  - Created forum: $forum_title\n";
        }
    }

    // --- Create Topics & Posts ---
    echo "Creating topics and posts...\n";

    // Topic 1
    $topic_id_1 = create_topic($forum_ids['Announcements'], $user_ids['admin'], 'Welcome to LiteForum!', 
        "Welcome to your new LiteForum installation!\n\nThis is a lightweight, dependency-free forum software built with performance and privacy in mind. It uses pure PHP and a simple SQLite database, making it easy to set up and run on almost any server.\n\nFeel free to look around and explore the features."
    );

    // Topic 2
    $topic_id_2 = create_topic($forum_ids['General Discussion'], $user_ids['member'], 'What is everyone working on?', 
        "Just a general thread to get the conversation started. What cool projects are you all working on right now?"
    );
    create_post($topic_id_2, $user_ids['moderator'], "I'm currently learning how to build applications with PHP. It's a lot of fun!");

    // Topic 3
    $topic_id_3 = create_topic($forum_ids['How-to & Guides'], $user_ids['admin'], 'How to Format Your Posts with BBCode', 
        "This forum supports BBCode to format your posts. Here are some examples:\n\n[b]This text will be bold.[/b]\n[i]This text will be italic.[/i]\n[u]This text will be underlined.[/u]\n\nYou can also create links:\n[url=https://github.com/google/gemini-cli]This is a link to the Gemini CLI repo![/url]\n\nAnd display images:\n[img]https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png[/img]"
    );

    // Topic 4
    $topic_id_4 = create_topic($forum_ids['How-to & Guides'], $user_ids['admin'], 'Understanding Notifications and Reactions', 
        "You will receive notifications for a couple of reasons:\n\n1.  **Quotes:** When another user quotes your post in their reply, you will get a notification.
2.  **Reactions:** When someone reacts to your post with an emoji, you will be notified.\n\nYou can view all your notifications by clicking the bell icon in the header."
    );

    // Topic 5
    $topic_id_5 = create_topic($forum_ids['Feedback & Suggestions'], $user_ids['member'], 'The search bar is a bit too wide on my monitor', 
        "Is it possible to make the search bar in the header a little smaller? On my 1440p screen it feels very wide."
    );
    create_post($topic_id_5, $user_ids['admin'], "[quote=member]Is it possible to make the search bar in the header a little smaller? On my 1440p screen it feels very wide.[/quote]\nThanks for the feedback! We can definitely look into adjusting the `max-width` property for the search bar on larger screens.");

    // Topic 6
    $topic_id_6 = create_topic($forum_ids['Feedback & Suggestions'], $user_ids['moderator'], 'Feature Request: User Signatures', 
        "It would be cool if we could add signatures to our posts that appear automatically."
    );

    // Topic 7
    $topic_id_7 = create_topic($forum_ids['Introductions'], $user_ids['member'], 'Hello everyone!', 
        "Hi all, I'm new here. Just wanted to say hello and check out the forum!"
    );

    echo "Seeding complete!\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage() . "\n");
}
