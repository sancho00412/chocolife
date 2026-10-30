<?php
require __DIR__ . '/includes/page.php';

$user = requirePageLogin();

$stmt = getDb()->prepare('SELECT phone, created_at FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$details = $stmt->fetch();

$isCustomer = $user['role'] === 'user';

$pageTitle   = 'My Profile';
$activePage  = 'profile';
$pageCss     = ['user-home.css', 'profile.css'];
$pageScripts = ['profile.js'];

require __DIR__ . '/includes/header.php';
?>

    <main class="profile-page">
        <section class="profile-heading">
            <p class="eyebrow">YOUR ACCOUNT</p>
            <h1>My Profile</h1>
            <p>Manage your personal information<?= $isCustomer ? ', orders and saved offers' : '' ?>.</p>
        </section>

        <div class="profile-layout">

            <section class="profile-card">
                <div class="profile-avatar">
                    <span class="material-symbols-outlined">person</span>
                </div>

                <h2 id="profile-display-name"><?= e($user['firstName'] . ' ' . $user['lastName']) ?></h2>
                <p id="profile-display-email"><?= e($user['email']) ?></p>
                <p class="profile-since">Member since <?= formatDate($details['created_at'], 'F Y') ?></p>

                <div class="profile-divider"></div>

                <h3>Personal Information</h3>

                <form id="profile-form" novalidate>
                    <div class="profile-field">
                        <label for="profile-first-name">First Name</label>
                        <input id="profile-first-name" type="text" required minlength="2" maxlength="50"
                            value="<?= e($user['firstName']) ?>">
                    </div>

                    <div class="profile-field">
                        <label for="profile-last-name">Last Name</label>
                        <input id="profile-last-name" type="text" required minlength="2" maxlength="50"
                            value="<?= e($user['lastName']) ?>">
                    </div>

                    <div class="profile-field">
                        <label for="profile-phone">Phone</label>
                        <input id="profile-phone" type="tel" maxlength="20" placeholder="+7 700 123 45 67"
                            value="<?= e($details['phone']) ?>">
                    </div>

                    <div class="profile-field">
                        <label for="profile-email">Email Address</label>
                        <input id="profile-email" type="email" disabled value="<?= e($user['email']) ?>">
                        <small>Email cannot be changed.</small>
                    </div>

                    <p id="profile-message" class="form-status" role="status"></p>

                    <button class="profile-save-button" type="submit">Save Changes</button>
                </form>

                <div class="profile-divider"></div>

                <h3>Change Password</h3>

                <form id="password-form" novalidate>
                    <div class="profile-field">
                        <label for="current-password">Current Password</label>
                        <input id="current-password" type="password" autocomplete="current-password" required>
                    </div>

                    <div class="profile-field">
                        <label for="new-password">New Password</label>
                        <input id="new-password" type="password" autocomplete="new-password"
                            minlength="8" placeholder="At least 8 characters" required>
                    </div>

                    <div class="profile-field">
                        <label for="confirm-new-password">Repeat New Password</label>
                        <input id="confirm-new-password" type="password" autocomplete="new-password" required>
                    </div>

                    <p id="password-message" class="form-status" role="status"></p>

                    <button class="profile-save-button" type="submit">Change Password</button>
                </form>
            </section>

<?php if ($isCustomer): ?>
            <div class="profile-side">
                <section class="profile-orders-card" id="orders">
                    <div class="profile-orders-heading">
                        <div>
                            <p class="eyebrow">PURCHASES</p>
                            <h2>My Orders</h2>
                        </div>
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>

                    <p id="orders-status" class="page-status" role="status">Loading orders...</p>
                    <div id="profile-orders"></div>
                </section>

                <section class="profile-orders-card">
                    <div class="profile-orders-heading">
                        <div>
                            <p class="eyebrow">WISHLIST</p>
                            <h2>Saved Offers</h2>
                        </div>
                        <span class="material-symbols-outlined">favorite</span>
                    </div>

                    <p id="favorites-status" class="page-status" role="status">Loading saved offers...</p>
                    <ul id="profile-favorites" class="profile-favorites"></ul>
                    <a href="favorites.php" class="profile-all-link">Open saved offers →</a>
                </section>
            </div>
<?php else: ?>
            <section class="profile-orders-card">
                <h2>Administrator</h2>
                <p class="profile-orders-description">
                    Orders, offers and users are managed in the
                    <a href="admin.php">admin panel</a>.
                </p>
            </section>
<?php endif; ?>
        </div>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
