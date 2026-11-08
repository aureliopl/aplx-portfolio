<?php

declare(strict_types=1);

$publicRoot = dirname(__DIR__) . '/public';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

if (is_string($path) && $path !== '/') {
    $asset = realpath($publicRoot . $path);
    $publicPrefix = rtrim((string) realpath($publicRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (is_string($asset) && str_starts_with($asset, $publicPrefix) && is_file($asset)) {
        return false;
    }
}

$localPort = (string) ($_SERVER['SERVER_PORT'] ?? '8087');
$_SERVER['HTTP_HOST'] = $localPort === '8088' ? 'aplx.example' : 'aplx-br.example';

require $publicRoot . '/index.php';
