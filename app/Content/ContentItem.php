<?php

declare(strict_types=1);

namespace Aplx\Content;

final readonly class ContentItem
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $relativePath,
        public array $metadata,
        public string $markdown,
        public string $html,
    ) {
    }

    public function id(): string
    {
        return (string) $this->metadata['content-id'];
    }

    public function locale(): string
    {
        return (string) $this->metadata['locale'];
    }

    public function route(): string
    {
        return (string) $this->metadata['route'];
    }

    public function status(): string
    {
        return (string) $this->metadata['status'];
    }

    /**
     * @return array<string, mixed>
     */
    public function indexData(): array
    {
        return [
            'file' => $this->relativePath,
            'metadata' => $this->metadata,
        ];
    }
}
