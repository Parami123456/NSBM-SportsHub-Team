<?php
require 'includes/db.php';

$pageTitle = 'Create account';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must contain at least 8 characters.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (name, email, student_id, password_hash, role, created_at)
                 VALUES (?, ?, ?, ?, "student", NOW())'
            );

            $stmt->execute([
                $name,
                $email,
                $studentId,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $_SESSION['user'] = [
                'id' => (int) $pdo->lastInsertId(),
                'name' => $name,
                'role' => 'student'
            ];

            header('Location: dashboard.php');
            exit;
        } catch (PDOException $error) {
            $error = 'That email or student ID is already registered.';
        }
    }
}

require 'includes/header.php';
?>

<div class="form-card">
  <span class="eyebrow">JOIN THE SPORTS COMMUNITY</span>
  <h2>Create your account</h2>
  <p class="muted">
    Use your NSBM student details to request club memberships
    and book facilities.
  </p>

  <?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="register.php">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

    <label>
      Full name
      <input
        class="field"
        type="text"
        name="name"
        maxlength="100"
        required
      >
    </label>

    <label>
      NSBM email
      <input class="field" type="email" name="email" required>
    </label>

    <label>
      Student ID
      <input
        class="field"
        type="text"
        name="student_id"
        maxlength="30"
        required
      >
    </label>

    <label>
      Password (8 or more characters)
      <input
        class="field"
        type="password"
        name="password"
        minlength="8"
        required
      >
    </label>

    <button class="button" type="submit">Create account ↗</button>
  </form>

  <p class="muted">
    Already registered?
    <a class="text-link" href="login.php">Sign in</a>
  </p>
</div>

<?php require 'includes/footer.php'; ?>