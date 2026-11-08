<?php

declare(strict_types=1);

use Aplx\Content\ContentException;
use Aplx\Content\ContentRepository;
use Aplx\Content\ContentValidator;
use Aplx\Content\FrontMatterParser;
use Aplx\Contact\ContactForm;
use Aplx\Http\Request;
use Aplx\Http\Router;
use Aplx\Support\View;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$temporaryRoot = sys_get_temp_dir() . '/aplx-test-' . bin2hex(random_bytes(6));
$failures = [];
$assertions = 0;

mkdir($temporaryRoot . '/content/es/pages', 0755, true);
mkdir($temporaryRoot . '/content/pt-br/pages', 0755, true);
mkdir($temporaryRoot . '/public/media/es', 0755, true);
mkdir($temporaryRoot . '/storage/temp', 0755, true);

register_shutdown_function(static function () use ($temporaryRoot): void {
    removeTree($temporaryRoot);
});

function expect(bool $condition, string $message): void
{
    global $assertions, $failures;
    $assertions++;

    if (!$condition) {
        $failures[] = $message;
    }
}

function removeTree(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = scandir($directory);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . '/' . $item;

        if (is_dir($path)) {
            removeTree($path);
        } else {
            unlink($path);
        }
    }

    rmdir($directory);
}

function page(string $locale, string $route, string $id, string $translationKey): string
{
    $htmlLang = $locale === 'es' ? 'es-ES' : 'pt-BR';
    $title = $locale === 'es' ? 'Pagina espanhola de teste' : 'Pagina brasileira de teste';
    $description = $locale === 'es'
        ? 'Descripcion exclusiva del contenido español.'
        : 'Descricao exclusiva do conteudo brasileiro.';

    return <<<MARKDOWN
---
content-id: {$id}
translation-key: {$translationKey}
type: page
status: published
locale: {$htmlLang}
route: {$route}
title: {$title}
description: {$description}
published-at: "2026-08-25T09:00:00+02:00"
updated-at: "2026-08-25T09:00:00+02:00"
author: aurelio
robots: index,follow
---
Conteudo seguro.

<script>alert(1)</script>

[link inseguro](javascript:alert(1))
MARKDOWN;
}

file_put_contents(
    $temporaryRoot . '/content/es/pages/teste.md',
    page('es', '/teste/', 'page-test-es', 'page-test'),
);
file_put_contents(
    $temporaryRoot . '/content/pt-br/pages/teste.md',
    page('pt-br', '/teste-br/', 'page-test-ptbr', 'page-test'),
);

$parser = new FrontMatterParser();
$validator = new ContentValidator();
$repository = new ContentRepository($temporaryRoot, $parser, $validator);
$contentTypes = require $projectRoot . '/config/content-types.php';
$scan = $repository->scan($contentTypes);

expect($scan['errors'] === [], 'Conteudos validos nao devem gerar erros.');
expect(count($scan['items']) === 2, 'A varredura deve encontrar os dois conteudos.');
expect(!str_contains($scan['items'][0]->html, '<script'), 'HTML bruto deve ser removido.');
expect(!str_contains($scan['items'][0]->html, 'javascript:'), 'Links inseguros devem ser bloqueados.');

$emphasizedNames = View::emphasizeNames('<b>APLX</b> por Aurélio Paz da Luz e Aurélio.');
expect(str_contains($emphasizedNames, '&lt;b&gt;<strong>APLX</strong>&lt;/b&gt;'), 'Enfase deve escapar HTML antes de destacar APLX.');
expect(str_contains($emphasizedNames, '<strong>Aurélio Paz da Luz</strong>'), 'Nome completo deve receber uma unica enfase.');
expect(str_contains($emphasizedNames, 'e <strong>Aurélio</strong>.'), 'Primeiro nome isolado deve receber enfase.');
$paragraphNames = View::emphasizeNamesInParagraphs('<p>APLX por Aurélio Paz da Luz e <a href="/">Aurélio</a>.</p><h2>APLX</h2><p><strong>APLX</strong></p>');
expect(str_contains($paragraphNames, '<p><strong>APLX</strong> por <strong>Aurélio Paz da Luz</strong> e <a href="/"><strong>Aurélio</strong></a>.</p>'), 'Nomes em texto e links de paragrafos devem receber enfase.');
expect(str_contains($paragraphNames, '<h2>APLX</h2>'), 'Nomes em titulos nao devem receber enfase automatica.');
expect(substr_count($paragraphNames, '<strong>APLX</strong>') === 2, 'Enfase existente nao deve ser duplicada.');

