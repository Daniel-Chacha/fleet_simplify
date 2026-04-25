<?php
// Expects optional $extra_js (array of paths) and optional $inline_js (string).
$extra_js = $extra_js ?? [];
$inline_js = $inline_js ?? '';
?>
<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
<?php foreach ($extra_js as $js): ?>
<script src="<?= e(url($js)) ?>" defer></script>
<?php endforeach; ?>
<?php if ($inline_js): ?>
<script><?= $inline_js // already trusted server-rendered config ?></script>
<?php endif; ?>
</body>
</html>
