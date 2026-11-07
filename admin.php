<?php
require 'includes/db.php';
require_login();

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access only.');
}

$pageTitle = 'Admin Dashboard';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $type = $_POST['type'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

       $status = $_POST['status'] ?? '';

    if (
        $type === 'membership' &&
        in_array($status, ['approved', 'rejected'], true)
    ) {
        $stmt = $pdo->prepare(
            'UPDATE memberships SET status = ? WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
        $message = 'Membership request updated.';
    } elseif ($type === 'club_create' || $type === 'club_update') {
        $clubName = trim($_POST['club_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $about = trim($_POST['about'] ?? '');
        $emoji = trim($_POST['emoji'] ?? '');
        $trainingDays = trim($_POST['training_days'] ?? '');
        $trainingTime = trim($_POST['training_time'] ?? '');
        $venue = trim($_POST['club_venue'] ?? '');

        if (
            $clubName === '' || $category === '' || $description === '' ||
            $about === '' || $emoji === '' || $trainingDays === '' ||
            $trainingTime === '' || $venue === ''
        ) {
            $error = 'Complete all required sports club details.';
        } else {
            try {
                if ($type === 'club_create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO clubs
                            (name, category, tagline, description, about, emoji,
                             training_days, training_time, venue)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $clubName, $category, $tagline, $description, $about,
                        $emoji, $trainingDays, $trainingTime, $venue
                    ]);
                    $message = 'Sports club created.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE clubs
                         SET name = ?, category = ?, tagline = ?, description = ?,
                             about = ?, emoji = ?, training_days = ?,
                             training_time = ?, venue = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([
                        $clubName, $category, $tagline, $description, $about,
                        $emoji, $trainingDays, $trainingTime, $venue, $id
                    ]);
                    $message = 'Sports club updated.';
                }
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    $error = 'A sports club with that name already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($type === 'club_delete') {
        $stmt = $pdo->prepare('DELETE FROM clubs WHERE id = ?');
        $stmt->execute([$id]);
        $message = $stmt->rowCount() > 0
            ? 'Sports club deleted.'
            : 'That sports club could not be found.';
    }
}

$clubs = $pdo->query('SELECT * FROM clubs ORDER BY name')->fetchAll();
$memberships = $pdo->query(
    'SELECT
        memberships.id,
        memberships.joined_at,
        users.name AS student_name,
        users.student_id,
        clubs.name AS club_name
     FROM memberships
     JOIN users ON users.id = memberships.user_id
     JOIN clubs ON clubs.id = memberships.club_id
     WHERE memberships.status = "pending"
     ORDER BY memberships.joined_at'
)->fetchAll();

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">SPORTSHUB MANAGEMENT</span>
  <h1>Admin dashboard.</h1>
  <p>
    Review membership and venue requests, schedule training,
    and manage campus events and results.
  </p>
</section>

<div class="page-wrap">
  <?php if ($message !== ''): ?>
    <div class="alert"><?= e($message) ?></div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <section class="form-card" style="max-width:none;margin-top:38px">
    <span class="eyebrow">SPORTS DIRECTORY</span>
    <h2>Add a sports club</h2>
    <form method="post" action="admin.php">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="type" value="club_create">
      <div class="grid">
        <label>Club name
          <input class="field" name="club_name" maxlength="80" required>
        </label>
        <label>Category
          <input class="field" name="category" maxlength="50" placeholder="Team sport" required>
        </label>
        <label>Tagline
          <input class="field" name="tagline" maxlength="120" placeholder="TEAMWORK · PRECISION">
        </label>
        <label>Card description
          <input class="field" name="description" maxlength="255" required>
        </label>
        <label>Sport emoji
          <input class="field" name="emoji" maxlength="12" placeholder="🏐" required>
        </label>
        <label>Training days
          <input class="field" name="training_days" maxlength="100" placeholder="Monday, Thursday" required>
        </label>
        <label>Training time
          <input class="field" name="training_time" maxlength="80" placeholder="4:00 PM – 6:00 PM" required>
        </label>
        <label>Venue
          <input class="field" name="club_venue" maxlength="120" required>
        </label>
        <label>About the club
          <textarea name="about" rows="3" required></textarea>
        </label>
      </div>
      <button class="button" type="submit" style="margin-top:14px">Add sports club ↗</button>
    </form>
  </section>

  <div class="section-head" style="margin-top:34px">
    <h2>Edit existing clubs</h2>
  </div>
  <div class="grid">
    <?php foreach ($clubs as $club): ?>
      <article class="panel">
        <form method="post" action="admin.php">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="type" value="club_update">
          <input type="hidden" name="id" value="<?= (int) $club['id'] ?>">
          <span class="eyebrow">EDIT SPORTS CLUB</span>
          <h3><?= e($club['emoji']) ?> <?= e($club['name']) ?></h3>
          <label>Club name
            <input class="field" name="club_name" maxlength="80" value="<?= e($club['name']) ?>" required>
          </label>
          <label>Category
            <input class="field" name="category" maxlength="50" value="<?= e($club['category']) ?>" required>
          </label>
          <label>Tagline
            <input class="field" name="tagline" maxlength="120" value="<?= e($club['tagline']) ?>">
          </label>
          <label>Card description
            <input class="field" name="description" maxlength="255" value="<?= e($club['description']) ?>" required>
          </label>
          <label>Sport emoji
            <input class="field" name="emoji" maxlength="12" value="<?= e($club['emoji']) ?>" required>
          </label>
          <label>Training days
            <input class="field" name="training_days" maxlength="100" value="<?= e($club['training_days']) ?>" required>
          </label>
          <label>Training time
            <input class="field" name="training_time" maxlength="80" value="<?= e($club['training_time']) ?>" required>
          </label>
          <label>Venue
            <input class="field" name="club_venue" maxlength="120" value="<?= e($club['venue']) ?>" required>
          </label>
          <label>About the club
            <textarea name="about" rows="3" required><?= e($club['about']) ?></textarea>
          </label>
          <button class="button" type="submit">Save club changes</button>
        </form>
        <form method="post" action="admin.php" onsubmit="return confirm('Delete this club? Its membership and training records will also be removed.')" style="margin-top:10px">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="type" value="club_delete">
          <input type="hidden" name="id" value="<?= (int) $club['id'] ?>">
          <button class="button dark small" type="submit">Delete club</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</div>
  <div class="section-head">
    <h2>Club membership approvals</h2>
    <a class="button" href="reports.php">Participation reports ↗</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>STUDENT</th><th>STUDENT ID</th><th>CLUB</th><th>REQUESTED</th><th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($memberships as $membership): ?>
          <tr>
            <td><?= e($membership['student_name']) ?></td>
            <td><?= e($membership['student_id']) ?></td>
            <td><?= e($membership['club_name']) ?></td>
            <td><?= date('d M Y', strtotime($membership['joined_at'])) ?></td>
            <td>
              <?php foreach (['approved', 'rejected'] as $decision): ?>
                <form method="post" action="admin.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="type" value="membership">
                  <input type="hidden" name="id" value="<?= (int) $membership['id'] ?>">
                  <input type="hidden" name="status" value="<?= e($decision) ?>">
                  <button class="text-link" type="submit" style="border:0;background:none;cursor:pointer">
                    <?= ucfirst($decision) ?>
                  </button>
                </form>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  

<?php require 'includes/footer.php'; ?>
