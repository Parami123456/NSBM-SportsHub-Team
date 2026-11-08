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
        $type === 'booking' &&
        in_array($status, ['approved', 'rejected'], true)
    ) {
        $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        $message = 'Booking request updated.';
    } elseif (
        $type === 'membership' &&
        in_array($status, ['approved', 'rejected'], true)
    ) {
        $stmt = $pdo->prepare('UPDATE memberships SET status = ? WHERE id = ?');
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
        } elseif ($type === 'training_create' || $type === 'training_update') {
        // GIMHAN - Training Sessions
        $clubId = (int) ($_POST['training_club_id'] ?? 0);
        $title = trim($_POST['training_title'] ?? '');
        $trainingDate = trim($_POST['training_date'] ?? '');
        $startTime = trim($_POST['training_start'] ?? '');
        $endTime = trim($_POST['training_end'] ?? '');
        $trainingVenue = trim($_POST['training_venue'] ?? '');
        $coach = trim($_POST['training_coach'] ?? '');

        if ($clubId === 0 || $title === '' || $trainingDate === '' || $startTime === '' || $endTime === '' || $trainingVenue === '') {
            $error = 'Complete all required training details.';
        } else {
            if ($type === 'training_create') {
                $stmt = $pdo->prepare('INSERT INTO training_sessions (club_id, title, training_date, start_time, end_time, venue, coach) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$clubId, $title, $trainingDate, $startTime, $endTime, $trainingVenue, $coach]);
                $message = 'Training session created.';
            } else {
                $stmt = $pdo->prepare('UPDATE training_sessions SET club_id = ?, title = ?, training_date = ?, start_time = ?, end_time = ?, venue = ?, coach = ? WHERE id = ?');
                $stmt->execute([$clubId, $title, $trainingDate, $startTime, $endTime, $trainingVenue, $coach, $id]);
                $message = 'Training session updated.';
            }
        }
    } elseif ($type === 'training_delete') {
        $stmt = $pdo->prepare('DELETE FROM training_sessions WHERE id = ?');
        $stmt->execute([$id]);
        $message = $stmt->rowCount() > 0 ? 'Training session deleted.' : 'That training session could not be found.';
    }
}

$clubs = $pdo->query('SELECT * FROM clubs ORDER BY name')->fetchAll();
    $bookings = $pdo->query(
    'SELECT bookings.id, bookings.booking_date, bookings.start_time,
     bookings.end_time, bookings.purpose,
     users.name AS student_name,
     facilities.name AS facility_name
     FROM bookings
     JOIN users ON users.id = bookings.user_id
     JOIN facilities ON facilities.id = bookings.facility_id
     WHERE bookings.status = "pending"
     ORDER BY bookings.booking_date'
)->fetchAll();
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
// GIMHAN - Fetch training sessions
$trainings = $pdo->query(
    'SELECT training_sessions.*, clubs.name AS club_name
     FROM training_sessions
     JOIN clubs ON clubs.id = training_sessions.club_id
     ORDER BY training_sessions.training_date DESC, training_sessions.start_time'
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
      <div class="section-head" style="margin-top:44px">
    <h2>Facility Bookings</h2>
    <p>Approve or reject bookings - Managed by Thiwanka</p>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>STUDENT</th><th>FACILITY</th><th>DATE</th><th>TIME</th><th>PURPOSE</th><th>ACTION</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <tr>
            <td><?= e($booking['student_name']) ?></td>
            <td><?= e($booking['facility_name']) ?></td>
            <td><?= date('d M Y', strtotime($booking['booking_date'])) ?></td>
            <td><?= e($booking['start_time']) ?> - <?= e($booking['end_time']) ?></td>
            <td><?= e($booking['purpose']) ?></td>
            <td>
              <?php foreach (['approved', 'rejected'] as $decision): ?>
                <form method="post" action="admin.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="type" value="booking">
                  <input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                  <input type="hidden" name="status" value="<?= e($decision) ?>">
                  <button class="text-link" type="submit" style="border:0;background:none;cursor:pointer"><?= ucfirst($decision) ?></button>
                </form>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <!-- GIMHAN - TRAINING SESSIONS -->
  <div class="section-head" style="margin-top:44px">
    <h2>Training Sessions</h2>
    <p>Schedule training - Managed by Gimhan</p>
  </div>

  <section class="form-card" style="max-width:none">
    <span class="eyebrow">SCHEDULE TRAINING</span>
    <h2>Add training session</h2>
    <form method="post" action="admin.php">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="type" value="training_create">
      <div class="grid">
        <label>Club
          <select class="field" name="training_club_id" required>
            <option value="">Select club</option>
            <?php foreach ($clubs as $club): ?>
              <option value="<?= (int) $club['id'] ?>"><?= e($club['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Session title
          <input class="field" name="training_title" maxlength="100" placeholder="Morning Practice" required>
        </label>
        <label>Date
          <input class="field" type="date" name="training_date" required>
        </label>
        <label>Start time
          <input class="field" type="time" name="training_start" required>
        </label>
        <label>End time
          <input class="field" type="time" name="training_end" required>
        </label>
        <label>Venue
          <input class="field" name="training_venue" maxlength="120" placeholder="Main Ground" required>
        </label>
        <label>Coach
          <input class="field" name="training_coach" maxlength="80" placeholder="Coach name">
        </label>
      </div>
      <button class="button" type="submit" style="margin-top:14px">Add training session ↗</button>
    </form>
  </section>

  <div class="table-wrap" style="margin-top:20px">
    <table>
      <thead><tr><th>CLUB</th><th>TITLE</th><th>DATE</th><th>TIME</th><th>VENUE</th><th>COACH</th><th>ACTION</th></tr></thead>
      <tbody>
        <?php foreach ($trainings as $t): ?>
          <tr>
            <td><?= e($t['club_name']) ?></td>
            <td><?= e($t['title']) ?></td>
            <td><?= date('d M Y', strtotime($t['training_date'])) ?></td>
            <td><?= e($t['start_time']) ?> - <?= e($t['end_time']) ?></td>
            <td><?= e($t['venue']) ?></td>
            <td><?= e($t['coach']) ?></td>
            <td>
              <form method="post" action="admin.php" onsubmit="return confirm('Delete this training session?')" style="display:inline">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="type" value="training_delete">
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button class="text-link" type="submit" style="border:0;background:none;cursor:pointer">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  

<?php require 'includes/footer.php'; ?>
