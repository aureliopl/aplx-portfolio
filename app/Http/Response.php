<?php

declare(strict_types=1);

namespace Aplx\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $body, string $contentType = 'text/plain; charset=UTF-8'): self
    {
        return new self($body, 200, ['Content-Type' => $contentType]);
    }

    public static function redirect(string $location, int $status = 301): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self($this->body, $this->status, array_merge($this->headers, $headers));
    }

    public function send(): never
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
        exit;
    }
}
