<?php
require 'includes/db.php';

$pageTitle = 'Sports Equipment Booking';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    require_login();

    $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate);
    $validDate = $date !== false && $date->format('Y-m-d') === $bookingDate;
    $validTime = preg_match('/^\d{2}:\d{2}$/', $startTime) &&
        preg_match('/^\d{2}:\d{2}$/', $endTime) &&
        $startTime < $endTime;

    $stmt = $pdo->prepare(
        'SELECT id, name, quantity FROM equipment WHERE id = ?'
    );
    $stmt->execute([$equipmentId]);
    $selectedEquipment = $stmt->fetch();

    if (!$selectedEquipment) {
        $error = 'Choose an available equipment item.';
    } elseif (!$validDate || $bookingDate < date('Y-m-d')) {
        $error = 'Choose today or a future date.';
    } elseif (!$validTime || $quantity < 1 || $purpose === '') {
        $error = 'Enter a valid time, quantity and purpose.';
    } elseif ($quantity > (int) $selectedEquipment['quantity']) {
        $error = 'The requested quantity is higher than the total stock.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(quantity), 0)
             FROM equipment_bookings
             WHERE equipment_id = ?
               AND booking_date = ?
               AND status IN ("pending", "approved")
               AND start_time < ?
               AND end_time > ?'
        );
        $stmt->execute([
            $equipmentId,
            $bookingDate,
            $endTime,
            $startTime
        ]);
        $reservedQuantity = (int) $stmt->fetchColumn();
        $availableQuantity = (int) $selectedEquipment['quantity'] - $reservedQuantity;

        if ($quantity > $availableQuantity) {
            $error = 'Only ' . max(0, $availableQuantity) .
                ' item(s) are available for that time slot.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO equipment_bookings
                    (user_id, equipment_id, booking_date, start_time,
                     end_time, quantity, purpose, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, "pending", NOW())'
            );
            $stmt->execute([
                $_SESSION['user']['id'],
                $equipmentId,
                $bookingDate,
                $startTime,
                $endTime,
                $quantity,
                $purpose
            ]);
            $message = 'Equipment booking request sent for admin approval.';
        }
    }
}

$equipment = $pdo->query(
    'SELECT id, name, category, description, quantity
     FROM equipment
     ORDER BY category, name'
)->fetchAll();

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">GEAR UP FOR YOUR GAME</span>
  <h1>Book sports equipment.</h1>
  <p>Choose the gear, date and time you need. The administrator will review your request.</p>
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
      <span class="eyebrow">CAMPUS SPORTS GEAR</span>
      <h2>Equipment catalogue.</h2>
    </div>
    <a class="text-link" href="facilities.php">Book a facility ↗</a>
  </div>

  <div class="grid">
    <?php foreach ($equipment as $item): ?>
      <article class="panel">
        <span class="tag"><?= e($item['category']) ?></span>
        <h3><?= e($item['name']) ?></h3>
        <p class="muted"><?= e($item['description']) ?></p>
        <p class="muted">Stock: <?= (int) $item['quantity'] ?> items</p>

        <?php if (!empty($_SESSION['user'])): ?>
          <form method="post" action="equipment.php">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="equipment_id" value="<?= (int) $item['id'] ?>">
            <label>Date
              <input class="field" type="date" name="booking_date" min="<?= date('Y-m-d') ?>" required>
            </label>
            <div class="row">
              <label>From
                <input class="field" type="time" name="start_time" required>
              </label>
              <label>To
                <input class="field" type="time" name="end_time" required>
              </label>
            </div>
            <label>Quantity
              <input class="field" type="number" name="quantity" min="1" max="<?= (int) $item['quantity'] ?>" value="1" required>
            </label>
            <label>Purpose
              <input class="field" type="text" name="purpose" maxlength="250" placeholder="Club practice, training..." required>
            </label>
            <button class="button" type="submit">Request equipment ↗</button>
          </form>
        <?php else: ?>
          <a class="button" href="login.php">Sign in to request ↗</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (count($equipment) === 0): ?>
    <p class="muted">No sports equipment is listed yet.</p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>