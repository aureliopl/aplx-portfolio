<?php

declare(strict_types=1);

use Aplx\Support\View;

/** @var array<string, mixed> $site */
/** @var list<array{title: string, description: string, route: string}> $searchEntries */
$ui = (array) $site['ui'];
$searchJson = json_encode($searchEntries, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<dialog class="search-dialog" data-search-dialog aria-labelledby="search-title">
    <div class="search-panel">
        <div class="search-heading">
            <h2 id="search-title"><?= View::escape((string) $ui['search_title']) ?></h2>
            <button class="icon-button" type="button" data-search-close aria-label="<?= View::escape((string) $ui['search_close']) ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
        </div>
        <form class="search-form" role="search" data-search-form>
            <label for="site-search"><?= View::escape((string) $ui['search_label']) ?></label>
            <div class="search-field-row">
                <input id="site-search" name="q" type="search" autocomplete="off" placeholder="<?= View::escape((string) $ui['search_placeholder']) ?>" data-search-input>
                <button class="button button-primary" type="submit"><?= View::escape((string) $ui['search_open']) ?></button>
            </div>
        </form>
        <p class="search-status" data-search-status aria-live="polite"><?= View::escape((string) $ui['search_empty']) ?></p>
        <div class="search-results" data-search-results></div>
    </div>
    <span data-search-index="<?= View::escape($searchJson) ?>" hidden></span>
</dialog>
