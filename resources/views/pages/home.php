<?php

declare(strict_types=1);

use Aplx\Content\ContentItem;
use Aplx\Support\View;

/** @var ContentItem $item */
$m = $item->metadata;
$whatsappPhone = preg_replace('/\D+/', '', (string) $m['founder-action-phone']);
$whatsappUrl = (string) $site['whatsapp_url'];
$contactRoute = $item->locale() === 'pt-BR' ? '/contato/' : '/contacto/';
?>
<main id="conteudo-principal" class="home-page">
    <section class="hero">
        <div class="container hero-content">
            <p class="eyebrow hero-eyebrow"><?= View::emphasizeNames((string) $m['eyebrow']) ?></p>
            <h1><?= View::escape((string) $m['headline']) ?></h1>
            <p class="hero-introduction"><?= View::emphasizeNames((string) $m['introduction']) ?></p>
            <div class="hero-actions">
                <a class="button button-primary" href="#atuacao"><?= View::escape((string) $m['hero-primary-label']) ?></a>
                <a class="button button-secondary" href="#metodo"><?= View::escape((string) $m['hero-secondary-label']) ?></a>
            </div>
        </div>
        <div class="container hero-stage" aria-label="<?= View::escape((string) $m['hero-visual-title']) ?>">
            <div class="hero-orbit hero-orbit-one" aria-hidden="true"></div>
            <div class="hero-orbit hero-orbit-two" aria-hidden="true"></div>
            <div class="hero-stage-card">
                <p><?= View::emphasizeNames((string) $m['hero-visual-kicker']) ?></p>
                <h2><?= View::escape((string) $m['hero-visual-title']) ?></h2>
                <ul>
<?php foreach ((array) $m['hero-visual-items'] as $visualItem): ?>
                    <li><?= View::escape((string) $visualItem) ?></li>
<?php endforeach; ?>
                </ul>
            </div>
            <p class="hero-stage-note"><?= View::emphasizeNames((string) $m['hero-note']) ?></p>
        </div>
    </section>

    <section class="section signals-section" aria-labelledby="signals-heading">
        <div class="container">
            <header class="centered-heading compact-heading">
                <p class="eyebrow"><?= View::emphasizeNames((string) $m['signals-kicker']) ?></p>
                <h2 id="signals-heading"><?= View::escape((string) $m['signals-heading']) ?></h2>
            </header>
            <div class="signal-grid">
<?php foreach ((array) $m['signals'] as $index => $signal): ?>
                <article class="signal-card signal-card-<?= $index + 1 ?>">
                    <span class="signal-icon" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <h3><?= View::escape((string) $signal['title']) ?></h3>
                    <p><?= View::emphasizeNames((string) $signal['text']) ?></p>
                </article>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section work-section" id="atuacao">
        <div class="container">
            <header class="centered-heading">
                <p class="eyebrow"><?= View::emphasizeNames((string) $m['work-kicker']) ?></p>
                <h2><?= View::escape((string) $m['work-heading']) ?></h2>
                <p><?= View::emphasizeNames((string) $m['work-intro']) ?></p>
            </header>
            <div class="work-grid">
<?php foreach ((array) $m['work'] as $index => $work): ?>
                <article class="work-card">
                    <span class="work-symbol" aria-hidden="true">
<?php if ($index === 0): ?>
                        <svg viewBox="0 0 48 48"><rect x="8" y="10" width="32" height="27" rx="4"></rect><path d="M8 17h32M14 13.5h.1M19 13.5h.1"></path></svg>
<?php elseif ($index === 1): ?>
                        <svg viewBox="0 0 48 48"><path d="M10 36V25h8v11M20 36V17h8v19M30 36V9h8v27M8 39h32"></path></svg>
<?php elseif ($index === 2): ?>
                        <svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="16"></circle><path d="m24 24 9-8M14 32c3-4 6-6 10-6s7 2 10 6"></path></svg>
<?php else: ?>
                        <svg viewBox="0 0 48 48"><path d="M17 9v8H9M31 39v-8h8M12 20a14 14 0 0 1 23-7l4 4M36 28a14 14 0 0 1-23 7l-4-4"></path></svg>
<?php endif; ?>
                    </span>
                    <div>
                        <h3><?= View::escape((string) $work['title']) ?></h3>
                        <p><?= View::emphasizeNames((string) $work['text']) ?></p>
                    </div>
                </article>
<?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section method-section" id="metodo">
        <div class="container method-shell">
            <header class="method-heading">
                <p class="eyebrow"><?= View::emphasizeNames((string) $m['method-kicker']) ?></p>
                <h2><?= View::escape((string) $m['method-heading']) ?></h2>
                <p><?= View::emphasizeNames((string) $m['method-intro']) ?></p>
            </header>
            <ol class="method-list">
<?php foreach ((array) $m['method'] as $step): ?>
                <li>
                    <span class="method-number"><?= View::escape((string) $step['number']) ?></span>
                    <div><h3><?= View::escape((string) $step['title']) ?></h3><p><?= View::emphasizeNames((string) $step['text']) ?></p></div>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>

    <section class="section principles-section">
        <div class="container principles-layout">
            <div class="principles-copy">
                <p class="eyebrow"><?= View::emphasizeNames((string) $m['principles-kicker']) ?></p>
                <h2><?= View::escape((string) $m['principles-heading']) ?></h2>
                <p><?= View::emphasizeNames((string) $m['principles-intro']) ?></p>
            </div>
            <ul class="principles-list">
<?php foreach ((array) $m['principles'] as $index => $principle): ?>
                <li><span aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><p><?= View::emphasizeNames((string) $principle) ?></p></li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section founder-section">
        <div class="container founder-shell">
            <figure class="founder-photo">
                <img src="/assets/site/images/founder/portrait-placeholder.svg" width="768" height="960" alt="Imagem ilustrativa de demonstracao" loading="lazy" decoding="async">
                <span aria-hidden="true"></span>
            </figure>
            <div class="founder-copy">
                <p class="eyebrow"><?= View::emphasizeNames((string) $m['founder-kicker']) ?></p>
                <h2><?= View::escape((string) $m['founder-heading']) ?></h2>
                <p><?= View::emphasizeNames((string) $m['founder-text']) ?></p>
                <a class="button button-primary founder-action" href="<?= View::escape($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= View::escape((string) $m['founder-action-label']) ?></a>
            </div>
        </div>
    </section>

    <section class="section closing-section" id="proximo-passo">
        <div class="container closing-shell">
            <p class="eyebrow"><?= View::emphasizeNames((string) $m['closing-kicker']) ?></p>
            <h2><?= View::escape((string) $m['closing-heading']) ?></h2>
            <p><?= View::emphasizeNames((string) $m['closing-text']) ?></p>
            <a class="button button-on-blue" href="<?= $contactRoute ?>"><?= View::escape((string) $m['closing-action-label']) ?></a>
            <p class="closing-note"><?= View::emphasizeNames((string) $m['closing-note']) ?></p>
        </div>
    </section>
</main>
