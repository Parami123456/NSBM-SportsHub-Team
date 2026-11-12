<?php
require 'includes/db.php';

$pageTitle = 'Fixtures & Results';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    require_login();

    $eventId = (int) ($_POST['event_id'] ?? 0);
    $action = $_POST['action'] ?? 'register';

    if ($action === 'cancel') {
        $stmt = $pdo->prepare(
            'UPDATE event_registrations
             SET status = "cancelled"
             WHERE event_id = ? AND user_id = ? AND status = "registered"'
        );
        $stmt->execute([$eventId, $_SESSION['user']['id']]);

        if ($stmt->rowCount() > 0) {
            $message = 'Your event registration has been cancelled.';
        } else {
            $error = 'You are not currently registered for that event.';
        }
    } elseif ($action !== 'register') {
        $error = 'Invalid event registration action.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, event_date, status FROM events WHERE id = ?'
        );
        $stmt->execute([$eventId]);
        $eventToRegister = $stmt->fetch();

        if (!$eventToRegister) {
            $error = 'That event could not be found.';
        } elseif (
            $eventToRegister['status'] !== 'upcoming' ||
            $eventToRegister['event_date'] < date('Y-m-d')
        ) {
            $error = 'Registration is closed for this event.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO event_registrations (event_id, user_id)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE
                    status = "registered",
                    registered_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$eventId, $_SESSION['user']['id']]);
            $message = 'You are registered for this event.';
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT
        events.*,
        clubs.name AS club_name,
        clubs.emoji,
        (SELECT COUNT(*)
         FROM event_registrations AS registrations
         WHERE registrations.event_id = events.id
           AND registrations.status = "registered") AS registration_count,
        EXISTS (
            SELECT 1
            FROM event_registrations AS my_registration
            WHERE my_registration.event_id = events.id
              AND my_registration.user_id = ?
              AND my_registration.status = "registered"
        ) AS is_registered
     FROM events
     LEFT JOIN clubs ON clubs.id = events.club_id
     ORDER BY events.event_date, events.event_time'
);
$stmt->execute([$_SESSION['user']['id'] ?? 0]);
$events = $stmt->fetchAll();

require 'includes/header.php';
?>

<script>
  document.body.classList.add('fixtures-page');
</script>

<section class="page-hero">
  <span class="eyebrow">SEASON 2026</span>
  <h1>Fixtures &amp; results.</h1>
  <p>
    Keep up with campus leagues, inter-faculty matches,
    tournaments and the scores that shape the season.
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
    <div>
      <span class="eyebrow">MATCH CENTRE</span>
      <h2>On the calendar.</h2>
    </div>

    <span class="tag"><?= count($events) ?> EVENTS</span>
  </div>

  <div class="grid">
    <?php foreach ($events as $event): ?>
      <article class="panel sport-<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $event['club_name'] ?? 'general')); ?>">
        <div class="row">
          <span class="tag"><?= e($event['event_type']) ?></span>
          <span class="muted">
            <?= date('d M Y', strtotime($event['event_date'])) ?>
          </span>
        </div>

        <h3><?= e($event['title']) ?></h3>
        <p class="muted"><?= e($event['subtitle']) ?></p>

        <p class="muted">
          <?= e($event['emoji'] ?? '🏆') ?>
          <?= e($event['club_name'] ?? 'Inter-faculty event') ?>
          · <?= date('h:i A', strtotime($event['event_time'])) ?>
          <br>
          <?= e($event['venue']) ?>
        </p>

        <?php if ($event['status'] === 'completed'): ?>
          <span class="tag">
            FINAL · <?= e($event['score'] ?? 'Result pending') ?>
          </span>
        <?php elseif ($event['status'] !== 'upcoming'): ?>
          <span class="tag">REGISTRATION CLOSED</span>
        <?php else: ?>
          <p class="muted">
            <?= (int) $event['registration_count'] ?> registered
          </p>

          <?php if (!empty($_SESSION['user'])): ?>
            <?php if ((bool) $event['is_registered']): ?>
              <div class="row">
                <span class="tag">YOU ARE REGISTERED</span>
                <form method="post" action="fixtures.php">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input
                    type="hidden"
                    name="event_id"
                    value="<?= (int) $event['id'] ?>"
                  >
                  <input type="hidden" name="action" value="cancel">
                  <button class="button dark small" type="submit">
                    Cancel registration
                  </button>
                </form>
              </div>
            <?php else: ?>
              <form method="post" action="fixtures.php">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input
                  type="hidden"
                  name="event_id"
                  value="<?= (int) $event['id'] ?>"
                >
                <input type="hidden" name="action" value="register">
                <button class="button" type="submit">
                  Register for this event ↗
                </button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <a class="text-link" href="login.php">
              Sign in to register ↗
            </a>
          <?php endif; ?>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (count($events) === 0): ?>
    <p class="muted">No fixtures have been added yet.</p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
