<?php

declare(strict_types=1);

namespace Aplx\Http;

use Aplx\Contact\ContactForm;
use Aplx\Content\ContentRepository;
use Aplx\Security\Headers;
use Aplx\Seo\SeoData;
use Aplx\Support\View;

final class Router
{
    /**
     * @param array<string, mixed> $sites
     * @param array<string, mixed> $contentTypes
     * @param array<string, mixed> $redirects
     * @param array<string, mixed> $index
     */
    public function __construct(
        private readonly string $projectRoot,
        private readonly array $sites,
        private readonly array $contentTypes,
        private readonly array $redirects,
        private readonly array $index,
        private readonly ContentRepository $repository,
    ) {
    }

    public function dispatch(Request $request): Response
    {
        if (!in_array($request->method, ['GET', 'HEAD', 'POST'], true)) {
            return $this->secure(Response::html($this->errorPage(405), 405));
        }

        if (isset($this->sites['aliases'][$request->host])) {
            $canonicalHost = (string) $this->sites['aliases'][$request->host];
            $canonicalSite = $this->sites['canonical'][$canonicalHost] ?? null;
            $path = $this->validPath($request->path) ? $request->path : '/';

            if (is_array($canonicalSite) && $path !== '/' && !str_ends_with($path, '/')) {
                $locale = (string) $canonicalSite['content_locale'];
                $routes = $this->index['routes'][$locale] ?? [];

                if (isset($routes[$path . '/'])) {
                    $path .= '/';
                }
            }

            $target = 'https://' . $canonicalHost . $path . $this->querySuffix($request);

            return $this->secure(Response::redirect($target, $request->method === 'POST' ? 308 : 301));
        }

        $site = $this->sites['canonical'][$request->host] ?? null;

        if (!is_array($site)) {
            return $this->secure(Response::html($this->errorPage(400), 400));
        }

        if (!$this->validPath($request->path)) {
            return $this->secure(Response::html($this->errorPage(400), 400));
        }

        $locale = (string) $site['content_locale'];
        $baseUrl = (string) $site['base_url'];

        if ($request->path === '/robots.txt') {
            return $this->secure(Response::text("User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n"));
        }

        if ($request->path === '/sitemap.xml') {
            return $this->secure(Response::text($this->sitemap($site), 'application/xml; charset=UTF-8'));
        }

        $routes = $this->index['routes'][$locale] ?? [];

        if ($request->path !== '/' && !str_ends_with($request->path, '/')) {
            $canonicalPath = $request->path . '/';

            if (isset($routes[$canonicalPath])) {
                return $this->secure(Response::redirect(
                    $baseUrl . $canonicalPath . $this->querySuffix($request),
                    $request->method === 'POST' ? 308 : 301,
                ));
            }
        }

        $redirect = $this->redirects[$locale][$request->path] ?? null;

        if (is_string($redirect)) {
            return $this->secure(Response::redirect($baseUrl . $redirect));
        }

        $entry = $routes[$request->path] ?? null;

        if (!is_array($entry)) {
            return $this->secure(Response::html($this->errorPage(404), 404));
        }

        $item = $this->repository->load((string) $entry['file']);
        $metadata = $item->metadata;
        $isContact = (string) ($metadata['translation-key'] ?? '') === 'contact';

        if ($request->method === 'POST' && !$isContact) {
            return $this->secure(Response::html($this->errorPage(405), 405));
        }

        $form = null;

        if ($isContact) {
            $form = (new ContactForm($this->projectRoot))->process($request, $site);

            if ($form['redirect']) {
                return $this->secure(Response::redirect($baseUrl . $request->path . '?sent=1', 303));
            }
        }
        $type = (string) $metadata['type'];
        $template = in_array($item->id(), ['home-ptbr', 'home-es'], true)
            ? 'pages/home'
            : ($isContact
                ? 'pages/contact'
                : (string) ($this->contentTypes[$type]['template'] ?? 'pages/default'));
        $alternates = $this->alternates($metadata, $request->path);
        $seo = new SeoData(
            (string) $metadata['title'],
            (string) $metadata['description'],
            $baseUrl . $request->path,
            (string) $metadata['robots'],
            $alternates,
        );

        $content = View::render(
            $this->projectRoot . '/resources/views/' . $template . '.php',
            ['item' => $item, 'site' => $site, 'form' => $form],
        );
        $html = View::render(
            $this->projectRoot . '/resources/views/layouts/site.php',
            [
                'content' => $content,
                'item' => $item,
                'seo' => $seo,
                'site' => $site,
                'searchEntries' => $this->searchEntries($locale),
            ],
        );

        $response = Response::html($request->method === 'HEAD' ? '' : $html, $form !== null && $form['errors'] !== [] ? 422 : 200);

        return $this->secure($response->withHeaders([
            'Cache-Control' => $isContact ? 'private, no-store' : 'public, max-age=0, s-maxage=300, stale-while-revalidate=60',
        ]));
    }

