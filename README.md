# APLX Portfolio

A bilingual, file-based PHP website with hostname routing, Markdown content, front matter validation, canonical URLs, hreflang and a generated content index.

Author: [Aurélio Paz da Luz](https://aureliopaz.com/en/).

This is a source-code portfolio edition. It has its own publication history and contains no production credentials, operational runbooks, account sessions or deployment automation. It is not a hosted service.

## What to inspect

- `app/Http/`: requests, responses and routing with an explicit hostname allowlist.
- `app/Content/`: Markdown metadata validation and indexed content loading.
- `app/Seo/`: localized metadata and alternate URLs.
- `app/Contact/`: CSRF, input validation, honeypot and rate limiting.
- `resources/views/` and `public/assets/`: shared presentation and responsive navigation.
- `tests/run.php`: routing, content, security and rendering assertions.

## Demo content and integrations

The sample hosts are `aplx.example` and `aplx-br.example`. Contact links and addresses are fictitious. Legal pages are demonstration content and must not be used as legal documents. The default contact handler validates input without sending email. SMTP, analytics credentials, DNS configuration and deployment scripts are not included.

The portrait area uses a neutral SVG placeholder. Personal photography and private working files are not included.

## Setup and verification

Use PHP 8.3 or newer with JSON and mbstring, and Composer 2.

```sh
composer install
composer content:build
composer check
composer audit --locked
```

The public document root is `public/`; application code and storage belong outside it. For a local preview, `scripts/local-router.php` maps ports 8087 and 8088 to the two sample hosts. No preview server is started by the tests.

There is no automatic publication step. This edition does not configure a real domain, hosting account or outbound mail service.
