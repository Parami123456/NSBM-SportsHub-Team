<?php
require 'includes/db.php';
require_login();

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Admin access only.');
}

$pageTitle = 'Participation & Performance Reports';
$defaultFrom = date('Y-01-01');
$defaultTo = date('Y-12-31');
$from = $_GET['from'] ?? $defaultFrom;
$to = $_GET['to'] ?? $defaultTo;
$error = '';

$isValidDate = static function ($value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
    return $date !== false && $date->format('Y-m-d') === $value;
};

if (!$isValidDate($from) || !$isValidDate($to) || $from > $to) {
    $error = 'Choose a valid date range.';
    $from = $defaultFrom;
    $to = $defaultTo;
}

$stmt = $pdo->prepare(
    'SELECT clubs.name, COUNT(memberships.id) AS participant_count
     FROM clubs
     LEFT JOIN memberships
       ON memberships.club_id = clubs.id
      AND memberships.status = "approved"
      AND DATE(memberships.joined_at) BETWEEN ? AND ?
     GROUP BY clubs.id, clubs.name
     ORDER BY participant_count DESC, clubs.name'
);
$stmt->execute([$from, $to]);
$clubParticipation = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT events.id, events.title, events.event_type, events.event_date,
            COUNT(event_registrations.id) AS registration_count
     FROM events
     LEFT JOIN event_registrations
       ON event_registrations.event_id = events.id
      AND event_registrations.status = "registered"
     WHERE events.event_date BETWEEN ? AND ?
     GROUP BY events.id, events.title, events.event_type, events.event_date
     ORDER BY events.event_date, events.title'
);
$stmt->execute([$from, $to]);
$eventParticipation = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT facilities.name,
            COUNT(bookings.id) AS request_count,
            SUM(CASE WHEN bookings.status = "approved" THEN 1 ELSE 0 END)
              AS approved_count
     FROM facilities
     LEFT JOIN bookings
       ON bookings.facility_id = facilities.id
      AND bookings.booking_date BETWEEN ? AND ?
     GROUP BY facilities.id, facilities.name
     ORDER BY request_count DESC, facilities.name'
);
$stmt->execute([$from, $to]);
$facilityUsage = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT equipment.name,
            COUNT(equipment_bookings.id) AS request_count,
            COALESCE(SUM(equipment_bookings.quantity), 0) AS quantity_requested,
            COALESCE(SUM(CASE WHEN equipment_bookings.status = "approved" THEN 1 ELSE 0 END), 0)
              AS approved_count
     FROM equipment
     LEFT JOIN equipment_bookings
       ON equipment_bookings.equipment_id = equipment.id
      AND equipment_bookings.booking_date BETWEEN ? AND ?
     GROUP BY equipment.id, equipment.name
     ORDER BY request_count DESC, equipment.name'
);
$stmt->execute([$from, $to]);
$equipmentUsage = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT title, event_type, event_date, score
     FROM events
     WHERE status = "completed"
       AND event_date BETWEEN ? AND ?
     ORDER BY event_date DESC, title'
);
$stmt->execute([$from, $to]);
$matchResults = $stmt->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'sportshub-report-' . $from . '-to-' . $to . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Report type', 'Item', 'Date', 'Count', 'Status / details']);

    foreach ($clubParticipation as $row) {
        fputcsv($output, ['Club participation', $row['name'], $from . ' to ' . $to, $row['participant_count'], 'Approved memberships']);
    }
    foreach ($eventParticipation as $row) {
        fputcsv($output, ['Event registrations', $row['event_type'] . ': ' . $row['title'], $row['event_date'], $row['registration_count'], 'Current registrations']);
    }
    foreach ($facilityUsage as $row) {
        fputcsv($output, ['Facility bookings', $row['name'], $from . ' to ' . $to, $row['request_count'], 'Approved: ' . (int) $row['approved_count']]);
    }
    foreach ($equipmentUsage as $row) {
        fputcsv($output, ['Equipment bookings', $row['name'], $from . ' to ' . $to, $row['request_count'], 'Approved: ' . (int) $row['approved_count'] . '; items requested: ' . (int) $row['quantity_requested']]);
    }
    foreach ($matchResults as $row) {
        fputcsv($output, ['Match performance', $row['event_type'] . ': ' . $row['title'], $row['event_date'], '', 'Completed · ' . ($row['score'] ?: 'Score not entered')]);
    }
    fclose($output);
    exit;
}

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">SPORTSHUB ANALYTICS</span>
  <h1>Participation &amp; performance.</h1>
  <p>Review club activity, event registrations, facility usage and completed match results.</p>
