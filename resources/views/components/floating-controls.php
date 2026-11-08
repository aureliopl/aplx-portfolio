<?php

declare(strict_types=1);

use Aplx\Seo\SeoData;
use Aplx\Support\View;

/** @var array<string, mixed> $site */
/** @var SeoData $seo */
$ui = (array) $site['ui'];
?>
<div class="floating-controls" data-floating-controls>
    <button class="floating-button back-top" type="button" data-back-top aria-label="<?= View::escape((string) $ui['back_top']) ?>" hidden>
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 14 6-6 6 6"></path></svg>
    </button>
    <a class="floating-button whatsapp-button" href="<?= View::escape((string) $site['whatsapp_url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= View::escape((string) $ui['whatsapp']) ?>">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 19.5l1.1-4A8 8 0 1 1 20 11.5Z"></path><path d="M9 8.5c.5 2.5 2 4 4.5 5l1.4-1.1 1.6.8c-.4 1.6-1.5 2.3-3 2.1-3.7-.5-6.2-3-6.8-6.7-.2-1.4.6-2.5 2.1-3l.8 1.6L9 8.5Z"></path></svg>
    </a>
    <div class="share-control">
        <button class="floating-button" type="button" data-share-toggle aria-label="<?= View::escape((string) $ui['share']) ?>" aria-expanded="false" aria-controls="share-panel">
            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="m8.2 10.8 7.6-4.5M8.2 13.2l7.6 4.5"></path></svg>
        </button>
        <div class="share-panel" id="share-panel" data-share-panel hidden>
            <button type="button" data-share-native><?= View::escape((string) $ui['share']) ?></button>
            <button type="button" data-copy-link><?= View::escape((string) $ui['copy_link']) ?></button>
            <p data-share-status aria-live="polite"></p>
        </div>
    </div>
    <span data-canonical-url="<?= View::escape($seo->canonical) ?>" hidden></span>
</div>