$index = $repository->buildIndex($scan['items']);
expect(isset($index['routes']['es']['/teste/']), 'Indice deve conter a rota espanhola.');
expect(isset($index['routes']['pt-br']['/teste-br/']), 'Indice deve conter a rota brasileira.');
expect(count($index['translations']['page-test']) === 2, 'Par de traducao deve conter dois idiomas.');

$sites = require $projectRoot . '/config/sites.php';
$redirects = require $projectRoot . '/config/redirects.php';
$router = new Router(
    $projectRoot,
    $sites,
    $contentTypes,
    $redirects,
    $index,
    $repository,
);

$withoutSlash = $router->dispatch(new Request('GET', 'aplx.example', '/teste', 'utm_source=test'));
expect($withoutSlash->status === 301, 'Rota sem barra deve redirecionar com 301.');
expect(
    ($withoutSlash->headers['Location'] ?? '') === 'https://aplx.example/teste/?utm_source=test',
    'Redirect deve preservar query string e adicionar barra.',
);

$canonical = $router->dispatch(new Request('GET', 'aplx.example', '/teste/', ''));
expect($canonical->status === 200, 'Rota canonica deve responder 200.');
expect(str_contains($canonical->body, 'https://aplx.example/teste/'), 'HTML deve conter canonical espanhol.');
expect(str_contains($canonical->body, 'https://aplx-br.example/teste-br/'), 'HTML deve conter hreflang brasileiro.');
expect(str_contains($canonical->body, '<header class="site-header"'), 'Layout deve renderizar o cabecalho compartilhado.');
expect(str_contains($canonical->body, '<footer class="site-footer"'), 'Layout deve renderizar o rodape compartilhado.');
expect(substr_count($canonical->body, 'class="language-toggle is-spain"') === 1, 'Seletor de idioma deve existir somente no rodape.');
$headerEnd = strpos($canonical->body, '</header>');
expect($headerEnd !== false, 'Cabecalho deve ser encerrado corretamente.');
expect(
    $headerEnd !== false && !str_contains(substr($canonical->body, 0, $headerEnd), 'language-toggle'),
    'Cabecalho nao deve conter seletor de idioma.',
);
expect(str_contains($canonical->body, 'href="https://aplx-br.example/teste-br/"'), 'Seletor deve apontar para pagina equivalente.');
expect(str_contains($canonical->body, 'hreflang="pt-BR"'), 'Seletor deve identificar o idioma alternativo.');
expect(str_contains($canonical->body, '🇪🇸') && str_contains($canonical->body, '🇧🇷'), 'Seletor deve exibir Espanha e Brasil nessa ordem.');
expect(str_contains($canonical->body, 'data-search-dialog'), 'Busca por lupa deve renderizar interface funcional.');
expect(str_contains($canonical->body, 'Pagina espanhola de teste'), 'Indice de busca deve conter o idioma atual.');
expect(!str_contains($canonical->body, 'Descricao exclusiva do conteudo brasileiro'), 'Busca nao deve vazar conteudo do outro idioma.');
expect(str_contains($canonical->body, 'data-back-top'), 'Controle de voltar ao topo deve ter nome e gancho funcional.');
expect(str_contains($canonical->body, 'data-canonical-url="https://aplx.example/teste/"'), 'Compartilhamento deve receber a URL canonica.');

$alias = $router->dispatch(new Request('GET', 'www.aplx.example', '/teste/', ''));
expect($alias->status === 301, 'Alias www deve redirecionar com 301.');
expect(($alias->headers['Location'] ?? '') === 'https://aplx.example/teste/', 'Alias deve manter a rota.');

