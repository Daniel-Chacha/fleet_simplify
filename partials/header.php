<?php
// Expects: $page_title, optional $extra_css (array of paths), optional $body_data (array of key=>value)
$page_title  = $page_title  ?? 'FleetSimplify';
$extra_css   = $extra_css   ?? [];
$body_data   = $body_data   ?? [];

// Pick up flash messages and forward to <body data-flash-*> for main.js auto-toast.
foreach (['success','error','info'] as $k) {
    $msg = get_flash($k);
    if ($msg) $body_data['flash-' . $k] = $msg;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — FleetSimplify</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/main.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
<?php foreach ($extra_css as $css): ?>
<link rel="stylesheet" href="<?= e(url($css)) ?>">
<?php endforeach; ?>
</head>
<body<?php foreach ($body_data as $k => $v): ?> data-<?= e($k) ?>="<?= e($v) ?>"<?php endforeach; ?>>
<div id="toast-root"></div>
