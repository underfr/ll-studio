<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use App\Entity\Album;
use App\Entity\Photo;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Applique la visibilité des photos et des albums à toute requête SQL de l'ORM.
 *
 * VisibleContentExtension ne peut contraindre que l'alias racine que le
 * fournisseur d'API Platform lui transmet. Ce filtre agit une couche plus bas,
 * là où Doctrine connaît l'entité cible de chaque fragment de SQL : clause
 * WHERE, jointures ToOne et ManyToMany, et chargement paresseux d'une
 * collection. C'est ce qui ferme les trois fuites de l'issue #45, la collection
 * `photos` imbriquée, la `coverPhoto` exposée sur la collection d'albums, et
 * les compteurs qui dénombraient du contenu masqué.
 *
 * Conséquence à connaître avant de lire un repository : la visibilité devient
 * une propriété de la connexion et non plus de la requête. Le contenu que rend
 * un findBy() dépend désormais de qui a émis la requête HTTP.
 *
 * Deux points de vigilance :
 *
 * - Le filtre est déclaré `enabled: false` dans doctrine.yaml et n'est activé
 *   que par VisibleContentFilterListener, donc uniquement en contexte HTTP.
 *   La console, les fixtures et les futures commandes ne le voient jamais.
 * - Il s'applique aussi aux requêtes DQL de mise à jour et de suppression en
 *   masse. Le projet n'en contient aucune aujourd'hui ; une requête de
 *   maintenance écrite plus tard ignorerait silencieusement les lignes
 *   masquées.
 */
final class VisibleContentFilter extends SQLFilter
{
    public const string NAME = 'app_visible_content';

    /** Identifiant du compte connecté, absent pour un visiteur anonyme. */
    public const string OWNER_PARAM = 'owner_id';

    private const array RESTRICTED = [Photo::class, Album::class];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!\in_array($targetEntity->getName(), self::RESTRICTED, true)) {
            return '';
        }

        /*
         * Les noms de colonnes viennent des métadonnées et non de chaînes en
         * dur : un changement de naming_strategy ne casserait pas le filtre.
         */
        $visible = \sprintf(
            '%s.%s = true',
            $targetTableAlias,
            $targetEntity->getColumnName('visible'),
        );

        if (!$this->hasParameter(self::OWNER_PARAM)) {
            return $visible;
        }

        /*
         * getParameter rend une valeur déjà échappée par la connexion, elle
         * peut donc être insérée telle quelle dans le SQL.
         */
        return \sprintf(
            '(%s OR %s.%s = %s)',
            $visible,
            $targetTableAlias,
            $targetEntity->getSingleAssociationJoinColumnName('owner'),
            $this->getParameter(self::OWNER_PARAM),
        );
    }
}
