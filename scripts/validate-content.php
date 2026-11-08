<?php

declare(strict_types=1);

use Aplx\Content\ContentException;
use Aplx\Content\ContentRepository;
use Aplx\Content\ContentValidator;
use Aplx\Content\FrontMatterParser;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$repository = new ContentRepository(
    $projectRoot,
    new FrontMatterParser(),
    new ContentValidator(),
);
$contentTypes = require $projectRoot . '/config/content-types.php';
$result = $repository->scan($contentTypes);

if ($result['errors'] !== []) {
    fwrite(STDERR, implode(PHP_EOL, $result['errors']) . PHP_EOL);
    exit(1);
}

try {
    $repository->buildIndex($result['items']);
} catch (ContentException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, sprintf("Conteudo valido: %d arquivo(s).\n", count($result['items'])));
