<?php
// POST -> ends the session

require __DIR__ . '/../includes/api.php';

requireMethod('POST');
logoutUser();

sendJson(['success' => true]);
