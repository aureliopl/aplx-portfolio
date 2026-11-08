<?php

declare(strict_types=1);

namespace Aplx\Content;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class FrontMatterParser
{
    private GithubFlavoredMarkdownConverter $markdown;

    public function __construct()
    {
        $this->markdown = new GithubFlavoredMarkdownConverter([
            'allow_unsafe_links' => false,
            'html_input' => 'strip',
            'max_nesting_level' => 20,
        ]);
    }

    public function parse(string $absolutePath, string $relativePath): ContentItem
    {
        $size = filesize($absolutePath);

        if ($size !== false && $size > 2 * 1024 * 1024) {
            throw new ContentException("{$relativePath} excede o limite de 2 MB.");
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new ContentException("Nao foi possivel ler {$relativePath}.");
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            throw new ContentException("{$relativePath} usa BOM, que nao e permitido.");
        }

        $contents = str_replace("\r\n", "\n", $contents);

        if (!str_starts_with($contents, "---\n")) {
            throw new ContentException("{$relativePath} nao possui front matter YAML.");
        }

        $closing = strpos($contents, "\n---\n", 4);

        if ($closing === false) {
            throw new ContentException("{$relativePath} possui front matter sem fechamento.");
        }

        $yaml = substr($contents, 4, $closing - 4);
        $markdown = ltrim(substr($contents, $closing + 5), "\n");

        try {
            $metadata = Yaml::parse(
                $yaml,
                Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE | Yaml::PARSE_EXCEPTION_ON_ALIAS,
            );
        } catch (ParseException $exception) {
            throw new ContentException(
                "YAML invalido em {$relativePath}: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        if (!is_array($metadata)) {
            throw new ContentException("Front matter de {$relativePath} deve ser um mapa YAML.");
        }

        /** @var array<string, mixed> $metadata */
        $html = (string) $this->markdown->convert($markdown);

        return new ContentItem(
            $relativePath,
            $metadata,
            $markdown,
            $this->withHeadingIds($html),
        );
    }

    private function withHeadingIds(string $html): string
    {
        $used = [];
        $result = preg_replace_callback(
            '/<h([2-3])>(.*?)<\/h\1>/su',
            static function (array $matches) use (&$used): string {
                $text = html_entity_decode(strip_tags((string) $matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $slug = strtr(mb_strtolower($text), [
                    'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
                    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
                    'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                    'ç' => 'c', 'ñ' => 'n',
                ]);
                $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
                $slug = $slug !== '' ? $slug : 'secao';
                $base = $slug;
                $suffix = 2;

                while (isset($used[$slug])) {
                    $slug = $base . '-' . $suffix++;
                }

                $used[$slug] = true;

                return '<h' . $matches[1] . ' id="' . $slug . '">' . $matches[2] . '</h' . $matches[1] . '>';
            },
            $html,
        );

        return is_string($result) ? $result : $html;
    }
}
