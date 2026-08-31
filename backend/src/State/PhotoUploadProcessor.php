<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Photo;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Rattache la photo téléversée au compte qui l'envoie.
 *
 * L'auteur n'est pas un champ du formulaire : le laisser au client
 * permettrait d'attribuer une photo à quelqu'un d'autre, et donc de
 * contourner le ContentVoter qui s'appuie sur la propriété.
 *
 * @implements ProcessorInterface<Photo, Photo>
 */
final readonly class PhotoUploadProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Photo, Photo> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Photo
    {
        $user = $this->security->getUser();

        if ($data instanceof Photo && null === $data->getOwner() && $user instanceof User) {
            $data->setOwner($user);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
