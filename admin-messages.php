<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();

$status = in_array($_GET['status'] ?? '', ['New', 'Answered'], true) ? $_GET['status'] : '';

$pageTitle   = 'Messages';
$activePage  = 'messages';
$pageScripts = ['admin-messages.js'];

require __DIR__ . '/includes/admin-header.php';
?>

<section class="admin-card">
    <form id="messages-filter" class="filter-bar">
        <label>
            <span>Type</span>
            <select id="messages-type">
                <option value="">All messages</option>
                <option value="feedback">Feedback</option>
                <option value="business">Partner requests</option>
            </select>
        </label>

        <label>
            <span>Status</span>
            <select id="messages-status">
                <option value="">All statuses</option>
                <option value="New" <?= $status === 'New' ? 'selected' : '' ?>>New</option>
                <option value="Answered" <?= $status === 'Answered' ? 'selected' : '' ?>>Answered</option>
            </select>
        </label>
    </form>

    <p id="messages-summary" class="text-muted" role="status">Loading messages...</p>
    <div id="messages-list" class="messages-list"></div>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
