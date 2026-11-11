<?php
require 'includes/db.php';
require_login();

$userRole = strtolower($_SESSION['user']['role'] ?? '');
if ($userRole !== 'admin' && $userRole !== 'administrator') {
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
    } elseif (
        $type === 'booking' &&
        in_array($status, ['approved', 'rejected'], true)
    ) {
        $stmt = $pdo->prepare(
            'UPDATE bookings SET status = ? WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
        $message = 'Facility booking request updated.';
    } elseif (
        $type === 'equipment_booking' &&
        in_array($status, ['approved', 'rejected'], true)
    ) {
        $stmt = $pdo->prepare(
            'UPDATE equipment_bookings SET status = ? WHERE id = ?'
        );
        $stmt->execute([$status, $id]);
        $message = 'Equipment booking request updated.';    } elseif ($type === 'club_create' || $type === 'club_update') {
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
    } elseif ($type === 'event_create') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $eventType = $_POST['event_type'] ?? 'Match';
        $eventDate = $_POST['event_date'] ?? '';
        $eventTime = $_POST['event_time'] ?? '';
        $venue = trim($_POST['venue'] ?? '');

        if ($title === '' || $venue === '' || $eventDate === '' || $eventTime === '') {
            $error = 'Complete the required event fields.';
        } elseif (!in_array($eventType, ['Match', 'Tournament'], true)) {
            $error = 'Choose a valid event type.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO events
                    (title, subtitle, event_type, event_date,
                     event_time, venue, status)
                 VALUES (?, ?, ?, ?, ?, ?, "upcoming")'
            );
            $stmt->execute([
                $title,
                $subtitle,
                $eventType,
                $eventDate,
                $eventTime,
                $venue
            ]);
            $message = 'Event created successfully.';
        }
    } elseif ($type === 'event_result') {
        $newStatus = $_POST['status'] ?? '';
        $score = trim($_POST['score'] ?? '');

        if (!in_array($newStatus, ['upcoming', 'completed', 'cancelled'], true)) {
            $error = 'Choose a valid event status.';
        } elseif (strlen($score) > 80) {
            $error = 'The score must be 80 characters or fewer.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE events SET status = ?, score = ? WHERE id = ?'
            );
            $stmt->execute([
                $newStatus,
                $score === '' ? null : $score,
                $id
            ]);
            $message = 'Event result and status updated.';
        }
    } elseif ($type === 'training_create' || $type === 'training_update') {
        $clubId = (int) ($_POST['club_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sessionDate = $_POST['session_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $venue = trim($_POST['venue'] ?? '');
        $coach = trim($_POST['coach'] ?? '');

        $dateParts = explode('-', $sessionDate);
        $validDate = count($dateParts) === 3 &&
            checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0]);
        $validTimes = preg_match('/^\d{2}:\d{2}$/', $startTime) &&
            preg_match('/^\d{2}:\d{2}$/', $endTime) &&
            $startTime < $endTime;

        if ($clubId < 1 || $title === '' || !$validDate || !$validTimes || $venue === '' || $coach === '') {
            $error = 'Complete the training details and make sure the end time is after the start time.';
        } else {
            if ($type === 'training_create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO training_sessions
                        (club_id, title, session_date, start_time,
                         end_time, venue, coach)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $clubId, $title, $sessionDate, $startTime,
                    $endTime, $venue, $coach
                ]);
                $message = 'Training session scheduled.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE training_sessions
                     SET club_id = ?, title = ?, session_date = ?,
                         start_time = ?, end_time = ?, venue = ?, coach = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $clubId, $title, $sessionDate, $startTime,
                    $endTime, $venue, $coach, $id
                ]);
                $message = 'Training session updated.';
            }
        }
    } elseif ($type === 'training_delete') {
        $stmt = $pdo->prepare('DELETE FROM training_sessions WHERE id = ?');
        $stmt->execute([$id]);
        $message = 'Training session deleted.';
    }
}

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

$bookings = $pdo->query(
    'SELECT
        bookings.id,
        bookings.booking_date,
        bookings.start_time,
        bookings.end_time,
        bookings.purpose,
        users.name AS student_name,
        facilities.name AS facility_name
     FROM bookings
     JOIN users ON users.id = bookings.user_id
     JOIN facilities ON facilities.id = bookings.facility_id
     WHERE bookings.status = "pending"
     ORDER BY bookings.booking_date'
)->fetchAll();

