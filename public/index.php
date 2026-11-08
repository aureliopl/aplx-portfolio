<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Dependencias indisponiveis.';
    exit;
}

require $autoload;

use Aplx\Bootstrap;
use Aplx\Http\Request;

Bootstrap::handle($projectRoot, Request::fromServer($_SERVER, $_POST))->send();
