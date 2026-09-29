<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Source unique de la politique de visibilité des contenus.
 *
 * Deux mécanismes appliquent cette politique : VisibleContentExtension sur la
 * requête racine d'API Platform, et VisibleContentFilter sur toutes les autres
 * profondeurs. Sans ce service, « qui est administrateur » et « qui est
 * propriétaire » seraient définis à deux endroits, et une divergence entre les
 * deux ne se remarquerait que des mois plus tard.
 *
 * À ne jamais injecter dans VisibleContentFilter : le constructeur de SQLFilter
 * est déclaré final par Doctrine, et l'état lu hors des paramètres du filtre
 * échapperait au calcul d'empreinte du cache de compilation DQL.
 */
final readonly class ContentVisibility
{
    public function __construct(private Security $security)
    {
    }

    /** L'administrateur voit l'intégralité du contenu, publié ou non. */
    public function seesEverything(): bool
    {
        return $this->security->isGranted(Role::ADMIN->value);
    }

    /**
     * Compte connecté, s'il y en a un. Il voit en plus ses propres contenus
     * masqués, ce qui lui permet de les rééditer.
     */
    public function viewer(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }
}
