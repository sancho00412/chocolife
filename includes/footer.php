<?php
// Shared footer and scripts for user pages.
// The page sets: $pageScripts
?>
    <footer class="user-footer">
        <a href="user-home.php" class="brand brand-logo">
            <span class="brand-text">chocolife</span>
            <span class="brand-heart">.me</span>
        </a>

        <nav class="footer-nav" aria-label="Footer navigation">
            <a href="user-home.php#offers">Offers</a>
<?php if ($user['role'] === 'user'): ?>
            <a href="cart.php">Cart</a>
<?php endif; ?>
            <a href="profile.php">My Profile</a>
            <a href="about.php">About us</a>
        </nav>

        <p>Support: <b><?= SUPPORT_PHONE ?></b></p>
    </footer>

    <script src="<?= asset('common.js') ?>"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>

</body>
</html>
