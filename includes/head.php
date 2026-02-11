<?php
/**
 * DPA Tool - Shared Head Section
 * Accepts: $page_title (string), $extra_css (array of paths), $load_dataTables (bool)
 */
?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo APP_NAME; ?> - <?php echo $page_title ?? 'Dashboard'; ?></title>
<script src="assets/js/theme.js"></script>
<link href="assets/css/theme-variables.css" rel="stylesheet" />
<link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
<link href="assets/css/css/all.min.css" rel="stylesheet" />
<link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
<?php if (!empty($load_dataTables)): ?>
<link href="assets/js/dataTables/dataTables.bootstrap5.css" rel="stylesheet" />
<?php endif; ?>
<link href="assets/css/custom.css" rel="stylesheet" />
<?php if (!empty($extra_css)): ?>
<?php foreach ($extra_css as $css): ?>
<link href="<?php echo $css; ?>" rel="stylesheet" />
<?php endforeach; ?>
<?php endif; ?>
