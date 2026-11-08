<?php
/** Rebuild the Vercel public site from the PHP source. Run: php build-static.php */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('SABINO_STATIC_BUILD', true);
ob_start();
require __DIR__ . '/index.php';
$html = ob_get_clean();

$output = __DIR__ . '/public';
foreach ([$output, "$output/assets", "$output/assets/css", "$output/assets/js"] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Could not create $dir");
    }
}

if (file_put_contents("$output/index.html", $html) === false
    || !copy(__DIR__ . '/assets/css/sabino.css', "$output/assets/css/sabino.css")
    || !copy(__DIR__ . '/assets/js/sabino-static.js', "$output/assets/js/sabino.js")) {
    throw new RuntimeException('Could not write the static site.');
}
echo "Built public/index.html and assets.\n";
