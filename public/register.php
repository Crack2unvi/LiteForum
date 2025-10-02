<?php
// config.php is needed for functions and session_start(), but it does not output HTML.
require_once __DIR__ . '/../config.php';

$errors = [];

// Handle the entire POST request before any HTML is sent.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    validate_csrf_token();

    // 2. Get form data
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $captcha = strtolower(trim($_POST['captcha'] ?? ''));

    // 3. Validate CAPTCHA - "5 plus three"
    if ($captcha !== 'eight' && $captcha !== '8') {
        $errors[] = 'Incorrect CAPTCHA answer.';
    }

    // 4. Validate username
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be between 3 and 20 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    } elseif (get_user_by_username($username)) {
        $errors[] = 'This username is already taken.';
    }

    // 5. Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // 6. If no errors, create user
    if (empty($errors)) {
        $success = create_user($username, $password, $display_name);
        if ($success) {
            // Redirect to login page with a success message. This will now work.
            redirect('login.php?registered=true');
        } else {
            $errors[] = 'An error occurred during registration. Please try again.';
        }
    }
}

// Now that all logic is done, we can start sending the HTML page.
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Register</h2>

<?php if (!empty($errors)): ?>
    <div class="errors" style="background-color: #FFD2D2; border: 1px solid #FF0000; color: #D8000C; padding: 10px; margin-bottom: 15px;">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="register.php" method="post">
    <div>
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
    </div>
    <div>
        <label for="display_name">Display Name (Optional)</label>
        <input type="text" name="display_name" id="display_name">
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
    </div>
    <div>
        <label for="password_confirm">Confirm Password</label>
        <input type="password" name="password_confirm" id="password_confirm" required>
    </div>
    <div>
        <label for="captcha">What is five plus three? (in words or number)</label>
        <input type="text" name="captcha" id="captcha" required>
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <div>
        <button type="submit">Register</button>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>