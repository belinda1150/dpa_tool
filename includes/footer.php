<?php
/**
 * DPA Tool - Shared Footer Scripts
 * Accepts: $extra_js (array of paths), $load_dataTables (bool), $load_echarts (bool)
 */
?>
<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap5.bundle.min.js"></script>
<script src="assets/js/sidebar-menu.js"></script>
<?php if (!empty($load_dataTables)): ?>
<script src="assets/js/dataTables/jquery.dataTables.js"></script>
<script src="assets/js/dataTables/dataTables.bootstrap5.js"></script>
<?php endif; ?>
<?php if (!empty($load_echarts)): ?>
<script src="assets/js/echarts.min.js"></script>
<?php endif; ?>
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
<?php if (!empty($extra_js)): ?>
<?php foreach ($extra_js as $js): ?>
<script src="<?php echo $js; ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
