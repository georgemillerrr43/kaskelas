        </div><!-- end .page-content -->

        <!-- Footer -->
        <footer class="app-footer">
            <span>
                &copy; <?= date('Y') ?> <strong style="color:#475569">Uangkas Kelas</strong>. Hak Cipta Dilindungi.
            </span>
            <span>
                Dibuat oleh <strong style="color:var(--primary-600)">Joji</strong> &mdash; Transparansi keuangan kelas yang akurat &amp; aman.
            </span>
        </footer>

    </div><!-- end .main-area -->
</div><!-- end .app-shell -->

<!-- Mobile Sidebar Toggle -->
<script>
(function() {
    const btn = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const icon = document.getElementById('menuIcon');

    function open() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        if (icon) icon.classList.replace('fa-bars', 'fa-xmark');
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
    }
    function close() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        if (icon) icon.classList.replace('fa-xmark', 'fa-bars');
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
    }

    if (btn) btn.addEventListener('click', function() {
        sidebar.classList.contains('open') ? close() : open();
    });
    if (overlay) overlay.addEventListener('click', close);

    const links = sidebar ? sidebar.querySelectorAll('.sb-link, .sb-logout') : [];
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) close();
        });
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('open')) close();
    });
})();
</script>
</body>
</html>
