<?php
require 'includes/db.php';
require_login();

$pageTitle = 'My SportsHub';
$user = $_SESSION['user'];

// Get the signed-in user's club membership requests.
$stmt = $pdo->prepare(
    'SELECT
        memberships.status,
        memberships.joined_at,
        clubs.name,
        clubs.emoji
     FROM memberships
     JOIN clubs ON clubs.id = memberships.club_id
     WHERE memberships.user_id = ?
     ORDER BY memberships.joined_at DESC'
);
$stmt->execute([$user['id']]);
$memberships = $stmt->fetchAll();

// Get the signed-in user's facility booking requests.
$stmt = $pdo->prepare(
    'SELECT
        bookings.*,
        facilities.name AS facility_name
     FROM bookings
     JOIN facilities ON facilities.id = bookings.facility_id
     WHERE bookings.user_id = ?
     ORDER BY bookings.booking_date DESC'
);
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

// Get the signed-in user's equipment booking requests.
$stmt = $pdo->prepare(
    'SELECT equipment_bookings.*, equipment.name AS equipment_name
     FROM equipment_bookings
     JOIN equipment ON equipment.id = equipment_bookings.equipment_id
     WHERE equipment_bookings.user_id = ?
     ORDER BY equipment_bookings.booking_date DESC'
);
$stmt->execute([$user['id']]);
$equipmentBookings = $stmt->fetchAll();

// Get the signed-in user's event registration history.
$stmt = $pdo->prepare(
    'SELECT
        event_registrations.status AS registration_status,
        event_registrations.registered_at,
        events.title,
        events.event_date,
        events.event_time,
        events.venue
     FROM event_registrations
     JOIN events ON events.id = event_registrations.event_id
     WHERE event_registrations.user_id = ?
     ORDER BY events.event_date DESC, events.event_time DESC'
);
$stmt->execute([$user['id']]);
$eventRegistrations = $stmt->fetchAll();

require 'includes/header.php';
?>

<section class="page-hero">
  <span class="eyebrow">YOUR SPORTS LIFE</span>
  <h1>Hey, <?= e($user['name']) ?>.</h1>
  <p>
    Your club requests, facility bookings and next steps,
    all in one place.
  </p>
</section>

<div class="page-wrap">
  <div class="section-head">
    <div>
      <span class="eyebrow">MY CLUBS</span>
      <h2>Membership requests.</h2>
    </div>

    <a class="button" href="clubs.php">Find a club ↗</a>
  </div>

  <?php if (count($memberships) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>CLUB</th>
            <th>REQUESTED</th>
            <th>STATUS</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($memberships as $membership): ?>
            <tr>
              <td>
                <?= e($membership['emoji']) ?>
                <?= e($membership['name']) ?>
              </td>
              <td>
                <?= date('d M Y', strtotime($membership['joined_at'])) ?>
              </td>
              <td>
                <span class="tag">
                  <?= e(strtoupper($membership['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">
      You have not requested a club membership yet.
      <a class="text-link" href="clubs.php">Explore clubs ↗</a>
    </p>
  <?php endif; ?>

  <div class="section-head" style="margin-top: 40px">
    <div>
      <span class="eyebrow">YOUR BOOKINGS</span>
      <h2>Facility requests.</h2>
    </div>

    <a class="text-link" href="facilities.php">Book a venue ↗</a>
  </div>

  <?php if (count($bookings) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>FACILITY</th>
            <th>DATE</th>
            <th>TIME</th>
            <th>PURPOSE</th>
            <th>STATUS</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($bookings as $booking): ?>
            <tr>
              <td><?= e($booking['facility_name']) ?></td>
              <td><?= e($booking['booking_date']) ?></td>
              <td>
                <?= e($booking['start_time']) ?>
                –
                <?= e($booking['end_time']) ?>
              </td>
              <td><?= e($booking['purpose']) ?></td>
              <td><?= e(strtoupper($booking['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No facility booking requests yet.</p>
  <?php endif; ?>

  <div class="section-head" style="margin-top: 40px">
    <div>
      <span class="eyebrow">SPORTS GEAR</span>
      <h2>Equipment requests.</h2>
    </div>
    <a class="text-link" href="equipment.php">Book equipment ↗</a>
  </div>

  <?php if (count($equipmentBookings) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>EQUIPMENT</th><th>DATE &amp; TIME</th><th>QUANTITY</th><th>PURPOSE</th><th>STATUS</th></tr>
        </thead>
        <tbody>
          <?php foreach ($equipmentBookings as $equipmentBooking): ?>
            <tr>
              <td><?= e($equipmentBooking['equipment_name']) ?></td>
              <td><?= e($equipmentBooking['booking_date']) ?> · <?= e($equipmentBooking['start_time']) ?>–<?= e($equipmentBooking['end_time']) ?></td>
              <td><?= (int) $equipmentBooking['quantity'] ?></td>
              <td><?= e($equipmentBooking['purpose']) ?></td>
              <td><?= e(strtoupper($equipmentBooking['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">No equipment booking requests yet.</p>
  <?php endif; ?>

  <div class="section-head" style="margin-top: 40px">
    <div>
      <span class="eyebrow">MY MATCHES</span>
      <h2>Event registrations.</h2>
    </div>

    <a class="text-link" href="fixtures.php">Browse fixtures ↗</a>
  </div>

  <?php if (count($eventRegistrations) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>EVENT</th>
            <th>DATE &amp; TIME</th>
            <th>VENUE</th>
            <th>STATUS</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($eventRegistrations as $registration): ?>
            <tr>
              <td><?= e($registration['title']) ?></td>
              <td>
                <?= date('d M Y', strtotime($registration['event_date'])) ?> ·
                <?= date('h:i A', strtotime($registration['event_time'])) ?>
              </td>
              <td><?= e($registration['venue']) ?></td>
              <td><?= e(strtoupper($registration['registration_status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="muted">You have not registered for an event yet.</p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>