# LiteForum

![PHP Version](https://img.shields.io/badge/php-8.0%2B-blue.svg)

A lightweight, dependency-free, and security-focused forum software written in pure PHP and SQLite.

---

LiteForum is designed for simplicity, performance, and privacy. It harks back to the early days of the internet, providing a fast, functional, and easy-to-use platform for online communities without the bloat of modern web frameworks. It uses a simple file-based SQLite database and requires no dependencies outside of standard PHP extensions, making it incredibly easy to set up and maintain.

<!-- Add a screenshot of the forum index here -->

## Features

LiteForum comes packed with a surprising number of features for its lightweight nature:

- **Core Forum Functionality:** Full support for Categories, Forums, Topics, and Posts.
- **User Management:** User registration and login system with secure password hashing (Bcrypt).
- **User Roles:** Member, Moderator, and Admin roles with a permissions system.
- **Admin Panel:** A full-featured dashboard for administrators to manage categories, forums, users, and site-wide announcements.
- **Private Messaging:** Users can send and receive private messages.
- **Post Reactions:** Users can react to posts with a selection of emojis.
- **Notifications:** Users are notified when someone quotes them or reacts to their post.
- **BBCode Support:** Format posts using `[b]`, `[i]`, `[u]`, `[url]`, and `[img]` tags.
- **Post Quoting:** Easily quote other users in your replies.
- **Maintenance Mode:** Admins can put the site into maintenance mode, accessible only by administrators.
- **And more:** Global topics, user profiles, active user tracking, forum statistics, and a search function.

## Technical Stack

- **Backend:** PHP 8.0+ (procedural, no frameworks)
- **Database:** SQLite 3
- **Frontend:** HTML5, CSS3 (no JS frameworks, no CSS frameworks)

## Installation

Setting up LiteForum is designed to be as simple as possible.

#### Requirements
- A web server (Apache, Nginx, Caddy, etc.)
- PHP 8.0 or higher
- The `php-sqlite3` PHP extension must be enabled.

#### Steps

1.  **Download:** Download the latest release or clone this repository.
2.  **Upload:** Upload the project files to your web server's public directory (e.g., `public_html`, `www`, `/var/www/html`).
3.  **Permissions:** Ensure your web server has **write permissions** for the project's root directory. This is required for PHP to create and write to the `forum.sqlite` database file.
4.  **Run the Installer:** Navigate to `http://your-domain.com/database.php` in your web browser. This will create the `forum.sqlite` file and set up all the necessary tables.
5.  **Seed the Database (Optional):** To create a default `admin` user and some sample content, navigate to `http://your-domain.com/seed.php`. 
    - Default credentials: **Username:** `admin`, **Password:** `password123`
6.  **Cleanup (Important Security Step):** After setup is complete, you **must** delete `database.php` and `seed.php` from your server to prevent them from being run again.

Your forum is now ready to use!

## Roadmap & Contributing (Author's Suggestions)

This project was built with a specific philosophy, but there are many ways it could be extended. If you wish to contribute, here are some ideas for future features:

- **User Banning:** A system for administrators to temporarily or permanently ban users.
- **AJAX Integration:** Now that the project is open-sourced, integrating JavaScript for a more dynamic experience is a great next step. This could include:
    - Submitting replies without a full page reload.
    - Live notifications.
    - A more interactive BBCode editor.
- **Theme Engine:** A simple system to allow admins to change the site's color scheme or styles without editing CSS files directly.
- **Full-Text Search (FTS5):** Upgrading the search functionality to use SQLite's built-in FTS5 extension for faster and more powerful search capabilities.
- **Improved "Last Post" Column:** The "Last Post" column in the topic list (`view_forum.php`) is currently not implemented. This should be added to show the last post's author and time.

## License

This project is open source and licensed under the [MIT License](LICENSE).