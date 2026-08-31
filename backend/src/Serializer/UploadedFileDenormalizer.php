<?php

declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Laisse passer tel quel un fichier déjà présent dans les données décodées.
 *
 * Sans lui, le sérialiseur tenterait de reconstruire un objet File à partir
 * de ses propriétés et échouerait : l'instance fournie par la requête est
 * déjà celle qu'attend VichUploader.
 */
final class UploadedFileDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): File
    {
        \assert($data instanceof File);

        return $data;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $data instanceof File;
    }

    /**
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [File::class => true];
    }
}