$combinedAlias = $router->dispatch(new Request('GET', 'www.aplx.example', '/teste', 'utm_source=test'));
expect($combinedAlias->status === 301, 'Alias sem barra deve usar um unico 301.');
expect(
    ($combinedAlias->headers['Location'] ?? '') === 'https://aplx.example/teste/?utm_source=test',
    'Alias deve normalizar host e barra no mesmo redirect.',
);

$unknownHost = $router->dispatch(new Request('GET', 'invalid.example', '/', ''));
expect($unknownHost->status === 400, 'Host desconhecido deve ser rejeitado.');

$extension = $router->dispatch(new Request('GET', 'aplx.example', '/teste.php', ''));
expect($extension->status === 404, 'Rota com extensao PHP deve responder 404.');

$sitemap = $router->dispatch(new Request('GET', 'aplx.example', '/sitemap.xml', ''));
expect($sitemap->status === 200, 'Sitemap deve responder 200.');
expect(str_contains($sitemap->body, 'https://aplx.example/teste/'), 'Sitemap deve listar URL canonica.');
expect(!str_contains($sitemap->body, 'aplx-br.example'), 'Sitemap ES nao deve usar URL brasileira como loc.');

$projectRepository = new ContentRepository($projectRoot, $parser, $validator);
$projectScan = $projectRepository->scan($contentTypes);
expect($projectScan['errors'] === [], 'Conteudo real do projeto deve ser valido.');
$projectIndex = $projectRepository->buildIndex($projectScan['items']);
expect(array_keys($projectIndex['routes']['pt-br']) === ['/', '/aviso-legal/', '/contato/', '/politica-de-cookies/', '/politica-de-privacidade/', '/protecao-de-dados-lgpd/', '/sobre/', '/termos-de-uso/'], 'Rotas brasileiras publicadas devem corresponder ao conjunto institucional e legal aprovado.');
expect(array_keys($projectIndex['routes']['es']) === ['/', '/aviso-legal/', '/condiciones-de-uso/', '/contacto/', '/politica-de-cookies/', '/politica-de-privacidad/', '/proteccion-de-datos-rgpd/', '/sobre/'], 'Rotas espanholas publicadas devem corresponder ao conjunto institucional e legal aprovado.');
expect(count($projectIndex['translations']['home']) === 2, 'As homes devem formar um par de traducao completo.');
$projectRouter = new Router($projectRoot, $sites, $contentTypes, $redirects, $projectIndex, $projectRepository);
$home = $projectRouter->dispatch(new Request('GET', 'aplx-br.example', '/', ''));
expect($home->status === 200, 'Home brasileira deve responder 200.');
expect(substr_count($home->body, '<h1') === 1, 'Home brasileira deve conter um unico H1.');
expect(str_contains($home->body, 'id="metodo"'), 'Home deve conter a ancora real do metodo.');
expect(str_contains($home->body, 'href="/contato/"'), 'Navegacao deve expor a pagina de contato publicada.');
expect(!str_contains($home->body, 'href="/criacao-de-sites/"'), 'Rodape nao deve expor servico ainda em revisao.');
expect(str_contains($home->body, 'href="/sobre/"'), 'Navegacao deve expor a pagina institucional publicada.');
expect(str_contains($home->body, 'href="/politica-de-privacidade/"'), 'Rodape deve expor a pagina legal publicada.');
expect(!str_contains($home->body, 'href="/manutencao-wordpress/"'), 'Rodape nao deve expor servico ainda nao publicado.');
expect(str_contains($home->body, '/assets/css/site.css?v=20260825-6'), 'CSS deve usar versao explicita para invalidar cache de CDN.');
expect(str_contains($home->body, '/assets/js/site.js?v=20260825-6'), 'JavaScript deve usar versao explicita para invalidar cache de CDN.');
expect(str_contains($home->body, '<strong>A APLX</strong>') || str_contains($home->body, 'A <strong>APLX</strong>'), 'APLX deve aparecer em negrito nos textos corridos.');
expect(str_contains($home->body, '<strong>Aurélio Paz da Luz</strong> conduz'), 'Nome do fundador deve aparecer em negrito no texto corrido.');
$whatsappPt = 'https://example.com/contact';
expect(str_contains($home->body, 'href="' . $whatsappPt . '" target="_blank" rel="noopener noreferrer">Começar uma conversa</a>'), 'CTA brasileiro usa contato ficticio.');
expect(str_contains($home->body, 'portrait-placeholder.svg'), 'Home usa somente imagem de demonstracao.');
expect(!str_contains($home->body, 'aurelio-trabalho-'), 'Home nao deve carregar a fotografia de trabalho anterior.');
expect(!str_contains($home->body, 'aurelio-corpo-inteiro-'), 'Home nao deve carregar a fotografia de corpo inteiro.');
expect(str_contains($home->body, 'language-toggle is-brasil') && str_contains($home->body, 'href="https://aplx.example/"'), 'Seletor brasileiro deve apontar para a home espanhola.');
expect(!str_contains($home->body, 'language-toggle is-brasil" disabled'), 'Seletor brasileiro deve estar habilitado com equivalente espanhol.');
$homeEs = $projectRouter->dispatch(new Request('GET', 'aplx.example', '/', ''));
expect($homeEs->status === 200, 'Home espanhola deve responder 200.');
expect(substr_count($homeEs->body, '<h1') === 1, 'Home espanhola deve conter um unico H1.');
expect(str_contains($homeEs->body, 'Consultoría técnica para presencia digital'), 'Home espanhola deve renderizar o conteúdo próprio.');
expect(str_contains($homeEs->body, 'lang="es-ES"'), 'Home espanhola deve declarar es-ES.');
expect(str_contains($homeEs->body, 'href="https://aplx-br.example/"'), 'Seletor espanhol deve apontar para a home brasileira.');
expect(str_contains($homeEs->body, 'hreflang="pt-BR"'), 'Home espanhola deve declarar o alternativo brasileiro.');
$whatsappEs = 'https://example.com/contact';
expect(str_contains($homeEs->body, 'href="' . $whatsappEs . '" target="_blank" rel="noopener noreferrer">Empezar una conversación</a>'), 'CTA espanhol usa contato ficticio.');
$contact = $projectRouter->dispatch(new Request('GET', 'aplx-br.example', '/contato/', ''));
expect($contact->status === 200, 'Contato brasileiro publicado deve responder 200.');
expect(($contact->headers['Cache-Control'] ?? '') === 'private, no-store', 'Formulario de contato nao deve usar cache compartilhado.');
expect(str_contains($contact->body, 'name="csrf_token"'), 'Formulario deve incluir token CSRF.');
expect(str_contains($contact->body, 'name="company_site"'), 'Formulario deve incluir honeypot.');
expect(!str_contains($contact->body, 'type="checkbox"'), 'Formulario nao deve exigir aceite generico de privacidade.');
$invalidContact = $projectRouter->dispatch(new Request('POST', 'aplx-br.example', '/contato/', '', ['csrf_token' => 'invalido'], '198.51.100.20'));
expect($invalidContact->status === 422, 'POST com CSRF invalido deve responder 422.');
expect(str_contains($invalidContact->body, 'sessão do formulário expirou'), 'Erro de CSRF deve ser claro sem expor detalhes internos.');

