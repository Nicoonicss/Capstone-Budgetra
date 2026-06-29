<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Root → landing page
if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/public/index.php';
    return;
}

// Static assets (CSS, JS, images, fonts) → serve directly
$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
if (in_array($ext, ['css','js','png','jpg','jpeg','gif','ico','svg','woff','woff2','ttf','eot','map'])) {
    return false;
}

// PHP file exists at project root → require it
$abs = __DIR__ . $uri;
if (file_exists($abs) && is_file($abs)) {
    require $abs;
    return;
}

// PHP file under /public → require it
$pub = __DIR__ . '/public' . $uri;
if (file_exists($pub) && is_file($pub)) {
    require $pub;
    return;
}

http_response_code(404);
echo "<h1>404 Not Found</h1><p>" . htmlspecialchars($uri) . "</p>";
