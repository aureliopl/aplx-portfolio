<?php

declare(strict_types=1);

use Aplx\Seo\SeoData;
use Aplx\Support\View;

/** @var array<string, mixed> $site */
/** @var SeoData $seo */
/** @var list<string> $publishedRoutes */
$ui = (array) $site['ui'];
$publishedLinks = static fn (array $links): array => array_filter(
    $links,
    static fn (mixed $href): bool => is_string($href) && in_array($href, $publishedRoutes, true),
);
$services = $publishedLinks((array) ($site['footer_services'] ?? []));
$institutional = $publishedLinks((array) ($site['footer_institutional'] ?? []));
$legal = $publishedLinks((array) ($site['footer_legal'] ?? []));
$isBrazil = (string) $site['hreflang'] === 'pt-BR';
$alternativeHreflang = $isBrazil ? 'es-ES' : 'pt-BR';
$alternativeHref = $seo->alternates[$alternativeHreflang] ?? null;
$languageLabel = $isBrazil
    ? 'Versão em espanhol indisponível para esta página'
    : 'Versão em português indisponível para esta página';
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <section class="footer-intro" aria-labelledby="footer-aplx">
            <h2 id="footer-aplx">APLX</h2>
            <p><?= View::emphasizeNames((string) $ui['footer_about']) ?></p>
        </section>
        <nav aria-labelledby="footer-navigation">
            <h2 id="footer-navigation"><?= View::escape((string) $ui['footer_navigation']) ?></h2>
            <ul class="footer-list">
                <li><a href="/"><?= View::escape((string) $ui['home']) ?></a></li>
            </ul>
        </nav>
<?php if ($services !== []): ?>
        <nav aria-labelledby="footer-services">
            <h2 id="footer-services"><?= View::escape((string) $ui['footer_work']) ?></h2>
            <ul class="footer-list">
<?php foreach ($services as $label => $href): ?>
                <li><a href="<?= View::escape((string) $href) ?>"><?= View::escape((string) $label) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>
<?php endif; ?>
<?php if ($institutional !== []): ?>
        <nav aria-labelledby="footer-institutional">
            <h2 id="footer-institutional"><?= View::escape((string) $ui['footer_institutional']) ?></h2>
            <ul class="footer-list">
<?php foreach ($institutional as $label => $href): ?>
                <li><a href="<?= View::escape((string) $href) ?>"><?= View::escape((string) $label) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>
<?php endif; ?>
<?php if ($legal !== []): ?>
        <nav aria-labelledby="footer-legal">
            <h2 id="footer-legal"><?= $isBrazil ? 'Legal e privacidade' : 'Legal y privacidad' ?></h2>
            <ul class="footer-list">
<?php foreach ($legal as $label => $href): ?>
                <li><a href="<?= View::escape((string) $href) ?>"><?= View::escape((string) $label) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>
<?php endif; ?>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= View::emphasizeNames('APLX. Aurélio Paz da Luz.') ?></p>
<?php if (is_string($alternativeHref)): ?>
        <a class="language-toggle<?= $isBrazil ? ' is-brasil' : ' is-spain' ?>" href="<?= View::escape($alternativeHref) ?>" hreflang="<?= View::escape($alternativeHreflang) ?>" aria-label="<?= $isBrazil ? 'Abrir versão em espanhol' : 'Abrir versão em português' ?>">
<?php else: ?>
        <button class="language-toggle<?= $isBrazil ? ' is-brasil' : ' is-spain' ?>" type="button" aria-label="<?= View::escape($languageLabel) ?>" disabled>
<?php endif; ?>
            <span class="language-option language-option-es" aria-hidden="true">🇪🇸</span>
            <span class="language-option language-option-br" aria-hidden="true">🇧🇷</span>
<?php if (is_string($alternativeHref)): ?>
        </a>
<?php else: ?>
        </button>
<?php endif; ?>
    </div>
</footer>
