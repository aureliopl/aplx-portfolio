<?php

declare(strict_types=1);

use Aplx\Content\ContentItem;
use Aplx\Support\View;

/** @var array<string, mixed> $site */
/** @var ContentItem $item */
/** @var list<string> $publishedRoutes */
$ui = (array) $site['ui'];
$navigation = array_filter(
    (array) ($site['navigation'] ?? []),
    static fn (mixed $href): bool => is_string($href) && in_array($href, $publishedRoutes, true),
);
$isHome = $item->route() === '/';
?>
<a class="skip-link" href="#conteudo-principal"><?= (string) $site['hreflang'] === 'pt-BR' ? 'Pular para o conteúdo' : 'Saltar al contenido' ?></a>
<header class="site-header" data-site-header>
    <div class="container header-inner">
        <a class="brand" href="/" aria-label="APLX, <?= View::escape((string) $ui['home']) ?>">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>APLX</span>
        </a>
<?php if ($navigation !== []): ?>
        <nav class="desktop-nav" aria-label="<?= View::escape((string) $ui['navigation']) ?>">
<?php foreach ($navigation as $label => $href): ?>
            <a href="<?= View::escape((string) $href) ?>"><?= View::escape((string) $label) ?></a>
<?php endforeach; ?>
        </nav>
<?php endif; ?>
        <div class="header-actions">
            <button class="icon-button" type="button" data-search-open aria-label="<?= View::escape((string) $ui['search_open']) ?>" aria-haspopup="dialog">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4 4"></path></svg>
            </button>
<?php if ($navigation !== []): ?>
            <button class="icon-button menu-toggle" type="button" data-menu-toggle data-open-label="<?= View::escape((string) $ui['menu_open']) ?>" data-close-label="<?= View::escape((string) $ui['menu_close']) ?>" aria-label="<?= View::escape((string) $ui['menu_open']) ?>" aria-expanded="false" aria-controls="mobile-menu">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
            </button>
<?php endif; ?>
        </div>
    </div>
<?php if ($navigation !== []): ?>
    <nav class="mobile-menu" id="mobile-menu" data-mobile-menu aria-label="<?= View::escape((string) $ui['navigation']) ?>" hidden>
        <div class="container mobile-menu-inner">
            <a href="/"<?= $isHome ? ' aria-current="page"' : '' ?>><?= View::escape((string) $ui['home']) ?></a>
<?php foreach ($navigation as $label => $href): ?>
            <a href="<?= View::escape((string) $href) ?>"><?= View::escape((string) $label) ?></a>
<?php endforeach; ?>
        </div>
    </nav>
<?php endif; ?>
</header>