    /**
     * @return list<array{title: string, description: string, route: string}>
     */
    private function searchEntries(string $locale): array
    {
        $entries = [];

        foreach (($this->index['routes'][$locale] ?? []) as $route => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $metadata = $entry['metadata'] ?? [];

            if (!is_array($metadata) || str_contains((string) ($metadata['robots'] ?? ''), 'noindex')) {
                continue;
            }

            $entries[] = [
                'title' => (string) ($metadata['title'] ?? ''),
                'description' => (string) ($metadata['description'] ?? ''),
                'route' => (string) $route,
            ];
        }

        return $entries;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, string>
     */
    private function alternates(array $metadata, string $route): array
    {
        $translationKey = (string) ($metadata['translation-key'] ?? '');

        if ($translationKey === '' || !isset($this->index['translations'][$translationKey])) {
            return [];
        }

        $alternates = [];

        foreach ($this->sites['canonical'] as $site) {
            $locale = (string) $site['content_locale'];
            $alternateRoute = $this->index['translations'][$translationKey][$locale] ?? null;

            if (is_string($alternateRoute)) {
                $alternates[(string) $site['hreflang']] = (string) $site['base_url'] . $alternateRoute;
            }
        }

        return $alternates;
    }

    /**
     * @param array<string, mixed> $site
     */
    private function sitemap(array $site): string
    {
        $locale = (string) $site['content_locale'];
        $baseUrl = (string) $site['base_url'];
        $routes = $this->index['routes'][$locale] ?? [];
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($routes as $route => $entry) {
            $robots = (string) ($entry['metadata']['robots'] ?? 'index,follow');

            if (str_contains($robots, 'noindex')) {
                continue;
            }

            $url = View::escape($baseUrl . $route);
            $lastModified = View::escape((string) ($entry['metadata']['updated-at'] ?? ''));
            $lines[] = "  <url><loc>{$url}</loc><lastmod>{$lastModified}</lastmod></url>";
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function validPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_contains($path, "\0")
            && !str_contains($path, '//')
            && !str_contains($path, '/./')
            && !str_contains($path, '/../')
            && rawurldecode($path) === $path;
    }

    private function querySuffix(Request $request): string
    {
        if ($request->query === '' || strpbrk($request->query, "\r\n") !== false) {
            return '';
        }

        return '?' . $request->query;
    }

    private function errorPage(int $status): string
    {
        $messages = [
            400 => 'Requisicao invalida',
            404 => 'Pagina nao encontrada',
            405 => 'Metodo nao permitido',
        ];
        $message = $messages[$status] ?? 'Erro';

        return "<!doctype html><html lang=\"pt-BR\"><meta charset=\"utf-8\"><meta name=\"robots\" content=\"noindex\"><title>{$status}</title><h1>{$message}</h1></html>";
    }

    private function secure(Response $response): Response
    {
        return $response->withHeaders(Headers::defaults());
    }
}
