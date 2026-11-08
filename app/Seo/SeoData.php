<?php

declare(strict_types=1);

namespace Aplx\Seo;

final readonly class SeoData
{
    /**
     * @param array<string, string> $alternates
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots,
        public array $alternates,
    ) {
    }
}
