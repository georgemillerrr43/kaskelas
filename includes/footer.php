        </div><!-- end .page-content -->

        <!-- Global Footer -->
        <footer class="app-footer">
            <span>
                &copy; <?= date('Y') ?> <strong style="color:#64748b">Uangkas Kelas</strong>. Hak Cipta Dilindungi.
            </span>
            <span>
                Dibuat oleh <strong style="color:#4f46e5">Joji</strong> &mdash; Transparansi keuangan kelas yang akurat &amp; aman.
            </span>
        </footer>

    </div><!-- end .main-content -->
</div><!-- end .app-wrapper -->

<!-- Mobile Sidebar Toggle Script -->
<script>
(function() {
    const toggleBtn  = document.getElementById('mobileMenuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');
    const menuIcon   = document.getElementById('menuIcon');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        menuIcon.classList.replace('fa-bars', 'fa-xmark');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        menuIcon.classList.replace('fa-xmark', 'fa-bars');
        document.body.style.overflow = '';
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when a nav link is clicked on mobile
    const navLinks = sidebar ? sidebar.querySelectorAll('.nav-link, .logout-link') : [];
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
})();
</script>
</body>
</html>
