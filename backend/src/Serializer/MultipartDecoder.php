<?php

declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

/**
 * Décode une requête multipart/form-data pour le sérialiseur.
 *
 * API Platform ne sait nativement lire que des corps de requête textuels
 * (JSON, JSON-LD…). Ce décodeur reconstitue un tableau associatif à partir
 * des champs de formulaire et des fichiers de la requête, pour que le
 * dénormaliseur puisse alimenter l'entité comme il le ferait depuis du JSON.
 */
final readonly class MultipartDecoder implements DecoderInterface
{
    public const string FORMAT = 'multipart';

    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        return array_map(
            static function (string $element) {
                // Un champ de formulaire ne transporte que du texte : les
                // valeurs structurées (tableaux d'IRI, booléens) arrivent
                // encodées en JSON, on les décode quand c'est le cas.
                $decoded = json_decode($element, true);

                return \JSON_ERROR_NONE === json_last_error() ? $decoded : $element;
            },
            $request->request->all()
        ) + $request->files->all();
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
