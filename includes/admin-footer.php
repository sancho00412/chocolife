<?php
// Closes the admin layout and loads the scripts.
// The page sets: $pageScripts
?>
        </main>

        <footer class="admin-footer">
            © 2026 Chocolife Admin Panel
        </footer>
    </div>
</div>

<script src="common.js"></script>
<script src="admin.js"></script>
<?php foreach ($pageScripts as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>
