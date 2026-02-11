/**
 * DPA Tool - Dark Mode Theme Toggle
 * Must be loaded in <head> to prevent FOUC (Flash of Unstyled Content)
 */
(function() {
    var STORAGE_KEY = 'bantu-dpo-theme';

    function getPreferredTheme() {
        var stored = localStorage.getItem(STORAGE_KEY);
        if (stored) return stored;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);

        var icon = document.getElementById('themeIcon');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'fa fa-sun-o';
            } else {
                icon.className = 'fa fa-moon-o';
            }
        }

        // Notify ECharts to update if charts exist
        if (window.updateChartsTheme) {
            window.updateChartsTheme(theme);
        }
    }

    // Apply theme immediately to prevent flash
    var initialTheme = getPreferredTheme();
    document.documentElement.setAttribute('data-theme', initialTheme);

    // Set up toggle after DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        // Update icon for initial state
        var icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = initialTheme === 'dark' ? 'fa fa-sun-o' : 'fa fa-moon-o';
        }

        var toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                var current = document.documentElement.getAttribute('data-theme') || 'light';
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        }
    });

    // Export for external use
    window.setTheme = setTheme;
})();
