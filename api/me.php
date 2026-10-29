<?php
// GET -> returns the logged-in user or null

require __DIR__ . '/../includes/api.php';

requireMethod('GET');

sendJson(['success' => true, 'user' => currentUser()]);
