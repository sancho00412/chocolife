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
        </nav>

        <p>© 2026 Chocolife. All rights reserved.</p>
    </footer>

    <script src="common.js"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>
