<?php
require 'includes/db.php';

$pageTitle = 'Training Schedule';
$selectedClub = (int) ($_GET['club'] ?? 0);

$sql = '
    SELECT
        training_sessions.*,
        clubs.name AS club_name,
        clubs.emoji
    FROM training_sessions
    JOIN clubs ON clubs.id = training_sessions.club_id
    WHERE training_sessions.session_date >= CURDATE()
';

$params = [];

if ($selectedClub > 0) {
    $sql .= ' AND clubs.id = ?';
    $params[] = $selectedClub;
}

$sql .= '
    ORDER BY training_sessions.session_date,
             training_sessions.start_time
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">SHOW UP. PUT IN THE WORK.</span>
  <h1>Training timetable.</h1>
  <p>
    Find your next session and get ready to play. Schedules are managed
    by club administrators and may be updated during the season.
  </p>
</section>

<div class="page-wrap">
  <div class="toolbar">
    <select
      aria-label="Filter by sports club"
      onchange="window.location.href='schedule.php?club=' + this.value"
    >
      <option value="0">All sports clubs</option>

      <?php
      $clubs = $pdo->query(
          'SELECT id, name FROM clubs ORDER BY name'
      );

      foreach ($clubs as $club):
      ?>
        <option
          value="<?= (int) $club['id'] ?>"
          <?= $selectedClub === (int) $club['id'] ? 'selected' : '' ?>
        >
          <?= e($club['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>DATE</th>
          <th>SESSION</th>
          <th>SPORTS CLUB</th>
          <th>TIME</th>
          <th>VENUE</th>
          <th>COACH</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($sessions as $session): ?>
          <tr>
            <td>
              <?= date('D, d M', strtotime($session['session_date'])) ?>
            </td>

            <td><b><?= e($session['title']) ?></b></td>

            <td>
              <?= e($session['emoji']) ?>
              <?= e($session['club_name']) ?>
            </td>

            <td>
              <?= date('h:i A', strtotime($session['start_time'])) ?>
              –
              <?= date('h:i A', strtotime($session['end_time'])) ?>
            </td>

            <td><?= e($session['venue']) ?></td>
            <td><?= e($session['coach']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (count($sessions) === 0): ?>
    <p class="muted">
      No upcoming training sessions have been scheduled yet.
    </p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>