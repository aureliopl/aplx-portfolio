<?php

declare(strict_types=1);

namespace Aplx;

use Aplx\Content\ContentRepository;
use Aplx\Content\ContentValidator;
use Aplx\Content\FrontMatterParser;
use Aplx\Http\Request;
use Aplx\Http\Response;
use Aplx\Http\Router;
use Aplx\Security\Headers;
use Throwable;

final class Bootstrap
{
    public static function handle(string $projectRoot, Request $request): Response
    {
        try {
            $sites = require $projectRoot . '/config/sites.php';
            $contentTypes = require $projectRoot . '/config/content-types.php';
            $redirects = require $projectRoot . '/config/redirects.php';
            $indexFile = $projectRoot . '/storage/cache/content-index.php';

            if (!is_file($indexFile)) {
                return new Response(
                    '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="robots" content="noindex"><title>503</title><h1>Site em preparacao</h1></html>',
                    503,
                    array_merge(['Content-Type' => 'text/html; charset=UTF-8'], Headers::defaults()),
                );
            }

            $index = require $indexFile;
            $repository = new ContentRepository(
                $projectRoot,
                new FrontMatterParser(),
                new ContentValidator(),
            );
            $router = new Router(
                $projectRoot,
                $sites,
                $contentTypes,
                $redirects,
                $index,
                $repository,
            );

            return $router->dispatch($request);
        } catch (Throwable $exception) {
            self::log($projectRoot, $exception);

            return new Response(
                '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="robots" content="noindex"><title>500</title><h1>Erro interno</h1></html>',
                500,
                array_merge(['Content-Type' => 'text/html; charset=UTF-8'], Headers::defaults()),
            );
        }
    }

    private static function log(string $projectRoot, Throwable $exception): void
    {
        $directory = $projectRoot . '/storage/logs';

        if (!is_dir($directory)) {
            return;
        }

        $line = sprintf(
            "[%s] %s in %s:%d\n",
            gmdate('c'),
            str_replace(["\r", "\n"], ' ', $exception->getMessage()),
            $exception->getFile(),
            $exception->getLine(),
        );
        error_log($line, 3, $directory . '/application.log');
    }
}
