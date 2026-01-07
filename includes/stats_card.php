<?php
/**
 * DPA Tool - Reusable Stats Card Component
 * Version: 1.0
 * Date: January 2026
 *
 * Usage:
 * include 'includes/stats_card.php';
 * render_stats_card([
 *     'value' => '125',
 *     'label' => 'Total Records',
 *     'sublabel' => '15 New This Month',
 *     'icon' => 'fa-list-alt',
 *     'color' => 'blue',        // blue, green, red, brown, purple
 *     'link' => 'records.php',  // optional
 *     'col_class' => 'col-md-3' // optional, default: col-md-3
 * ]);
 */

if (!function_exists('render_stats_card')) {
    function render_stats_card($options = []) {
        // Default values
        $defaults = [
            'value' => '0',
            'label' => 'Stat Label',
            'sublabel' => '',
            'icon' => 'fa-chart-bar',
            'color' => 'blue',
            'link' => '',
            'col_class' => 'col-md-3',
            'badge' => '',
            'badge_color' => 'danger'
        ];

        $card = array_merge($defaults, $options);

        // Color mapping
        $color_classes = [
            'blue' => 'bg-color-blue',
            'green' => 'bg-color-green',
            'red' => 'bg-color-red',
            'brown' => 'bg-color-brown',
            'purple' => 'icon-box-purple',
            'orange' => 'bg-color-brown',
            'teal' => 'icon-box-green'
        ];

        $color_class = $color_classes[$card['color']] ?? 'bg-color-blue';

        // Start output
        ?>
        <div class="<?php echo htmlspecialchars($card['col_class']); ?> col-sm-6 col-xs-12">
            <?php if (!empty($card['link'])): ?>
            <a href="<?php echo htmlspecialchars($card['link']); ?>" style="text-decoration: none; color: inherit;">
            <?php endif; ?>

            <div class="panel panel-back noti-box <?php echo !empty($card['link']) ? 'stats-card-hover' : ''; ?>">
                <span class="icon-box <?php echo $color_class; ?> set-icon">
                    <i class="fa <?php echo htmlspecialchars($card['icon']); ?>"></i>
                </span>
                <div class="text-box">
                    <p class="main-text">
                        <?php echo htmlspecialchars($card['label']); ?>
                        <?php if (!empty($card['badge'])): ?>
                            <span class="badge badge-<?php echo htmlspecialchars($card['badge_color']); ?>" style="font-size: 11px; margin-left: 8px; vertical-align: middle;">
                                <?php echo htmlspecialchars($card['badge']); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <p class="text-muted" style="font-size: 20px; font-weight: 700; color: #2c3e50 !important; margin: 0;">
                        <?php echo htmlspecialchars($card['value']); ?>
                    </p>
                    <?php if (!empty($card['sublabel'])): ?>
                        <p class="text-muted" style="font-size: 12px; margin-top: 5px;">
                            <?php echo $card['sublabel']; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($card['link'])): ?>
            </a>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('render_stats_row')) {
    /**
     * Render multiple stats cards in a row
     *
     * Usage:
     * render_stats_row([
     *     ['value' => '125', 'label' => 'Total', 'icon' => 'fa-list', 'color' => 'blue'],
     *     ['value' => '45', 'label' => 'Pending', 'icon' => 'fa-clock', 'color' => 'brown'],
     *     ['value' => '80', 'label' => 'Completed', 'icon' => 'fa-check', 'color' => 'green'],
     *     ['value' => '5', 'label' => 'Failed', 'icon' => 'fa-times', 'color' => 'red']
     * ]);
     */
    function render_stats_row($cards = []) {
        if (empty($cards)) {
            return;
        }

        echo '<div class="row">';
        foreach ($cards as $card) {
            render_stats_card($card);
        }
        echo '</div>';
    }
}

if (!function_exists('render_info_card')) {
    /**
     * Render an informational card with custom content
     *
     * Usage:
     * render_info_card([
     *     'title' => 'Compliance Status',
     *     'content' => '<p>Your compliance score is good!</p>',
     *     'icon' => 'fa-info-circle',
     *     'color' => 'info',  // info, success, warning, danger, primary
     *     'col_class' => 'col-md-6'
     * ]);
     */
    function render_info_card($options = []) {
        $defaults = [
            'title' => 'Information',
            'content' => '',
            'icon' => 'fa-info-circle',
            'color' => 'info',
            'col_class' => 'col-md-12',
            'footer' => ''
        ];

        $card = array_merge($defaults, $options);

        ?>
        <div class="<?php echo htmlspecialchars($card['col_class']); ?>">
            <div class="panel panel-<?php echo htmlspecialchars($card['color']); ?>">
                <div class="panel-heading">
                    <i class="fa <?php echo htmlspecialchars($card['icon']); ?>"></i>
                    <?php echo htmlspecialchars($card['title']); ?>
                </div>
                <div class="panel-body">
                    <?php echo $card['content']; ?>
                </div>
                <?php if (!empty($card['footer'])): ?>
                <div class="panel-footer">
                    <?php echo $card['footer']; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
