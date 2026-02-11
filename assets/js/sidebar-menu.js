/**
 * Sidebar Menu - Bootstrap 5 compatible replacement for MetisMenu
 * Handles accordion toggle, active state, and CSS transitions
 */
document.addEventListener('DOMContentLoaded', function() {
    var mainMenu = document.getElementById('main-menu');
    if (!mainMenu) return;

    // Show sub-menus for active items
    mainMenu.querySelectorAll('li.active > ul').forEach(function(ul) {
        ul.style.display = 'block';
    });

    // Hide inactive sub-menus
    mainMenu.querySelectorAll('li:not(.active) > ul').forEach(function(ul) {
        ul.style.display = 'none';
    });

    // Click handlers for parent items with sub-menus
    mainMenu.querySelectorAll('li').forEach(function(li) {
        var subMenu = li.querySelector(':scope > ul');
        if (!subMenu) return;

        var link = li.querySelector(':scope > a');
        if (!link) return;

        link.addEventListener('click', function(e) {
            e.preventDefault();
            var isOpen = li.classList.contains('active');

            // Collapse siblings (accordion behavior)
            var siblings = li.parentElement.querySelectorAll(':scope > li.active');
            siblings.forEach(function(sibling) {
                if (sibling !== li) {
                    sibling.classList.remove('active');
                    var sibSub = sibling.querySelector(':scope > ul');
                    if (sibSub) {
                        sibSub.style.display = 'none';
                    }
                }
            });

            // Toggle current
            if (isOpen) {
                li.classList.remove('active');
                subMenu.style.display = 'none';
            } else {
                li.classList.add('active');
                subMenu.style.display = 'block';
            }
        });
    });
});
