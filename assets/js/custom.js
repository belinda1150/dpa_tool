/**
 * DPA Tool - Custom JavaScript
 * Bootstrap 5 compatible
 */

(function ($) {
    "use strict";

    $(document).ready(function () {
        // Responsive sidebar handling
        $(window).on("load resize", function () {
            if ($(this).width() < 768) {
                $('div.sidebar-collapse').addClass('d-none-mobile');
            } else {
                $('div.sidebar-collapse').removeClass('d-none-mobile');
            }
        });
    });

}(jQuery));
