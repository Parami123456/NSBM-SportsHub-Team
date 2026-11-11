<?php
require 'includes/db.php';

$pageTitle = 'Sports Clubs';
$search = trim($_GET['q'] ?? '');
$searchTerm = '%' . $search . '%';

$sql = '
    SELECT
        clubs.*,
        COUNT(memberships.id) AS member_count
    FROM clubs
    LEFT JOIN memberships
        ON memberships.club_id = clubs.id
        AND memberships.status = "approved"
    WHERE clubs.name LIKE ?
       OR clubs.description LIKE ?
    GROUP BY clubs.id
    ORDER BY clubs.name
';

$stmt = $pdo->prepare($sql);
$stmt->execute([$searchTerm, $searchTerm]);
$clubs = $stmt->fetchAll();

require 'includes/header.php';
?>
<script>
  document.body.classList.add('clubs-page');
</script>

<section class="page-hero">
  <span class="eyebrow">FIND YOUR PEOPLE</span>
  <h1>Pick your team.</h1>
  <p>
    From first-timers to seasoned competitors, there’s a place
    for every athlete at NSBM. Find your sport and join the community.
  </p>
</section>

<div class="page-wrap">
  <form class="toolbar" method="get" action="clubs.php">
    <input
      class="field"
      type="search"
      name="q"
      placeholder="Search clubs or sports…"
      value="<?= e($search) ?>"
    >
    <button class="button" type="submit">Search clubs</button>
  </form>

  <div class="grid">
    <?php foreach ($clubs as $club): ?>
      <article class="panel">
        <div class="row">
          <span class="tag"><?= e($club['category']) ?></span>
          <span style="font-size: 34px"><?= e($club['emoji']) ?></span>
        </div>

        <h3><?= e($club['name']) ?></h3>
        <p class="muted"><?= e($club['description']) ?></p>

        <div class="row">
          <span class="muted">
            <?= (int) $club['member_count'] ?> active members
          </span>

          <a
            class="text-link"
            href="club.php?id=<?= (int) $club['id'] ?>"
          >
            Club details ↗
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if (count($clubs) === 0): ?>
    <p class="muted">No clubs match that search.</p>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
