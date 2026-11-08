<?php

declare(strict_types=1);

namespace Aplx\Content;

use DateTimeImmutable;
use DateTimeInterface;

final class ContentValidator
{
    /** @var list<string> */
    private const REQUIRED_FIELDS = [
        'content-id',
        'type',
        'status',
        'locale',
        'route',
        'title',
        'description',
        'published-at',
        'updated-at',
        'author',
        'robots',
    ];

    /** @var list<string> */
    private const STATUSES = ['draft', 'review', 'scheduled', 'published', 'archived'];

    /** @var array<string, string> */
    private const LOCALES = ['es' => 'es-ES', 'pt-br' => 'pt-BR'];

    /**
     * @param array<string, mixed> $contentTypes
     * @return list<string>
     */
    public function validate(ContentItem $item, array $contentTypes, string $projectRoot): array
    {
        $errors = [];
        $metadata = $item->metadata;

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $metadata) || $metadata[$field] === '') {
                $errors[] = "Campo obrigatorio ausente: {$field}.";
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        $id = (string) $metadata['content-id'];
        $type = (string) $metadata['type'];
        $status = (string) $metadata['status'];
        $locale = (string) $metadata['locale'];
        $route = (string) $metadata['route'];

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id) !== 1) {
            $errors[] = 'content-id deve usar ASCII, minusculas e kebab-case.';
        }

        if (!array_key_exists($type, $contentTypes)) {
            $errors[] = "Tipo de conteudo desconhecido: {$type}.";
        } else {
            $expectedDirectory = (string) $contentTypes[$type]['directory'];
            $pathAfterLocale = explode('/', $item->relativePath, 2)[1] ?? '';

            if (!str_starts_with($pathAfterLocale, $expectedDirectory . '/')) {
                $errors[] = "Tipo {$type} deve ficar em {$expectedDirectory}/.";
            }
        }

        if (!in_array($status, self::STATUSES, true)) {
            $errors[] = "Status desconhecido: {$status}.";
        }

        $pathLocale = explode('/', $item->relativePath, 2)[0] ?? '';

        if (!isset(self::LOCALES[$pathLocale]) || self::LOCALES[$pathLocale] !== $locale) {
            $errors[] = "Locale {$locale} nao corresponde a pasta {$pathLocale}.";
        }

        if ($route !== '/' && preg_match('#^/(?:[a-z0-9]+(?:-[a-z0-9]+)*/)+$#', $route) !== 1) {
            $errors[] = 'route deve usar segmentos ASCII em kebab-case e terminar com barra.';
        }

        if (str_contains($route, '.php') || str_contains($route, '.html')) {
            $errors[] = 'route nao pode expor extensao .php ou .html.';
        }

        foreach (['published-at', 'updated-at'] as $dateField) {
            if (!is_string($metadata[$dateField]) || !$this->validDate($metadata[$dateField])) {
                $errors[] = "Data invalida em {$dateField}; use uma string ISO 8601 entre aspas.";
            }
        }

        if (
            is_string($metadata['published-at'])
            && is_string($metadata['updated-at'])
            && $this->validDate($metadata['published-at'])
            && $this->validDate($metadata['updated-at'])
            && new DateTimeImmutable($metadata['updated-at']) < new DateTimeImmutable($metadata['published-at'])
        ) {
            $errors[] = 'updated-at nao pode ser anterior a published-at.';
        }

        if (
            isset($contentTypes[$type])
            && $type !== 'page'
            && is_string($metadata['published-at'])
            && $this->validDate($metadata['published-at'])
        ) {
            $published = new DateTimeImmutable($metadata['published-at']);
            $expectedPrefix = sprintf(
                '%s/%s/%s/',
                (string) $contentTypes[$type]['directory'],
                $published->format('Y'),
                $published->format('m'),
            );
            $pathAfterLocale = explode('/', $item->relativePath, 2)[1] ?? '';

            if (!str_starts_with($pathAfterLocale, $expectedPrefix)) {
                $errors[] = "Arquivo deve ficar em {$expectedPrefix} conforme published-at.";
            }
        }

        if (!in_array((string) $metadata['robots'], ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            $errors[] = 'robots possui valor nao permitido.';
        }

        if (in_array($status, ['published', 'scheduled'], true) && trim($item->markdown) === '') {
            $errors[] = 'Conteudo publicado ou agendado nao pode ter corpo vazio.';
        }

        if (isset($metadata['translation-key']) && $metadata['translation-key'] !== '') {
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $metadata['translation-key']) !== 1) {
                $errors[] = 'translation-key deve usar ASCII, minusculas e kebab-case.';
            }
        }

        if (isset($metadata['image']) && $metadata['image'] !== '') {
            $image = (string) $metadata['image'];

            if (!str_starts_with($image, '/media/')) {
                $errors[] = 'Imagem editorial deve usar caminho iniciado por /media/.';
            } elseif (!is_file($projectRoot . '/public' . $image)) {
                $errors[] = "Imagem nao encontrada: {$image}.";
            }
        }

        $forbidden = '/[\x{2013}\x{2014}\x{2018}\x{2019}\x{201C}\x{201D}\x{00A0}\x{200B}\x{200C}\x{200D}\x{FEFF}]/u';
        $source = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . $item->markdown;

        if (preg_match($forbidden, $source) === 1) {
            $errors[] = 'Conteudo possui caractere tipografico ou invisivel proibido.';
        }

        if (preg_match('/&(?:nbsp|#x?20);/i', $source) === 1) {
            $errors[] = 'Conteudo possui entidade HTML de espaco proibida.';
        }

        if (str_contains($item->markdown, '<?php')) {
            $errors[] = 'Conteudo editorial nao pode executar PHP.';
        }

        return $errors;
    }

    private function validDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);

        return $date !== false && $date->format(DateTimeInterface::ATOM) === $value;
    }
}
