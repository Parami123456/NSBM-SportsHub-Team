<?php
$pageTitle = 'Home';
require 'includes/header.php';

$clubs = $pdo->query(
    'SELECT * FROM clubs ORDER BY name LIMIT 3'
)->fetchAll();

$events = $pdo->query(
    'SELECT * FROM events
     WHERE event_date >= CURDATE()
     ORDER BY event_date
     LIMIT 3'
)->fetchAll();
?>

<section class="hero">
  <div class="hero-copy">
    <span class="tag">✦ NSBM GREEN UNIVERSITY SPORTS</span>

    <h1>
      MAKE YOUR<br>
      <em>MOVE.</em> OWN<br>
      THE MOMENT.
    </h1>

    <p>
      Find your team, book your ground, and bring your competitive
      spirit to life. Your next big moment starts here.
    </p>

    <div class="hero-actions">
      <a class="button" href="clubs.php">Explore sports ↗</a>
      <a class="button dark" href="fixtures.php">
        ◉ &nbsp; Upcoming matches
      </a>
    </div>

    <p class="eyebrow">— BUILT FOR THE GAME. MADE FOR NSBM.</p>
  </div>

  <div class="hero-card">
    <div class="hero-card-top">
      <small>SEASON 2026</small>
      <small>● IN PLAY</small>
    </div>

    <div class="hero-art">
      <span class="court-mark" aria-hidden="true">0</span>
      <span class="play-sticker">#PLAYYOURWAY</span>

      <span class="court-label">
        FIND YOUR<br>
        <b>GAME FACE.</b>
      </span>
    </div>

    <div class="hero-card-bottom">
      <div>
        <small>THE CAMPUS SPORTS COMMUNITY</small>
        <b>One campus. Many champions.</b>
      </div>
      <a class="round-arrow" href="#clubs" aria-label="Scroll to sports clubs">↗</a>
    </div>
  </div>
</section>

<section class="stats">
  <div class="stat">
    <strong>
      <?= $pdo->query('SELECT COUNT(*) FROM clubs')->fetchColumn() ?>+
    </strong>
    <span>SPORTS CLUBS</span>
  </div>

  <div class="stat">
    <strong>
      <?= $pdo->query(
          "SELECT COUNT(*) FROM memberships WHERE status = 'approved'"
      )->fetchColumn() ?>+
    </strong>
    <span>STUDENT ATHLETES</span>
  </div>

  <div class="stat">
    <strong>
      <?= $pdo->query('SELECT COUNT(*) FROM facilities')->fetchColumn() ?>+
    </strong>
    <span>SPORTS VENUES</span>
  </div>

  <div class="stat">
    <strong>
      <?= $pdo->query(
          "SELECT COUNT(*) FROM events
           WHERE MONTH(event_date) = MONTH(CURDATE())"
      )->fetchColumn() ?>+
    </strong>
    <span>MONTHLY MATCHES</span>
  </div>
</section>

<section class="section" id="clubs">
  <div class="section-head">
    <div>
      <span class="eyebrow">FIND YOUR PLAY</span>
      <h2>More than a sport.<br><em>It's your community.</em></h2>
    </div>

    <a class="text-link" href="clubs.php">View all clubs ↗</a>
  </div>

  <div class="cards">
    <?php foreach ($clubs as $club): ?>
      <a
        class="club-card"
        href="club.php?id=<?= (int) $club['id'] ?>"
      >
        <small><?= e($club['tagline']) ?></small>
        <span class="emoji"><?= e($club['emoji']) ?></span>

        <div>
          <h3><?= e($club['name']) ?></h3>
          <p><?= e($club['description']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section schedule-section">
  <div class="section-head">
    <div>
      <span class="eyebrow">SAVE THE DATE</span>
      <h2>Next up <em>on the field.</em></h2>
    </div>

    <a class="text-link" href="fixtures.php">Full schedule ↗</a>
  </div>

  <div class="event-list">
    <?php foreach ($events as $event): ?>
    <?php 
        $eventEmoji = '🏆';
        $titleLower = strtolower($event['title']);
        
        if (strpos($titleLower, 'cricket') !== false) {
            $eventEmoji = '🏏';
        } elseif (strpos($titleLower, 'basketball') !== false) {
            $eventEmoji = '🏀';
        } elseif (strpos($titleLower, 'football') !== false) {
            $eventEmoji = '⚽';
        } elseif (strpos($titleLower, 'badminton') !== false) {
            $eventEmoji = '🏸';
        } elseif (strpos($titleLower, 'swimming') !== false) {
            $eventEmoji = '🏊';
        }
      ?>
    
      <a class="event" href="fixtures.php">
        <div class="date">
          <?= date('d', strtotime($event['event_date'])) ?>
          <small><?= strtoupper(date('M', strtotime($event['event_date']))) ?></small>
        </div>

        <span class="event-icon"><?= $eventEmoji ?></span>

        <div>
          <b><?= e($event['title']) ?></b>
          <p><?= e($event['subtitle']) ?></p>
        </div>

        <time>
          <?= date('h:i A', strtotime($event['event_time'])) ?>
          <p><?= e($event['venue']) ?></p>
        </time>

        <span class="event-arrow">↗</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta">
  <div>
    <span class="eyebrow">YOUR NEXT SESSION STARTS HERE</span>
    <h2>Got a game plan?</h2>
    <span class="muted">
      Book your favourite campus sports facility and get your team together.
    </span>
  </div>

  <a class="button dark" href="facilities.php">Book a facility ↗</a>
</section>

<?php require 'includes/footer.php'; ?>
