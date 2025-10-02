<?php
// config.php is needed for functions and session_start(), but it does not output HTML.
require_once __DIR__ . '/../config.php';

$errors = [];
$registered_success = isset($_GET['registered']) && $_GET['registered'] === 'true';

// Handle the entire POST request before any HTML is sent.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    validate_csrf_token();

    // 2. Get form data
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 3. Validate input
    if (empty($username) || empty($password)) {
        $errors[] = 'Username and password are required.';
    } else {
        // 4. Fetch user and verify password
        $user = get_user_by_username($username);

        if ($user && password_verify($password, $user['password'])) {
            // 5. Regenerate session ID and store user data
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // 6. Redirect to homepage. This will now work correctly.
            redirect('index.php');
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

// Now that all logic is done, we can start sending the HTML page.
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Login</h2>

<?php if ($registered_success): ?>
    <div class="success" style="background-color: #DFF2BF; border: 1px solid #4F8A10; color: #4F8A10; padding: 10px; margin-bottom: 15px;">
        Registration successful! You can now log in.
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="errors" style="background-color: #FFD2D2; border: 1px solid #FF0000; color: #D8000C; padding: 10px; margin-bottom: 15px;">
        <strong>Login failed:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="login.php" method="post">
    <div>
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>
    </div>
    <div>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <div>
        <button type="submit">Login</button>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>