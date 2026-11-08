<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$options = getopt('', [
    'locale:',
    'type:',
    'slug:',
    'route:',
    'title:',
    'description:',
    'translation-key::',
    'content-id::',
    'date::',
]);

foreach (['locale', 'type', 'slug', 'route', 'title', 'description'] as $required) {
    if (!isset($options[$required]) || trim((string) $options[$required]) === '') {
        fwrite(STDERR, "Opcao obrigatoria ausente: --{$required}.\n");
        exit(1);
    }
}

$locale = (string) $options['locale'];
$type = (string) $options['type'];
$slug = (string) $options['slug'];
$route = (string) $options['route'];
$locales = ['es' => 'es-ES', 'pt-br' => 'pt-BR'];
$contentTypes = require $projectRoot . '/config/content-types.php';

if (!isset($locales[$locale])) {
    fwrite(STDERR, "Locale invalido. Use es ou pt-br.\n");
    exit(1);
}

if (!isset($contentTypes[$type])) {
    fwrite(STDERR, "Tipo de conteudo invalido.\n");
    exit(1);
}

if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) {
    fwrite(STDERR, "Slug deve usar ASCII, minusculas e kebab-case.\n");
    exit(1);
}

if ($route !== '/' && preg_match('#^/(?:[a-z0-9]+(?:-[a-z0-9]+)*/)+$#', $route) !== 1) {
    fwrite(STDERR, "Route deve usar segmentos em kebab-case e terminar com barra.\n");
    exit(1);
}

$timezone = new DateTimeZone('Europe/Madrid');
$dateInput = isset($options['date']) ? (string) $options['date'] : 'now';

try {
    $date = new DateTimeImmutable($dateInput, $timezone);
} catch (Throwable) {
    fwrite(STDERR, "Data invalida.\n");
    exit(1);
}

$contentId = isset($options['content-id']) && $options['content-id'] !== false
    ? (string) $options['content-id']
    : "{$type}-{$slug}-{$locale}";
$translationKey = isset($options['translation-key']) && $options['translation-key'] !== false
    ? (string) $options['translation-key']
    : '';

foreach ([$contentId, ...($translationKey === '' ? [] : [$translationKey])] as $identifier) {
    if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $identifier) !== 1) {
        fwrite(STDERR, "Identificadores devem usar ASCII, minusculas e kebab-case.\n");
        exit(1);
    }
}

$directory = (string) $contentTypes[$type]['directory'];

if ($type !== 'page') {
    $directory .= '/' . $date->format('Y/m');
}

$contentDirectory = $projectRoot . '/content/' . $locale . '/' . $directory;
$contentFile = $contentDirectory . '/' . $slug . '.md';
$mediaDirectory = sprintf(
    '%s/public/media/%s/%s/%s/%s',
    $projectRoot,
    $locale,
    $date->format('Y'),
    $date->format('m'),
    $contentId,
);

if (file_exists($contentFile)) {
    fwrite(STDERR, "Arquivo ja existe: {$contentFile}.\n");
    exit(1);
}

foreach ([$contentDirectory, $mediaDirectory] as $directoryToCreate) {
    if (!is_dir($directoryToCreate) && !mkdir($directoryToCreate, 0755, true) && !is_dir($directoryToCreate)) {
        fwrite(STDERR, "Nao foi possivel criar {$directoryToCreate}.\n");
        exit(1);
    }
}

$metadata = [
    'content-id' => $contentId,
    'type' => $type,
    'status' => 'draft',
    'locale' => $locales[$locale],
    'route' => $route,
    'title' => (string) $options['title'],
    'description' => (string) $options['description'],
    'published-at' => $date->format(DateTimeInterface::ATOM),
    'updated-at' => $date->format(DateTimeInterface::ATOM),
    'author' => 'aurelio',
    'robots' => 'noindex,follow',
];

if ($translationKey !== '') {
    $metadata['translation-key'] = $translationKey;
}

$yaml = Yaml::dump(
    $metadata,
    2,
    2,
    Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE | Yaml::DUMP_FORCE_DOUBLE_QUOTES_ON_VALUES,
);
$contents = "---\n{$yaml}---\n\n";

if (file_put_contents($contentFile, $contents, LOCK_EX) === false) {
    fwrite(STDERR, "Nao foi possivel criar {$contentFile}.\n");
    exit(1);
}

fwrite(STDOUT, "Conteudo criado: {$contentFile}\n");
fwrite(STDOUT, "Midia: {$mediaDirectory}\n");
fwrite(STDOUT, "Status inicial: draft\n");
