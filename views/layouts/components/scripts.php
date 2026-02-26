<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= $basePath ?>/public/assets/js/vendors.min.js"></script>

<!-- Intercept app.js's broken relative translation fetch BEFORE app.js loads -->
<script>
    (function() {
        var _origFetch = window.fetch;
        var _correctBase = '<?= $basePath ?>/public/assets/data/translations/';
        window.fetch = function(url, opts) {
            // If app.js tries to fetch a relative translations URL, fix it
            if (typeof url === 'string' && url.indexOf('translations/') !== -1 && url.indexOf('://') === -1) {
                var filename = url.split('translations/').pop();
                url = _correctBase + filename + '?v=' + new Date().getTime(); // Cache buster
            }
            return _origFetch.call(this, url, opts);
        };
    })();
</script>

<script src="<?= $basePath ?>/public/assets/js/app.js"></script>
<?php if (($title ?? '') === 'Dashboard'): ?>
<script src="<?= $basePath ?>/public/assets/js/pages/dashboard.js"></script>
<?php endif; ?>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Cube Loader Component JS -->
<script src="<?= $basePath ?>/public/assets/js/cube-loader.js"></script>

<?php if (isset($extra_scripts)) echo $extra_scripts; ?>

<script>
    $(document).ready(function() {
        // Initialize Select2 on any element with class 'select2'
        $('.select2').select2({
            width: '100%'
        });
    });
</script>

<script>
    lucide.createIcons();
</script>
    <!-- Datatables js -->
    <script src="<?= $basePath ?>/public/assets/plugins/datatables/dataTables.min.js"></script>
    <script src="<?= $basePath ?>/public/assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="<?= $basePath ?>/public/assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="<?= $basePath ?>/public/assets/plugins/datatables/responsive.bootstrap5.min.js"></script>

    <script>
        new DataTable('[data-tables="basic"]', {
            language: {
                paginate: {
                    first: '<i class="ti ti-chevrons-left"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>',
                    last: '<i class="ti ti-chevrons-right"></i>'
                }
            }
        });
    </script>
</body>
</html>
