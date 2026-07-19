<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Search Submissions
        </h2>
    </x-slot>

    @vite(['resources/css/search.css'])

    <div class="search-container">
        @guest
            <div class="access-message">
                <p style="font-size: 1.5rem; margin-bottom: 1rem;">ACCESS REQUIRED</p>
                <p>Please log in to search submissions.</p>
            </div>
        @endguest

        @auth
            <div class="search-bar-wrap">
                <input
                    type="text"
                    id="song-search-input"
                    class="search-input"
                    placeholder="Search by artist or song title..."
                    autocomplete="off"
                >
                <div class="search-status" id="search-status"></div>
            </div>

            <div id="search-results" class="search-results"></div>
        @endauth
    </div>

    <script>
        (function () {
            const input = document.getElementById('song-search-input');
            const resultsEl = document.getElementById('search-results');
            const statusEl = document.getElementById('search-status');
            if (!input) return;

            let debounceTimer = null;
            let currentRequest = 0;

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str ?? '';
                return div.innerHTML;
            }

            function renderResults(results) {
                if (results.length === 0) {
                    resultsEl.innerHTML = '<div class="no-results">No matching songs found.</div>';
                    return;
                }

                resultsEl.innerHTML = results.map(function (group) {
                    const entriesHtml = group.entries.map(function (e) {
                        return `
                            <div class="search-entry">
                                <span class="search-entry-round">Round ${escapeHtml(e.round)}</span>
                                <span class="search-entry-group">${escapeHtml(e.group)}</span>
                                <span class="search-entry-contestant">${escapeHtml(e.contestant)}</span>
                                ${e.url ? `<a href="${escapeHtml(e.url)}" target="_blank" class="search-entry-link">Listen ↗</a>` : ''}
                            </div>
                        `;
                    }).join('');

                    const multi = group.entries.length > 1;

                    return `
                        <div class="search-result-card">
                            <div class="search-result-header">
                                <span class="search-result-title">${escapeHtml(group.title)}</span>
                                <span class="search-result-artist">${escapeHtml(group.artist)}</span>
                                ${multi ? `<span class="search-result-count">${group.entries.length}× submitted</span>` : ''}
                            </div>
                            <div class="search-entries">${entriesHtml}</div>
                        </div>
                    `;
                }).join('');
            }

            function doSearch(q) {
                const requestId = ++currentRequest;
                statusEl.textContent = 'Searching...';

                fetch('/search/query', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ q: q })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (requestId !== currentRequest) return; // stale response
                        statusEl.textContent = '';
                        renderResults(data.results || []);
                    })
                    .catch(() => {
                        if (requestId !== currentRequest) return;
                        statusEl.textContent = 'Search failed. Try again.';
                    });
            }

            input.addEventListener('input', function () {
                const q = input.value.trim();
                clearTimeout(debounceTimer);

                if (q === '') {
                    resultsEl.innerHTML = '';
                    statusEl.textContent = '';
                    return;
                }

                debounceTimer = setTimeout(() => doSearch(q), 300);
            });
        })();
    </script>
</x-app-layout>