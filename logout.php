<?php
require 'includes/db.php';

// Clear the signed-in user's session data.
$_SESSION = [];

// End the session.
session_destroy();

// Return to the home page.
header('Location: index.php');
exit;