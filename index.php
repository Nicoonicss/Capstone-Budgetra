<?php
// Simple front controller
$request = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = str_replace(dirname($script_name), '', $request);
$path = parse_url($path, PHP_URL_PATH);

// Route the request
if ($path === '/' || $path === '/index.php') {
    require __DIR__ . '/public/index.php';
} elseif (file_exists(__DIR__ . $path)) {
    // Serve existing files directly (CSS, JS, images, etc.)
    return false;
} elseif (file_exists(__DIR__ . '/public' . $path)) {
    require __DIR__ . '/public' . $path;
} elseif (file_exists(__DIR__ . '/src' . $path)) {
    require __DIR__ . '/src' . $path;
} else {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
}
