<?php
require 'includes/db.php';

$pageTitle = 'Sign in';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, name, email, password_hash, role
         FROM users
         WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role']
        ];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Email or password is incorrect.';
}

require 'includes/header.php';
?>

<div class="form-card">
  <span class="eyebrow">WELCOME BACK</span>
  <h2>Sign in to SportsHub</h2>

  <?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

    <label>
      Email address
      <input
        class="field"
        type="email"
        name="email"
        autocomplete="email"
        required
      >
    </label>

    <label>
      Password
      <input
        class="field"
        type="password"
        name="password"
        autocomplete="current-password"
        required
      >
    </label>

    <button class="button" type="submit">Sign in ↗</button>
  </form>

  <p class="muted">
    New to the hub?
    <a class="text-link" href="register.php">Create a student account</a>
  </p>
</div>

<?php require 'includes/footer.php'; ?>