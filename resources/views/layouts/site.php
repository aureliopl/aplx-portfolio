<?php

declare(strict_types=1);

use Aplx\Content\ContentItem;
use Aplx\Seo\SeoData;
use Aplx\Support\View;

/** @var string $content */
/** @var ContentItem $item */
/** @var SeoData $seo */
/** @var array<string, mixed> $site */
/** @var list<array{title: string, description: string, route: string}> $searchEntries */
$assetVersion = '20260825-6';
$publishedRoutes = array_column($searchEntries, 'route');
?>
<!doctype html>
<html lang="<?= View::escape((string) $site['html_lang']) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($seo->title) ?></title>
    <meta name="description" content="<?= View::escape($seo->description) ?>">
    <meta name="robots" content="<?= View::escape($seo->robots) ?>">
    <link rel="canonical" href="<?= View::escape($seo->canonical) ?>">
<?php foreach ($seo->alternates as $hreflang => $href): ?>
    <link rel="alternate" hreflang="<?= View::escape($hreflang) ?>" href="<?= View::escape($href) ?>">
<?php endforeach; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= View::escape($seo->title) ?>">
    <meta property="og:description" content="<?= View::escape($seo->description) ?>">
    <meta property="og:url" content="<?= View::escape($seo->canonical) ?>">
    <link rel="icon" href="/favicon.svg?v=<?= $assetVersion ?>" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/site.css?v=<?= $assetVersion ?>">
    <script src="/assets/js/site.js?v=<?= $assetVersion ?>" defer></script>
</head>
<body>
<?= View::render(__DIR__ . '/../components/header.php', ['item' => $item, 'site' => $site, 'publishedRoutes' => $publishedRoutes]) ?>
<?= View::render(__DIR__ . '/../components/search.php', ['site' => $site, 'searchEntries' => $searchEntries]) ?>
<?= $content ?>
<?= View::render(__DIR__ . '/../components/footer.php', ['site' => $site, 'seo' => $seo, 'publishedRoutes' => $publishedRoutes]) ?>
<?= View::render(__DIR__ . '/../components/floating-controls.php', ['site' => $site, 'seo' => $seo]) ?>
</body>
</html>
