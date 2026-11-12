<?php
require 'includes/db.php';
require_login();

$pageTitle = 'Facilities & Bookings';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $facilityId = (int) ($_POST['facility_id'] ?? 0);
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');

    $stmt = $pdo->prepare(
        'INSERT INTO bookings
            (user_id, facility_id, booking_date, start_time,
             end_time, purpose, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "pending", NOW())'
    );

    $stmt->execute([
        $_SESSION['user']['id'],
        $facilityId,
        $bookingDate,
        $startTime,
        $endTime,
        $purpose
    ]);

    $message = 'Booking request submitted. You can track it from your dashboard.';
}

$facilities = $pdo->query(
    'SELECT * FROM facilities ORDER BY name'
)->fetchAll();

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">MAKE ROOM FOR THE GAME</span>
  <h1>Find your ground.</h1>
  <p>
    Browse campus sports venues and send a booking request for
    your next training session or team event.
  </p>
</section>

<div class="page-wrap">
  <?php if ($message !== ''): ?>
    <div class="alert"><?= e($message) ?></div>
  <?php endif; ?>

  <div class="section-head">
    <a class="button dark" href="equipment.php">Browse sports equipment ↗</a>
  </div>

  <div class="grid facilities-grid">
    <?php foreach ($facilities as $facility): ?>
      <article class="panel">
        <span style="font-size: 38px">🏟️</span>
        <h3><?= e($facility['name']) ?></h3>
        <p class="muted"><?= e($facility['description']) ?></p>
        <span class="tag">
          CAPACITY <?= (int) $facility['capacity'] ?>
        </span>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="form-card">
    <span class="eyebrow">REQUEST A SLOT</span>
    <h2>Book a facility</h2>

    <form method="post" action="facilities.php">
      <input
        type="hidden"
        name="csrf"
        value="<?= e(csrf()) ?>"
      >

      <label>
        Facility
        <select name="facility_id" required>
          <?php foreach ($facilities as $facility): ?>
            <option value="<?= (int) $facility['id'] ?>">
              <?= e($facility['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        Date
        <input
          class="field"
          type="date"
          name="booking_date"
          min="<?= date('Y-m-d') ?>"
          required
        >
      </label>

      <div class="row">
        <label>
          Start time
          <input class="field" type="time" name="start_time" required>
        </label>

        <label>
          End time
          <input class="field" type="time" name="end_time" required>
        </label>
      </div>

      <label>
        Purpose
        <textarea
          name="purpose"
          maxlength="250"
          rows="3"
          placeholder="Training, friendly match, team practice…"
          required
        ></textarea>
      </label>

      <button class="button" type="submit">
        Send booking request ↗
      </button>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
