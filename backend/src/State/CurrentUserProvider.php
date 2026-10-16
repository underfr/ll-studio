<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Fournit à GET /api/me le compte du porteur du jeton (issue #62).
 *
 * Le front n'a pas d'identifiant à demander : le jeton d'accès ne transporte
 * que l'adresse e-mail. Cette opération lui permet de vérifier la session
 * auprès du serveur et d'afficher le nom de l'administrateur connecté.
 *
 * @implements ProviderInterface<User>
 */
final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $user = $this->security->getUser();

        /*
         * Sans compte, refuser plutôt que renvoyer null : API Platform
         * répondrait 404, comme si la route n'existait pas. L'exception est
         * convertie en 401 par le pare-feu, comme sur toute opération
         * protégée appelée sans jeton.
         */
        if (!$user instanceof User) {
            throw new AccessDeniedException('Authentification requise.');
        }

        return $user;
    }
}
