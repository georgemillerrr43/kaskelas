        </main>

        <footer class="pub-footer">
            <span>
                &copy; <?= date('Y') ?> <strong>Uangkas Kelas</strong>. All rights reserved.
            </span>
            <span>
                Informasi keuangan transparan &mdash; Developed by <strong style="color:var(--primary-600)">Joji</strong>
            </span>
            <div style="display:flex;gap:14px;font-size:11px;color:var(--text-muted);width:100%;justify-content:center;border-top:1px solid var(--border);padding-top:10px;margin-top:6px">
                <a href="index.php" style="color:var(--text-muted);text-decoration:none">Beranda</a>
                <a href="public-rekap.php" style="color:var(--text-muted);text-decoration:none">Rekap</a>
                <a href="public-riwayat.php" style="color:var(--text-muted);text-decoration:none">Riwayat</a>
                <a href="login.php" style="color:var(--primary-500);text-decoration:none;font-weight:600">Login Bendahara</a>
            </div>
        </footer>

        <script>
        (function() {
            /* ── Theme ────────────────────────────────── */
            const html = document.documentElement;
            const btn = document.getElementById('themeToggle');
            const icon = document.getElementById('themeIcon');
            const stored = localStorage.getItem('theme');

            function setTheme(theme) {
                html.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                if (icon) {
                    icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
                }
            }

            if (stored) {
                setTheme(stored);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                setTheme('dark');
            }

            if (btn) {
                btn.addEventListener('click', function() {
                    const current = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    setTheme(current);
                });
            }

            /* ── Tabs ─────────────────────────────────── */
            const tabBtns = document.querySelectorAll('.tab-btn');
            const panes = document.querySelectorAll('.tab-pane');

            tabBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const target = btn.getAttribute('data-tab');
                    tabBtns.forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    panes.forEach(function(p) { p.classList.remove('active'); });
                    const pane = document.getElementById('pane-' + target);
                    if (pane) pane.classList.add('active');
                });
            });

            /* ── Filter select auto-submit ────────────── */
            const filterForm = document.getElementById('filterForm');
            const selects = document.querySelectorAll('.filter-select');
            if (filterForm && selects.length) {
                selects.forEach(function(s) {
                    s.addEventListener('change', function() { filterForm.submit(); });
                });
            }

            /* ── Print ────────────────────────────────── */
            const printBtn = document.getElementById('printBtn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    /* Ensure the active tab is visible for print */
                    window.print();
                });
            }

            /* ── Search ───────────────────────────────── */
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const q = searchInput.value.toLowerCase().trim();
                    document.querySelectorAll('.searchable-row').forEach(function(row) {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(q) > -1 ? '' : 'none';
                    });
                });
            }
        })();

        /* ── Mobile Drawer Toggle ────────────── */
        var h=document.getElementById('pubHamburger'),d=document.getElementById('pubMobileDrawer'),o=document.getElementById('pubDrawerOverlay');
        function op(){if(d)d.classList.add('open');if(o)o.classList.add('open');document.body.style.overflow='hidden';}
        function cl(){if(d)d.classList.remove('open');if(o)o.classList.remove('open');document.body.style.overflow='';}
        if(h)h.addEventListener('click',op);if(o)o.addEventListener('click',cl);
        </script>
    </body>
    </html>
