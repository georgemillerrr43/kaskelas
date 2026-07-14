        </main>

        <footer class="pub-footer">
            <span>
                &copy; <?= date('Y') ?> <strong style="color:#475569">Uangkas Kelas</strong>. Hak Cipta Dilindungi.
            </span>
            <span>
                Dikelola oleh <strong style="color:var(--primary-600)">Bendahara Kelas</strong> &mdash; Transparansi keuangan untuk semua.
            </span>
        </footer>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Print button ──
            const btn = document.getElementById('printBtn');
            if (btn) {
                btn.addEventListener('click', function() {
                    window.print();
                });
            }

            // ── Filter form submit on change ──
            const selects = document.querySelectorAll('.filter-select');
            const filterForm = document.getElementById('filterForm');
            if (selects.length && filterForm) {
                selects.forEach(function(s) {
                    s.addEventListener('change', function() { filterForm.submit(); });
                });
            }
        });
        </script>
    </body>
    </html>
