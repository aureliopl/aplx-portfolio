<?php

declare(strict_types=1);

namespace Aplx\Contact;

use Aplx\Http\Request;
use Closure;

final class ContactForm
{
    private Closure $mailer;

    public function __construct(private readonly string $projectRoot, ?callable $mailer = null)
    {
        $this->mailer = $mailer === null
            ? static fn (string $to, string $subject, string $message, string $headers): bool => true // A demonstracao valida os dados, sem enviar e-mail.
            : Closure::fromCallable($mailer);
    }

    /**
     * @param array<string, mixed> $site
     * @return array{errors: array<string, string>, values: array<string, string>, token: string, sent: bool, redirect: bool}
     */
    public function process(Request $request, array $site): array
    {
        $this->startSession();
        $token = $this->token();
        $values = [
            'name' => '',
            'email' => '',
            'domain' => '',
            'market' => '',
            'need' => '',
            'message' => '',
        ];
        $state = [
            'errors' => [],
            'values' => $values,
            'token' => $token,
            'sent' => $request->method === 'GET' && $request->query === 'sent=1',
            'redirect' => false,
        ];

        if ($request->method !== 'POST') {
            return $state;
        }

        if (trim((string) ($request->post['company_site'] ?? '')) !== '') {
            $state['redirect'] = true;
            return $state;
        }

        $submittedToken = (string) ($request->post['csrf_token'] ?? '');

        if ($submittedToken === '' || !hash_equals($token, $submittedToken)) {
            $state['errors']['form'] = $this->message($site, 'A sessão do formulário expirou. Recarregue a página e tente novamente.', 'La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.');
            return $state;
        }

        if (!$this->allowAttempt($request->remoteAddress)) {
            $state['errors']['form'] = $this->message($site, 'Muitas tentativas em pouco tempo. Aguarde antes de enviar novamente.', 'Se han realizado demasiados intentos. Espera antes de volver a enviar.');
            return $state;
        }

        foreach ($values as $field => $_) {
            $state['values'][$field] = trim((string) ($request->post[$field] ?? ''));
        }

        $state['errors'] = $this->validate($state['values'], $site);

        if ($state['errors'] !== []) {
            return $state;
        }

        $recipient = (string) $site['contact_email'];
        $subject = $this->message($site, 'Novo contato pelo site APLX', 'Nuevo contacto desde el sitio APLX');
        $body = implode("\n", [
            'Nome / Nombre: ' . $state['values']['name'],
            'E-mail: ' . $state['values']['email'],
            'Dominio: ' . ($state['values']['domain'] !== '' ? $state['values']['domain'] : '-'),
            'Mercado: ' . $state['values']['market'],
            'Necessidade / Necesidad: ' . $state['values']['need'],
            '',
            $state['values']['message'],
        ]);
        $headers = implode("\r\n", [
            'From: APLX <' . $recipient . '>',
            'Reply-To: ' . $state['values']['email'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Content-Type-Options: nosniff',
        ]);

        if (!(($this->mailer)($recipient, $subject, $body, $headers))) {
            $state['errors']['form'] = $this->message($site, 'Não foi possível enviar agora. Use o e-mail ou o WhatsApp informados nesta página.', 'No ha sido posible enviar el mensaje. Utiliza el correo o WhatsApp indicados en esta página.');
            return $state;
        }

        unset($_SESSION['aplx_contact_csrf']);
        $state['redirect'] = true;

        return $state;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, mixed> $site
     * @return array<string, string>
     */
    private function validate(array $values, array $site): array
    {
        $errors = [];

        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100 || strpbrk($values['name'], "\r\n") !== false) {
            $errors['name'] = $this->message($site, 'Informe um nome entre 2 e 100 caracteres.', 'Indica un nombre de entre 2 y 100 caracteres.');
        }

        if (filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($values['email']) > 190 || strpbrk($values['email'], "\r\n") !== false) {
            $errors['email'] = $this->message($site, 'Informe um e-mail válido.', 'Indica un correo válido.');
        }

        if ($values['domain'] !== '' && (mb_strlen($values['domain']) > 200 || preg_match('#^(?:https?://)?[a-z0-9.-]+(?:/[^\s]*)?$#i', $values['domain']) !== 1)) {
            $errors['domain'] = $this->message($site, 'Informe um domínio ou URL válido.', 'Indica un dominio o una URL válida.');
        }

        if (!in_array($values['market'], ['brasil', 'espanha', 'ambos'], true)) {
            $errors['market'] = $this->message($site, 'Selecione o mercado principal.', 'Selecciona el mercado principal.');
        }

        if (!in_array($values['need'], ['site', 'seo', 'performance', 'manutencao', 'automacao', 'outro'], true)) {
            $errors['need'] = $this->message($site, 'Selecione o tipo de necessidade.', 'Selecciona el tipo de necesidad.');
        }

        if (mb_strlen($values['message']) < 20 || mb_strlen($values['message']) > 3000) {
            $errors['message'] = $this->message($site, 'Escreva uma mensagem entre 20 e 3.000 caracteres.', 'Escribe un mensaje de entre 20 y 3.000 caracteres.');
        }

        return $errors;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('aplx_contact');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    private function token(): string
    {
        $token = $_SESSION['aplx_contact_csrf'] ?? null;

        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['aplx_contact_csrf'] = $token;
        }

        return $token;
    }

    private function allowAttempt(string $remoteAddress): bool
    {
        $key = hash('sha256', $remoteAddress !== '' ? $remoteAddress : session_id());
        $path = $this->projectRoot . '/storage/temp/contact-rate-' . $key . '.json';
        $handle = fopen($path, 'c+');

        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return false;
        }

        $contents = stream_get_contents($handle);
        $decoded = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
        $now = time();
        $attempts = array_values(array_filter(is_array($decoded) ? $decoded : [], static fn (mixed $time): bool => is_int($time) && $time > $now - 600));
        $allowed = count($attempts) < 5;

        if ($allowed) {
            $attempts[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($attempts, JSON_THROW_ON_ERROR));
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return $allowed;
    }

    /** @param array<string, mixed> $site */
    private function message(array $site, string $ptBr, string $es): string
    {
        return (string) $site['hreflang'] === 'pt-BR' ? $ptBr : $es;
    }
}
