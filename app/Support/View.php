<?php

declare(strict_types=1);

namespace Aplx\Support;

use RuntimeException;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): string
    {
        if (!is_file($view)) {
            throw new RuntimeException("Template nao encontrado: {$view}.");
        }

        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $view;
            $output = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if ($output === false) {
            throw new RuntimeException("Falha ao renderizar {$view}.");
        }

        return $output;
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    public static function emphasizeNames(string $value): string
    {
        $escaped = self::escape($value);
        $emphasized = self::emphasizeText($escaped);

        return is_string($emphasized) ? $emphasized : $escaped;
    }

    public static function emphasizeNamesInParagraphs(string $html): string
    {
        $result = preg_replace_callback(
            '/<p\b[^>]*>.*?<\/p>/su',
            static function (array $paragraph): string {
                $strongDepth = 0;
                $parts = preg_split('/(<[^>]+>)/su', (string) $paragraph[0], -1, PREG_SPLIT_DELIM_CAPTURE);

                if (!is_array($parts)) {
                    return (string) $paragraph[0];
                }

                foreach ($parts as &$part) {
                    if (str_starts_with($part, '<')) {
                        if (preg_match('/^<\s*strong\b/i', $part) === 1) {
                            $strongDepth++;
                        } elseif (preg_match('/^<\s*\/\s*strong\b/i', $part) === 1) {
                            $strongDepth = max(0, $strongDepth - 1);
                        }

                        continue;
                    }

                    if ($strongDepth === 0) {
                        $part = self::emphasizeText($part);
                    }
                }
                unset($part);

                return implode('', $parts);
            },
            $html,
        );

        return is_string($result) ? $result : $html;
    }

    private static function emphasizeText(string $value): string
    {
        $result = preg_replace(
            '/(?<![\p{L}\p{N}])(Aurélio Paz da Luz|Aurélio|APLX)(?![\p{L}\p{N}])/u',
            '<strong>$1</strong>',
            $value,
        );

        return is_string($result) ? $result : $value;
    }
}
