<?php

declare(strict_types=1);

use Aplx\Content\ContentItem;
use Aplx\Support\View;

/** @var ContentItem $item */
/** @var array<string, mixed> $site */
/** @var array{errors: array<string, string>, values: array<string, string>, token: string, sent: bool} $form */
$isBrazil = $item->locale() === 'pt-BR';
$route = $item->route();
$error = static fn (string $field): string => isset($form['errors'][$field]) ? '<p class="field-error" id="error-' . $field . '">' . View::escape($form['errors'][$field]) . '</p>' : '';
$describedBy = static fn (string $field): string => isset($form['errors'][$field]) ? ' aria-describedby="error-' . $field . '" aria-invalid="true"' : '';
?>
<main id="conteudo-principal">
    <article class="content-page contact-page">
        <header class="content-header">
            <h1><?= View::escape((string) $item->metadata['title']) ?></h1>
            <p><?= View::emphasizeNames((string) $item->metadata['description']) ?></p>
        </header>
<?php if ($form['sent']): ?>
        <div class="form-notice form-success" role="status"><?= $isBrazil ? 'Dados validados. Esta demonstração não envia mensagens.' : 'Datos validados. Esta demostración no envía mensajes.' ?></div>
<?php endif; ?>
        <section class="contact-form-section" aria-labelledby="contact-form-heading">
            <h2 id="contact-form-heading"><?= $isBrazil ? 'Explique o que você precisa' : 'Explica qué necesitas' ?></h2>
            <p><?= $isBrazil ? 'Envie apenas as informações necessárias para uma primeira avaliação. Não inclua senhas ou dados sensíveis.' : 'Envía solo la información necesaria para una primera valoración. No incluyas contraseñas ni datos sensibles.' ?></p>
<?php if (isset($form['errors']['form'])): ?>
            <div class="form-notice form-error" role="alert"><?= View::escape($form['errors']['form']) ?></div>
<?php endif; ?>
            <form class="contact-form" action="<?= $route ?>" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= View::escape($form['token']) ?>">
                <div class="form-trap" aria-hidden="true"><label for="company-site">Deixe este campo vazio</label><input id="company-site" name="company_site" type="text" tabindex="-1" autocomplete="off"></div>
                <div class="form-grid">
                    <div class="form-field"><label for="name"><?= $isBrazil ? 'Nome' : 'Nombre' ?></label><input id="name" name="name" value="<?= View::escape($form['values']['name']) ?>" maxlength="100" autocomplete="name" required<?= $describedBy('name') ?>><?= $error('name') ?></div>
                    <div class="form-field"><label for="email">E-mail</label><input id="email" name="email" type="email" value="<?= View::escape($form['values']['email']) ?>" maxlength="190" autocomplete="email" required<?= $describedBy('email') ?>><?= $error('email') ?></div>
                </div>
                <div class="form-field"><label for="domain"><?= $isBrazil ? 'Domínio ou site atual (opcional)' : 'Dominio o sitio actual (opcional)' ?></label><input id="domain" name="domain" value="<?= View::escape($form['values']['domain']) ?>" maxlength="200" inputmode="url" placeholder="https://"<?= $describedBy('domain') ?>><?= $error('domain') ?></div>
                <div class="form-grid">
                    <div class="form-field"><label for="market"><?= $isBrazil ? 'Mercado principal' : 'Mercado principal' ?></label><select id="market" name="market" required<?= $describedBy('market') ?>><option value=""><?= $isBrazil ? 'Selecione' : 'Selecciona' ?></option><option value="brasil"<?= $form['values']['market'] === 'brasil' ? ' selected' : '' ?>>Brasil</option><option value="espanha"<?= $form['values']['market'] === 'espanha' ? ' selected' : '' ?>>España</option><option value="ambos"<?= $form['values']['market'] === 'ambos' ? ' selected' : '' ?>><?= $isBrazil ? 'Ambos' : 'Ambos' ?></option></select><?= $error('market') ?></div>
                    <div class="form-field"><label for="need"><?= $isBrazil ? 'Tipo de necessidade' : 'Tipo de necesidad' ?></label><select id="need" name="need" required<?= $describedBy('need') ?>><option value=""><?= $isBrazil ? 'Selecione' : 'Selecciona' ?></option><option value="site">Site / web</option><option value="seo">SEO</option><option value="performance"><?= $isBrazil ? 'Performance' : 'Rendimiento' ?></option><option value="manutencao"><?= $isBrazil ? 'Manutenção' : 'Mantenimiento' ?></option><option value="automacao"><?= $isBrazil ? 'Automação e IA' : 'Automatización e IA' ?></option><option value="outro"><?= $isBrazil ? 'Outro' : 'Otro' ?></option></select><?= $error('need') ?></div>
                </div>
                <div class="form-field"><label for="message"><?= $isBrazil ? 'Mensagem' : 'Mensaje' ?></label><textarea id="message" name="message" minlength="20" maxlength="3000" rows="8" required<?= $describedBy('message') ?>><?= View::escape($form['values']['message']) ?></textarea><?= $error('message') ?></div>
                <p class="form-privacy"><?= $isBrazil ? 'Os dados serão usados para responder e avaliar a solicitação. Consulte a' : 'Los datos se utilizarán para responder y valorar la solicitud. Consulta la' ?> <a href="<?= $isBrazil ? '/politica-de-privacidade/' : '/politica-de-privacidad/' ?>"><?= $isBrazil ? 'Política de Privacidade' : 'Política de Privacidad' ?></a> <?= $isBrazil ? 'e seus' : 'y tus' ?> <a href="<?= $isBrazil ? '/protecao-de-dados-lgpd/' : '/proteccion-de-datos-rgpd/' ?>"><?= $isBrazil ? 'direitos LGPD' : 'derechos RGPD' ?></a>.</p>
                <button class="button button-primary" type="submit"><?= $isBrazil ? 'Enviar mensagem' : 'Enviar mensaje' ?></button>
            </form>
        </section>
        <div class="content-body"><?= View::emphasizeNamesInParagraphs($item->html) ?></div>
    </article>
</main>
