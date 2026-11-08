<?php

declare(strict_types=1);

use Aplx\Content\ContentItem;
use Aplx\Support\View;

/** @var ContentItem $item */
?>
<main id="conteudo-principal">
    <article class="content-page">
        <header class="content-header">
            <h1><?= View::escape((string) $item->metadata['title']) ?></h1>
            <p><?= View::emphasizeNames((string) $item->metadata['description']) ?></p>
        </header>
        <div class="content-body">
            <?= View::emphasizeNamesInParagraphs($item->html) ?>
        </div>
    </article>
</main>