$mailSent = false;
$contactForm = new ContactForm(
    $temporaryRoot,
    static function (string $to, string $subject, string $message, string $headers) use (&$mailSent): bool {
        $mailSent = $to === 'contact@example.com'
            && str_contains($subject, 'Novo contato')
            && str_contains($message, 'Mensagem valida para o teste')
            && str_contains($headers, 'Reply-To: teste@example.com');
        return true;
    },
);
$formGet = $contactForm->process(new Request('GET', 'aplx-br.example', '/contato/', ''), $sites['canonical']['aplx-br.example']);
$formPost = $contactForm->process(new Request('POST', 'aplx-br.example', '/contato/', '', [
    'csrf_token' => $formGet['token'],
    'company_site' => '',
    'name' => 'Pessoa de Teste',
    'email' => 'teste@example.com',
    'domain' => 'https://example.com',
    'market' => 'brasil',
    'need' => 'site',
    'message' => 'Mensagem valida para o teste do formulario seguro.',
], '198.51.100.21'), $sites['canonical']['aplx-br.example']);
expect($formPost['redirect'] === true && $formPost['errors'] === [], 'Formulario valido deve concluir com PRG sem erros.');
expect($mailSent, 'Formulario valido deve usar somente o destinatario e cabecalhos esperados.');
$privacy = $projectRouter->dispatch(new Request('GET', 'aplx-br.example', '/politica-de-privacidade/', ''));
expect($privacy->status === 200, 'Politica de privacidade brasileira deve responder 200.');
expect(str_contains($privacy->body, 'id="transferencias-internacionais"'), 'Titulos longos devem receber IDs estaveis para o indice da pagina.');
expect(str_contains($privacy->body, 'href="#formulario-de-contato"'), 'Indice da politica deve apontar para uma secao existente.');
$projectSitemap = $projectRouter->dispatch(new Request('GET', 'aplx-br.example', '/sitemap.xml', ''));
expect(str_contains($projectSitemap->body, '/contato/'), 'Contato publicado deve entrar no sitemap.');
expect(!str_contains($projectSitemap->body, '/criacao-de-sites/'), 'Rascunho de servico deve ficar fora do sitemap.');
$projectSitemapEs = $projectRouter->dispatch(new Request('GET', 'aplx.example', '/sitemap.xml', ''));
expect(str_contains($projectSitemapEs->body, 'https://aplx.example/'), 'Sitemap espanhol deve listar a home espanhola.');
expect(str_contains($projectSitemapEs->body, 'https://aplx.example/aviso-legal/'), 'Aviso legal espanhol publicado deve entrar no sitemap.');

