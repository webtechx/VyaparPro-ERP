<?php
// Main Master Layout
// This file wraps the content pages with the standard Header, Sidebar, and Footer.

// Define constant to indicate layout is loaded
define('LAYOUT_LOADED', true);

// Ensure a view file is specified
if (!isset($viewFile)) {
    die('Error: View file not specified in layout.');
}

// 1. Start Output Buffering to capture the view's content
ob_start();

// 2. Include the View File
// This executes the PHP in the view (setting $title, etc.) and generates the HTML content logic
include $viewFile;

// 3. Get the content and clean the buffer
$content = ob_get_clean();

// 4. Now Include Head (CSS, Meta) - $title is now available!
include __DIR__ . '/components/head.php';

// Include Topbar
include __DIR__ . '/components/TobbarHeader.php';

// Include Sidebar
include __DIR__ . '/components/SidebarHeader.php';
?>

<!-- ============================================================== -->
<!-- Start Main Content Wrapper -->
<!-- ============================================================== -->
<!-- The content of the specific page will be injected here -->
<?php echo $content; ?>
<!-- ============================================================== -->
<!-- End Main Content Wrapper -->
<!-- ============================================================== -->

<?php
// Include Footer
include __DIR__ . '/components/footer.php';

// Include Scripts (JS)
include __DIR__ . '/components/scripts.php';
?>
</body>
</html>
