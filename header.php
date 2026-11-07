<?php
require_once __DIR__ . '/db.php';

$pageTitle = $pageTitle ?? 'NSBM SportsHub';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | SportsHub</title>
  <link rel="stylesheet" href="assets/style.css?v=20261031-club-cards">
</head>
<body>

  <div class="ticker">
    <span>● CAMPUS SPORTS UPDATE</span>
    Inter-Faculty Cricket Championship 2026 registrations are open
    <a href="fixtures.php">Register by Nov 15 ↗</a>
  </div>

  <header class="site-head">
    <a class="brand" href="index.php">
      <img class="campus-logo" src="assets/logo.png" alt="NSBM Sports Club logo">
      <span>
        <b>NSBM SportsHub</b>
        <small>YOUR CAMPUS. YOUR GAME.</small>
      </span>
    </a>

    <div class="head-actions">
      <?php if (!empty($_SESSION['user'])): ?>

        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
          <a href="admin.php">Admin</a>
        <?php endif; ?>

        <a href="dashboard.php">
          Hi, <?= e($_SESSION['user']['name']) ?>
        </a>
        <a class="button dark small" href="logout.php">Sign out ↗</a>

      <?php else: ?>

        <a href="login.php">Sign in</a>
        <a class="button dark small" href="register.php">Join the Hub ↗</a>

      <?php endif; ?>
    </div>

    <nav>
      <a href="index.php">Home</a>
      <a href="clubs.php">Sports Clubs</a>
      <a href="facilities.php">Facilities</a>
      <a href="fixtures.php">Fixtures</a>
      <a href="schedule.php">Training</a>
    </nav>
  </header>

  <main>