foreach (['pt-br' => 'aplx-br.example', 'es' => 'aplx.example'] as $locale => $host) {
    foreach ($projectIndex['routes'][$locale] as $route => $_entry) {
        $response = $projectRouter->dispatch(new Request('GET', $host, (string) $route, ''));
        expect($response->status === 200, "Rota publicada {$host}{$route} deve responder 200.");
        expect(str_contains($response->body, 'rel="canonical" href="https://' . $host . $route . '"'), "Rota {$host}{$route} deve ter canonical proprio.");
        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/su', $response->body, $paragraphs);

        foreach ($paragraphs[1] ?? [] as $paragraph) {
            $withoutEmphasis = preg_replace('/<strong\b[^>]*>.*?<\/strong>/su', '', (string) $paragraph);
            $plainParagraph = html_entity_decode(strip_tags(is_string($withoutEmphasis) ? $withoutEmphasis : ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            expect(
                preg_match('/(?<![\p{L}\p{N}])(Aurélio Paz da Luz|Aurélio|APLX)(?![\p{L}\p{N}])/u', $plainParagraph) !== 1,
                "Nomes em paragrafos de {$host}{$route} devem estar em negrito: " . mb_substr(trim($plainParagraph), 0, 120),
            );
        }

        preg_match_all('/href="(\/[^"#?]*)"/', $response->body, $links);

        foreach (array_unique($links[1] ?? []) as $linkedRoute) {
            if (str_starts_with($linkedRoute, '/assets/') || $linkedRoute === '/favicon.svg') {
                continue;
            }
            expect(isset($projectIndex['routes'][$locale][$linkedRoute]), "Link interno {$linkedRoute} em {$host}{$route} deve apontar para rota publicada.");
        }
    }
}

$spanishLegalNotice = $projectRouter->dispatch(new Request('GET', 'aplx.example', '/aviso-legal/', ''));
expect($spanishLegalNotice->status === 200, 'Aviso legal espanhol publicado deve responder 200.');
expect(str_contains($spanishLegalNotice->body, 'Los datos de contacto son ficticios.'), 'Aviso de demonstracao sem endereco pessoal.');

try {
    $repository->buildIndex([$scan['items'][0], $scan['items'][0]]);
    expect(false, 'content-id duplicado deve interromper o build.');
} catch (ContentException) {
    expect(true, 'content-id duplicado foi rejeitado.');
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    fwrite(STDERR, sprintf("Falhas: %d de %d assercoes.\n", count($failures), $assertions));
    exit(1);
}

fwrite(STDOUT, sprintf("Testes concluidos: %d assercoes.\n", $assertions));
