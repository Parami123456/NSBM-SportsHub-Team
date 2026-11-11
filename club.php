<?php
require 'includes/db.php';

$clubId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ?');
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
    http_response_code(404);
    exit('Club not found.');
}

$pageTitle = $club['name'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    require_login();

    $stmt = $pdo->prepare(
        'INSERT INTO memberships (user_id, club_id, status, joined_at)
         VALUES (?, ?, "pending", NOW())
         ON DUPLICATE KEY UPDATE
         status = IF(status = "rejected", "pending", status)'
    );

    $stmt->execute([
        $_SESSION['user']['id'],
        $clubId
    ]);

    $message = 'Your membership request has been sent for approval.';
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM memberships
     WHERE club_id = ? AND status = "approved"'
);
$stmt->execute([$clubId]);
$memberCount = $stmt->fetchColumn();

require 'includes/header.php';
?>
<script>
  document.body.classList.add('club-detail-page');
  document.body.classList.add('sport-<?= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $club['name'])); ?>');
</script>

<section class="page-hero">
  <span class="eyebrow">
    <?= e($club['category']) ?> · NSBM SPORTS CLUB
  </span>

  <h1><?= e($club['emoji']) ?> <?= e($club['name']) ?></h1>
  <p><?= e($club['description']) ?></p>
</section>

<div class="page-wrap">
  <div class="grid">
    <section class="panel" style="grid-column: span 2">
      <span class="eyebrow">ABOUT THE CLUB</span>
      <h3><?= e($club['tagline']) ?></h3>
      <p class="muted"><?= e($club['about']) ?></p>

      <h3>Training information</h3>
      <p class="muted">
        Training: <?= e($club['training_days']) ?><br>
        Time: <?= e($club['training_time']) ?><br>
        Venue: <?= e($club['venue']) ?>
      </p>

      <?php if ($message !== ''): ?>
        <div class="alert"><?= e($message) ?></div>
      <?php endif; ?>

      <form method="post" action="club.php?id=<?= $clubId ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <button class="button" type="submit">Request to join ↗</button>
      </form>
    </section>

    <aside class="panel">
      <span style="font-size: 50px"><?= e($club['emoji']) ?></span>
      <h3><?= (int) $memberCount ?> members</h3>
      <p class="muted">
        Membership requests are reviewed by the club administrator.
        Sign in or create your student account to apply.
      </p>
      <a class="text-link" href="schedule.php?club=<?= $clubId ?>">
        View training schedule ↗
      </a>
    </aside>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
