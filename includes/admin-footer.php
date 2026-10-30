<?php
// Closes the admin layout and loads the scripts.
// The page sets: $pageScripts
?>
        </main>

    </div>
</div>

<script src="<?= asset('common.js') ?>"></script>
<script src="<?= asset('admin.js') ?>"></script>
<?php foreach ($pageScripts as $script): ?>
<script src="<?= e(asset($script)) ?>"></script>
<?php endforeach; ?>

</body>
</html>
