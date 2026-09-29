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
use App\Security\ContentVisibility;
use Doctrine\ORM\QueryBuilder;

/**
 * Masque les photos et les albums non publiés aux visiteurs, sur la ressource
 * demandée.
 *
 * Le drapeau `visible` ne doit pas seulement retirer un élément de la
 * galerie : sans ce filtre, GET /api/photos/{id} laisserait n'importe qui
 * consulter une photo dépubliée en devinant son identifiant.
 *
 * L'administrateur voit tout ; un utilisateur authentifié voit en plus ses
 * propres contenus masqués, ce qui lui permet de les rééditer.
 *
 * Cette extension ne peut contraindre QUE l'alias racine que lui transmet le
 * fournisseur d'API Platform. Les relations imbriquées, les jointures et les
 * compteurs sont couverts par VisibleContentFilter, une couche plus bas.
 *
 * Les deux coexistent volontairement, et la condition se retrouve donc deux
 * fois sur la racine. La raison n'est pas décorative : cette extension tourne
 * dans le fournisseur, elle est garantie à chaque lecture de l'API, elle
 * échoue fermée. Le filtre, lui, dépend d'un écouteur d'événement : s'il ne
 * tournait pas, il échouerait ouvert. Les garder tous les deux plafonne donc
 * le dégât d'un écouteur cassé au comportement d'avant l'issue #45.
 */
final readonly class VisibleContentExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const array RESTRICTED = [Photo::class, Album::class];

    public function __construct(private ContentVisibility $visibility)
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

        if ($this->visibility->seesEverything()) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $user = $this->visibility->viewer();

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
