<?php
// includes/footer.php
$settings = getStoreSettings();
?>
        </main>

        <!-- Footer Bar -->
        <footer class="no-print bg-white border-t border-slate-200 py-3 px-6 text-center sm:text-left text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong class="text-slate-700"><?= e($settings['nama_toko']) ?></strong>. All rights reserved.
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <span>System Status: <span class="text-emerald-500 font-semibold">Online</span></span>
            </div>
        </footer>
    </div>
</div>

<!-- Global JavaScript -->
<script src="../assets/js/main.js"></script>
</body>
</html>
