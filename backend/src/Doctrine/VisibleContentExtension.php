<?php

declare(strict_types=1);

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\User;
use App\Security\Role;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Masque les photos et les albums non publiés aux visiteurs.
 *
 * Le drapeau `visible` ne doit pas seulement retirer un élément de la
 * galerie : sans ce filtre, GET /api/photos/{id} laisserait n'importe qui
 * consulter une photo dépubliée en devinant son identifiant.
 *
 * L'administrateur voit tout ; un utilisateur authentifié voit en plus ses
 * propres contenus masqués, ce qui lui permet de les rééditer.
 */
final readonly class VisibleContentExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const array RESTRICTED = [Photo::class, Album::class];

    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrict($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrict($queryBuilder, $resourceClass);
    }

    private function restrict(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (!\in_array($resourceClass, self::RESTRICTED, true)) {
            return;
        }

        if ($this->security->isGranted(Role::ADMIN->value)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    \sprintf('%s.visible = :visible', $alias),
                    \sprintf('%s.owner = :owner', $alias),
                ))
                ->setParameter('visible', true)
                ->setParameter('owner', $user);

            return;
        }

        $queryBuilder
            ->andWhere(\sprintf('%s.visible = :visible', $alias))
            ->setParameter('visible', true);
    }
}