</section>

<div class="page-wrap">
  <div class="section-head">
    <div>
      <span class="eyebrow">REPORT PERIOD</span>
      <h2>Choose a date range.</h2>
    </div>
    <a class="text-link" href="admin.php">← Back to admin dashboard</a>
  </div>

  <?php if ($error !== ''): ?>
    <div class="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form class="toolbar" method="get" action="reports.php">
    <label>From
      <input class="field" type="date" name="from" value="<?= e($from) ?>" required>
    </label>
    <label>To
      <input class="field" type="date" name="to" value="<?= e($to) ?>" required>
    </label>
    <button class="button" type="submit">Generate report</button>
    <a class="button dark" href="reports.php?from=<?= e(urlencode($from)) ?>&amp;to=<?= e(urlencode($to)) ?>&amp;export=csv">Download CSV ↗</a>
  </form>

  <section class="section">
    <span class="eyebrow">PARTICIPATION</span>
    <h2>Approved club memberships</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>SPORTS CLUB</th><th>APPROVED MEMBERS</th></tr></thead>
        <tbody>
          <?php foreach ($clubParticipation as $row): ?>
            <tr><td><?= e($row['name']) ?></td><td><?= (int) $row['participant_count'] ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="section">
    <span class="eyebrow">EVENTS</span>
    <h2>Event registrations</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>EVENT</th><th>DATE</th><th>REGISTRATIONS</th></tr></thead>
        <tbody>
          <?php foreach ($eventParticipation as $row): ?>
            <tr>
              <td><?= e($row['event_type']) ?> · <?= e($row['title']) ?></td>
              <td><?= date('d M Y', strtotime($row['event_date'])) ?></td>
              <td><?= (int) $row['registration_count'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($eventParticipation) === 0): ?><p class="muted">No events in this date range.</p><?php endif; ?>
  </section>

  <section class="section">
    <span class="eyebrow">FACILITIES</span>
    <h2>Facility booking activity</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>FACILITY</th><th>BOOKING REQUESTS</th><th>APPROVED</th></tr></thead>
        <tbody>
          <?php foreach ($facilityUsage as $row): ?>
            <tr>
              <td><?= e($row['name']) ?></td>
              <td><?= (int) $row['request_count'] ?></td>
              <td><?= (int) $row['approved_count'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="section">
    <span class="eyebrow">EQUIPMENT</span>
    <h2>Equipment booking activity</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>EQUIPMENT</th><th>REQUESTS</th><th>ITEMS REQUESTED</th><th>APPROVED</th></tr></thead>
        <tbody>
          <?php foreach ($equipmentUsage as $row): ?>
            <tr>
              <td><?= e($row['name']) ?></td>
              <td><?= (int) $row['request_count'] ?></td>
              <td><?= (int) $row['quantity_requested'] ?></td>
              <td><?= (int) $row['approved_count'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="section">
    <span class="eyebrow">PERFORMANCE</span>
    <h2>Completed match results</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>EVENT</th><th>DATE</th><th>RESULT</th></tr></thead>
        <tbody>
          <?php foreach ($matchResults as $row): ?>
            <tr>
              <td><?= e($row['event_type']) ?> · <?= e($row['title']) ?></td>
              <td><?= date('d M Y', strtotime($row['event_date'])) ?></td>
              <td><?= e($row['score'] ?: 'Score not entered') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (count($matchResults) === 0): ?><p class="muted">No completed matches in this date range.</p><?php endif; ?>
  </section>
</div>

<?php require 'includes/footer.php'; ?>