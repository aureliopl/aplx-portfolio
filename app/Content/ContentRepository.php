<?php

declare(strict_types=1);

namespace Aplx\Content;

use DateTimeImmutable;
use DateTimeInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ContentRepository
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly FrontMatterParser $parser,
        private readonly ContentValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $contentTypes
     * @return array{items: list<ContentItem>, errors: list<string>}
     */
    public function scan(array $contentTypes): array
    {
        $contentRoot = $this->projectRoot . '/content';
        $items = [];
        $errors = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contentRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'md' || $file->getFilename() === 'README.md') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = ltrim(substr($absolutePath, strlen($contentRoot)), '/');

            try {
                $item = $this->parser->parse($absolutePath, $relativePath);
                $itemErrors = $this->validator->validate($item, $contentTypes, $this->projectRoot);

                foreach ($itemErrors as $error) {
                    $errors[] = "{$relativePath}: {$error}";
                }

                $items[] = $item;
            } catch (ContentException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        sort($errors);

        return ['items' => $items, 'errors' => $errors];
    }

    public function load(string $relativePath): ContentItem
    {
        $contentRoot = realpath($this->projectRoot . '/content');
        $absolutePath = realpath($this->projectRoot . '/content/' . ltrim($relativePath, '/'));

        if ($contentRoot === false || $absolutePath === false) {
            throw new ContentException("Arquivo de conteudo nao encontrado: {$relativePath}.");
        }

        $prefix = rtrim($contentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!str_starts_with($absolutePath, $prefix) || !is_file($absolutePath)) {
            throw new ContentException("Caminho de conteudo invalido: {$relativePath}.");
        }

        return $this->parser->parse($absolutePath, ltrim($relativePath, '/'));
    }

    /**
     * @param list<ContentItem> $items
     * @return array<string, mixed>
     */
    public function buildIndex(array $items, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $index = [
            'generated-at' => $now->format(DateTimeInterface::ATOM),
            'routes' => ['es' => [], 'pt-br' => []],
            'translations' => [],
        ];
        $ids = [];

        foreach ($items as $item) {
            $metadata = $item->metadata;
            $id = $item->id();
            $localeKey = explode('/', $item->relativePath, 2)[0];
            $status = $item->status();

            if (isset($ids[$id])) {
                throw new ContentException("content-id duplicado: {$id}.");
            }

            $ids[$id] = true;

            if (!$this->isPublic($status, (string) $metadata['published-at'], $now)) {
                continue;
            }

            $route = $item->route();

            if (isset($index['routes'][$localeKey][$route])) {
                throw new ContentException("Rota duplicada em {$localeKey}: {$route}.");
            }

            $index['routes'][$localeKey][$route] = $item->indexData();
            $translationKey = (string) ($metadata['translation-key'] ?? '');

            if ($translationKey !== '') {
                if (isset($index['translations'][$translationKey][$localeKey])) {
                    throw new ContentException("translation-key duplicada em {$localeKey}: {$translationKey}.");
                }

                $index['translations'][$translationKey][$localeKey] = $route;
            }
        }

        foreach ($index['routes'] as &$routes) {
            ksort($routes);
        }
        unset($routes);
        ksort($index['translations']);

        return $index;
    }

    /**
     * @param array<string, mixed> $index
     */
    public function writeIndex(array $index, string $target): void
    {
        $directory = dirname($target);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ContentException("Nao foi possivel criar {$directory}.");
        }

        $temporary = tempnam($directory, 'content-index-');

        if ($temporary === false) {
            throw new ContentException('Nao foi possivel criar indice temporario.');
        }

        $php = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($index, true) . ";\n";

        if (file_put_contents($temporary, $php, LOCK_EX) === false || !rename($temporary, $target)) {
            @unlink($temporary);
            throw new ContentException("Nao foi possivel gravar {$target}.");
        }
    }

    private function isPublic(string $status, string $publishedAt, DateTimeImmutable $now): bool
    {
        if ($status === 'published') {
            return true;
        }

        if ($status !== 'scheduled') {
            return false;
        }

        return new DateTimeImmutable($publishedAt) <= $now;
    }
}
