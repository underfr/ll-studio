<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Photo;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Renseigne Photo::$contentUrl juste avant la sérialisation.
 *
 * La base ne stocke que le nom du fichier ; l'URL publique dépend du mapping
 * VichUploader (uri_prefix). La calculer ici évite de figer le préfixe dans
 * la base et de devoir migrer si le dossier de stockage change.
 */
#[AsDecorator('api_platform.jsonld.normalizer.item')]
final readonly class PhotoContentUrlNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    public function __construct(
        #[AutowireDecorated]
        private NormalizerInterface $decorated,
        private StorageInterface $storage,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): ArrayObject|array|string|int|float|bool|null
    {
        if ($data instanceof Photo) {
            $data->setContentUrl($this->storage->resolveUri($data, 'imageFile'));
        }

        return $this->decorated->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    /**
     * Le Serializer s'injecte dans le normaliseur de tête, c'est-à-dire ce
     * décorateur : il faut le transmettre au normaliseur décoré, qui en a
     * besoin pour sérialiser les relations imbriquées.
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
