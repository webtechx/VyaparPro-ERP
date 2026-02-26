<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

// Redirect to the application root
// $basePath is defined in index.php which includes this file
if (isset($basePath)) {
    header("Location: " . $basePath . "/");
} else {
    // Fallback: try to go to root relative to generic structure
    header("Location: ./");
}
exit();
?>