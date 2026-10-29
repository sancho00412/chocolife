<?php
require __DIR__ . '/includes/page.php';

$user = requirePageAdmin();

$pageTitle   = 'Users';
$activePage  = 'users';
$pageScripts = ['admin-users.js'];

require __DIR__ . '/includes/admin-header.php';
?>

<section class="admin-card">
    <form id="users-filter" class="filter-bar" role="search">
        <label>
            <span>Search</span>
            <input type="search" id="users-search" placeholder="Name or email">
        </label>

        <label>
            <span>Role</span>
            <select id="users-role">
                <option value="">All roles</option>
                <option value="user">Customers</option>
                <option value="admin">Admins</option>
            </select>
        </label>

        <label>
            <span>Sort</span>
            <select id="users-sort">
                <option value="newest">Newest first</option>
                <option value="name">Name A-Z</option>
                <option value="spent">Spent the most</option>
                <option value="orders">Most orders</option>
            </select>
        </label>
    </form>

    <p id="users-summary" class="text-muted" role="status">Loading users...</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Orders</th>
                    <th>Spent</th>
                    <th>Last order</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="users-body"></tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
