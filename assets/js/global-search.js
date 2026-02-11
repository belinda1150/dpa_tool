/**
 * DPA Tool - Global Search
 * AJAX-powered cross-module search with debouncing
 */
(function() {
    var searchInput = document.getElementById('globalSearchInput');
    var searchResults = document.getElementById('searchResults');
    var debounceTimer = null;
    var currentXHR = null;

    if (!searchInput || !searchResults) return;

    var moduleColors = {
        'ROPA': '#667eea',
        'DPIA': '#11998e',
        'Incidents': '#eb3349',
        'DSR': '#7f00ff',
        'Vendors': '#fd7e14',
        'Policies': '#f093fb',
        'Documents': '#3498db',
        'Risks': '#f5576c',
        'Consents': '#02aab0',
        'Cross-Border': '#20c997'
    };

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchResults.innerHTML = '<div class="search-loading"><i class="fa fa-spinner fa-spin"></i> Searching...</div>';
        searchResults.style.display = 'block';

        debounceTimer = setTimeout(function() {
            if (currentXHR) currentXHR.abort();

            currentXHR = new XMLHttpRequest();
            currentXHR.open('GET', 'api/search.php?q=' + encodeURIComponent(query));
            currentXHR.onload = function() {
                if (this.status === 200) {
                    try {
                        var data = JSON.parse(this.responseText);
                        renderResults(data.results, query);
                    } catch (e) {
                        searchResults.innerHTML = '<div class="search-no-results">Search error</div>';
                    }
                }
            };
            currentXHR.onerror = function() {
                searchResults.innerHTML = '<div class="search-no-results">Search unavailable</div>';
            };
            currentXHR.send();
        }, 300);
    });

    function renderResults(results, query) {
        if (!results || results.length === 0) {
            searchResults.innerHTML = '<div class="search-no-results">' +
                '<i class="fa fa-search" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>' +
                'No results for "' + escapeHtml(query) + '"</div>';
            return;
        }

        var grouped = {};
        results.forEach(function(r) {
            if (!grouped[r.module]) grouped[r.module] = [];
            grouped[r.module].push(r);
        });

        var html = '';
        for (var module in grouped) {
            html += '<div class="search-module-header">' + escapeHtml(module) + '</div>';
            grouped[module].forEach(function(r) {
                var color = moduleColors[r.module] || '#6c757d';
                html += '<a href="' + escapeHtml(r.url) + '" class="search-result-item">';
                html += '<div class="search-result-icon" style="background:' + color + '">';
                html += '<i class="fa ' + escapeHtml(r.icon) + '"></i></div>';
                html += '<div class="search-result-info">';
                html += '<div class="search-result-title">' + highlightMatch(escapeHtml(r.title || 'Untitled'), query) + '</div>';
                html += '<div class="search-result-module">' + escapeHtml(r.module) + '</div>';
                html += '</div>';
                if (r.status) {
                    html += '<span class="search-result-status badge bg-secondary">' + escapeHtml(r.status) + '</span>';
                }
                html += '</a>';
            });
        }

        searchResults.innerHTML = html;
    }

    function highlightMatch(text, query) {
        var re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(re, '<strong>$1</strong>');
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Close on Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });

    // Keyboard shortcut: Ctrl+K to focus search
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'SELECT')) {
            e.preventDefault();
            searchInput.focus();
        }
    });
})();
