(() => {
    'use strict';

    const menuButton = document.querySelector('[data-menu-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');
    const header = document.querySelector('[data-site-header]');

    const closeMenu = () => {
        if (!menuButton || !mobileMenu) return;
        mobileMenu.hidden = true;
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', menuButton.dataset.openLabel);
    };

    menuButton?.addEventListener('click', () => {
        const willOpen = mobileMenu.hidden;
        mobileMenu.hidden = !willOpen;
        menuButton.setAttribute('aria-expanded', String(willOpen));
        menuButton.setAttribute('aria-label', willOpen ? menuButton.dataset.closeLabel : menuButton.dataset.openLabel);
    });
    mobileMenu?.addEventListener('click', (event) => {
        if (event.target.closest('a')) closeMenu();
    });
    document.addEventListener('click', (event) => {
        if (!header?.contains(event.target)) closeMenu();
    });

    const dialog = document.querySelector('[data-search-dialog]');
    const searchOpen = document.querySelector('[data-search-open]');
    const searchInput = document.querySelector('[data-search-input]');
    const searchForm = document.querySelector('[data-search-form]');
    const searchStatus = document.querySelector('[data-search-status]');
    const searchResults = document.querySelector('[data-search-results]');
    const searchIndexNode = document.querySelector('[data-search-index]');
    const searchEntries = searchIndexNode ? JSON.parse(searchIndexNode.dataset.searchIndex) : [];
    const initialSearchMessage = searchStatus?.textContent || '';

    searchOpen?.addEventListener('click', () => {
        closeMenu();
        dialog?.showModal();
        searchInput?.focus();
    });
    document.querySelector('[data-search-close]')?.addEventListener('click', () => dialog?.close());
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog?.addEventListener('close', () => searchOpen?.focus());

    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    searchForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const query = normalize(searchInput.value.trim());
        searchResults.replaceChildren();
        if (query.length < 2) {
            searchStatus.textContent = initialSearchMessage;
            return;
        }
        const matches = searchEntries.filter((entry) => normalize(`${entry.title} ${entry.description}`).includes(query));
        searchStatus.textContent = matches.length ? `${matches.length} resultado${matches.length === 1 ? '' : 's'}.` : searchStatus.dataset.none;
        if (!matches.length) searchStatus.textContent = document.documentElement.lang === 'pt-BR' ? 'Nenhum conteúdo publicado foi encontrado.' : 'No se han encontrado contenidos publicados.';
        matches.forEach((entry) => {
            const article = document.createElement('article');
            const link = document.createElement('a');
            const description = document.createElement('p');
            link.href = entry.route;
            link.textContent = entry.title;
            description.textContent = entry.description;
            article.append(link, description);
            searchResults.append(article);
        });
    });

    const backTop = document.querySelector('[data-back-top]');
    const updateBackTop = () => { if (backTop) backTop.hidden = window.scrollY < 480; };
    window.addEventListener('scroll', updateBackTop, { passive: true });
    updateBackTop();
    backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }));

    const shareToggle = document.querySelector('[data-share-toggle]');
    const sharePanel = document.querySelector('[data-share-panel]');
    const canonical = document.querySelector('[data-canonical-url]')?.dataset.canonicalUrl || location.href;
    const shareStatus = document.querySelector('[data-share-status]');
    shareToggle?.addEventListener('click', () => {
        const open = sharePanel.hidden;
        sharePanel.hidden = !open;
        shareToggle.setAttribute('aria-expanded', String(open));
    });
    document.querySelector('[data-share-native]')?.addEventListener('click', async () => {
        if (navigator.share) await navigator.share({ title: document.title, url: canonical });
        else await navigator.clipboard.writeText(canonical);
    });
    document.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
        await navigator.clipboard.writeText(canonical);
        shareStatus.textContent = document.documentElement.lang === 'pt-BR' ? 'Link copiado' : 'Enlace copiado';
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            if (sharePanel) sharePanel.hidden = true;
            shareToggle?.setAttribute('aria-expanded', 'false');
        }
    });
})();