$equipmentBookings = $pdo->query(
    'SELECT
        equipment_bookings.id,
        equipment_bookings.booking_date,
        equipment_bookings.start_time,
        equipment_bookings.end_time,
        equipment_bookings.quantity,
        equipment_bookings.purpose,
        users.name AS student_name,
        equipment.name AS equipment_name
     FROM equipment_bookings
     JOIN users ON users.id = equipment_bookings.user_id
     JOIN equipment ON equipment.id = equipment_bookings.equipment_id
     WHERE equipment_bookings.status = "pending"
     ORDER BY equipment_bookings.booking_date'
)->fetchAll();
$clubs = $pdo->query('SELECT * FROM clubs ORDER BY name')->fetchAll();
$events = $pdo->query(
    'SELECT id, title, event_type, event_date, event_time,
            venue, status, score
     FROM events
     ORDER BY event_date DESC, event_time DESC'
)->fetchAll();
$trainingSessions = $pdo->query(
    'SELECT training_sessions.*, clubs.name AS club_name
     FROM training_sessions
     JOIN clubs ON clubs.id = training_sessions.club_id
     ORDER BY training_sessions.session_date DESC,
              training_sessions.start_time DESC'
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

  <div class="section-head" style="margin-top:34px">
    <h2>Facility booking approvals</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>STUDENT</th><th>FACILITY</th><th>DATE &amp; TIME</th><th>PURPOSE</th><th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $booking): ?>
          <tr>
            <td><?= e($booking['student_name']) ?></td>
            <td><?= e($booking['facility_name']) ?></td>
            <td><?= e($booking['booking_date']) ?> · <?= e($booking['start_time']) ?>–<?= e($booking['end_time']) ?></td>
            <td><?= e($booking['purpose']) ?></td>
            <td>
              <?php foreach (['approved', 'rejected'] as $decision): ?>
                <form method="post" action="admin.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="type" value="booking">
                  <input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
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

  <div class="section-head" style="margin-top:34px">
    <h2>Equipment booking approvals</h2>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>STUDENT</th><th>EQUIPMENT</th><th>QUANTITY</th><th>DATE &amp; TIME</th><th>PURPOSE</th><th>ACTION</th></tr>
      </thead>
      <tbody>
        <?php foreach ($equipmentBookings as $equipmentBooking): ?>
          <tr>
            <td><?= e($equipmentBooking['student_name']) ?></td>
            <td><?= e($equipmentBooking['equipment_name']) ?></td>
            <td><?= (int) $equipmentBooking['quantity'] ?></td>
            <td><?= e($equipmentBooking['booking_date']) ?> · <?= e($equipmentBooking['start_time']) ?>–<?= e($equipmentBooking['end_time']) ?></td>
            <td><?= e($equipmentBooking['purpose']) ?></td>
            <td>
              <?php foreach (['approved', 'rejected'] as $decision): ?>
                <form method="post" action="admin.php" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="type" value="equipment_booking">
                  <input type="hidden" name="id" value="<?= (int) $equipmentBooking['id'] ?>">
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

  <section class="form-card" style="max-width:none;margin-top:38px">
    <span class="eyebrow">TRAINING TIMETABLE</span>
    <h2>Schedule a training session</h2>
    <form method="post" action="admin.php">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="type" value="training_create">
      <div class="grid">
        <label>Sports club
          <select name="club_id" required>
            <?php foreach ($clubs as $club): ?>
              <option value="<?= (int) $club['id'] ?>"><?= e($club['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Session title
          <input class="field" name="title" maxlength="120" required>
        </label>
        <label>Date
          <input class="field" type="date" name="session_date" required>
        </label>
        <label>Start time
          <input class="field" type="time" name="start_time" required>
        </label>
        <label>End time
          <input class="field" type="time" name="end_time" required>
        </label>
        <label>Venue
          <input class="field" name="venue" maxlength="120" required>
        </label>
        <label>Coach
          <input class="field" name="coach" maxlength="100" required>
        </label>
      </div>
      <button class="button" type="submit" style="margin-top:14px">Add training session ↗</button>
    </form>
  </section>

  <div class="section-head" style="margin-top:34px">
    <h2>Manage training sessions</h2>
  </div>
  <?php if (count($trainingSessions) > 0): ?>
    <div class="grid">
      <?php foreach ($trainingSessions as $session): ?>
        <article class="panel">
          <span class="eyebrow"><?= e($session['club_name']) ?></span>
          <h3>Edit training session</h3>
          <form method="post" action="admin.php">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="type" value="training_update">
            <input type="hidden" name="id" value="<?= (int) $session['id'] ?>">
            <label>Sports club
              <select name="club_id" required>
                <?php foreach ($clubs as $club): ?>
                  <option value="<?= (int) $club['id'] ?>" <?= (int) $club['id'] === (int) $session['club_id'] ? 'selected' : '' ?>><?= e($club['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Session title
              <input class="field" name="title" maxlength="120" value="<?= e($session['title']) ?>" required>
            </label>
            <label>Date
              <input class="field" type="date" name="session_date" value="<?= e($session['session_date']) ?>" required>
            </label>
            <div class="row">
              <label>Start time
                <input class="field" type="time" name="start_time" value="<?= e(substr($session['start_time'], 0, 5)) ?>" required>
              </label>
              <label>End time
                <input class="field" type="time" name="end_time" value="<?= e(substr($session['end_time'], 0, 5)) ?>" required>
              </label>
            </div>
            <label>Venue
              <input class="field" name="venue" maxlength="120" value="<?= e($session['venue']) ?>" required>
            </label>
            <label>Coach
              <input class="field" name="coach" maxlength="100" value="<?= e($session['coach']) ?>" required>
            </label>
            <button class="button" type="submit">Save changes</button>
          </form>
          <form method="post" action="admin.php" onsubmit="return confirm('Delete this training session?')" style="margin-top:10px">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="type" value="training_delete">
            <input type="hidden" name="id" value="<?= (int) $session['id'] ?>">
            <button class="button dark small" type="submit">Delete session</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">No training sessions have been scheduled yet.</p>
  <?php endif; ?>

  <section class="form-card" style="max-width:none;margin-top:38px">
    <span class="eyebrow">PUBLISH TO MATCH CENTRE</span>
    <h2>Create campus event</h2>
    <form method="post" action="admin.php">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="type" value="event_create">
      <div class="grid">
        <label>Event title
          <input class="field" type="text" name="title" maxlength="150" required>
        </label>
        <label>Subtitle
          <input class="field" type="text" name="subtitle" maxlength="180">
        </label>
        <label>Event type
          <select name="event_type" required>
            <option value="Match">Match</option>
            <option value="Tournament">Tournament</option>
          </select>
        </label>
        <label>Date
          <input class="field" type="date" name="event_date" required>
        </label>
        <label>Time
          <input class="field" type="time" name="event_time" required>
        </label>
        <label>Venue
          <input class="field" type="text" name="venue" maxlength="120" required>
        </label>
      </div>
      <button class="button" type="submit" style="margin-top:14px">Create event ↗</button>
    </form>
  </section>

  <div class="section-head" style="margin-top:34px">
    <h2>Manage fixtures and results</h2>
    <a class="text-link" href="fixtures.php">View match centre ↗</a>
  </div>
  <?php if (count($events) > 0): ?>
    <div class="grid">
      <?php foreach ($events as $event): ?>
        <article class="panel">
          <span class="tag"><?= e($event['event_type']) ?> · <?= e(strtoupper($event['status'])) ?></span>
          <h3><?= e($event['title']) ?></h3>
          <p class="muted"><?= date('d M Y', strtotime($event['event_date'])) ?> · <?= e($event['venue']) ?></p>
          <form method="post" action="admin.php">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="type" value="event_result">
            <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
            <label>Status
              <select name="status" required>
                <?php foreach (['upcoming' => 'Upcoming', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $event['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Score / result
              <input class="field" name="score" maxlength="80" value="<?= e($event['score'] ?? '') ?>" placeholder="Example: NSBM 2 - 1 SLIIT">
            </label>
            <button class="button" type="submit">Save status and result</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">No events have been created yet.</p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
