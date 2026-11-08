<?php

declare(strict_types=1);

namespace Aplx\Http;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $host,
        public string $path,
        public string $query,
        public array $post = [],
        public string $remoteAddress = '',
    ) {
    }

    /**
     * @param array<string, mixed> $server
     */
    public static function fromServer(array $server, array $post = []): self
    {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $host = strtolower(trim((string) ($server['HTTP_HOST'] ?? '')));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        return new self(
            $method,
            $host,
            is_string($path) && $path !== '' ? $path : '/',
            is_string($query) ? $query : '',
            $post,
            (string) ($server['HTTP_CF_CONNECTING_IP'] ?? $server['REMOTE_ADDR'] ?? ''),
        );
    }
}
