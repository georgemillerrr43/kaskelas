        </div><!-- end .page-content -->

        <!-- Footer -->
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <strong>Uangkas Kelas</strong>. All rights reserved.</span>
            <span>Panel <strong style="color:var(--primary-600)">Bendahara</strong> &mdash; Developed by <strong style="color:var(--primary-400)">Joji</strong></span>
            <div style="display:flex;gap:14px;font-size:11px;color:var(--text-muted);width:100%;justify-content:center;border-top:1px solid var(--border);padding-top:10px;margin-top:6px">
                <a href="dashboard.php" style="color:var(--text-muted);text-decoration:none">Dashboard</a>
                <a href="transaksi.php" style="color:var(--text-muted);text-decoration:none">Transaksi</a>
                <a href="rekap.php" style="color:var(--text-muted);text-decoration:none">Rekap</a>
                <a href="anggota.php" style="color:var(--text-muted);text-decoration:none">Anggota</a>
            </div>
        </footer>
    </div>

    <script>
    (function() {
        /* ── Theme ── */
        const html = document.documentElement;
        const btn = document.getElementById('themeBtn');
        const icon = document.getElementById('themeIcon');
        const stored = localStorage.getItem('theme');

        function setTheme(t) {
            html.setAttribute('data-theme', t);
            localStorage.setItem('theme', t);
            if (icon) icon.className = t === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        if (stored) setTheme(stored);
        else if (window.matchMedia('(prefers-color-scheme: dark)').matches) setTheme('dark');
        if (btn) btn.addEventListener('click', function() {
            setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });

        /* ── Mobile Drawer ── */
        const toggle = document.getElementById('drawerToggle');
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        function openDrawer() { drawer.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow = 'hidden'; }
        function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }
        if (toggle) toggle.addEventListener('click', openDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);

        /* ── Tabs ── */
        document.querySelectorAll('.tab-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const target = btn.getAttribute('data-tab');
                document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove('active'); });
                const pane = document.getElementById('pane-' + target);
                if (pane) pane.classList.add('active');
            });
        });

        /* ── Filter select auto-submit ── */
        const ff = document.getElementById('filterForm');
        if (ff) ff.querySelectorAll('.filter-select').forEach(function(s) {
            s.addEventListener('change', function() { ff.submit(); });
        });

        /* ── Search ── */
        const si = document.getElementById('searchInput');
        if (si) si.addEventListener('input', function() {
            const q = si.value.toLowerCase().trim();
            document.querySelectorAll('.searchable-row').forEach(function(r) {
                r.style.display = r.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
            });
        });
    })();
    </script>
</body>
</html>
