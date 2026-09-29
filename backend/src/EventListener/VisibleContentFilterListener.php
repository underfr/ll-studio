<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Doctrine\Filter\VisibleContentFilter;
use App\Security\ContentVisibility;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Active VisibleContentFilter pour la requête HTTP en cours.
 *
 * Le filtre est déclaré désactivé dans doctrine.yaml : il n'existe donc que
 * pendant une requête HTTP, jamais en console. C'est ce qui garde
 * doctrine:fixtures:load et les futures commandes à l'abri de ses effets.
 *
 * La priorité 7 n'est pas arbitraire. Le pare-feu de Symfony résout le jeton à
 * la priorité 8 et rien ne tourne en dessous : plus haut, isGranted serait
 * évalué contre un jeton nul et un administrateur se verrait filtré comme un
 * visiteur anonyme.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 7)]
final readonly class VisibleContentFilterListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContentVisibility $visibility,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $filters = $this->entityManager->getFilters();

        /*
         * Repartir d'une instance propre rend l'écoute idempotente. Sous
         * PHP-FPM le processus est remis à plat entre deux requêtes, mais si le
         * projet passait un jour à un modèle à processus résident, un
         * identifiant de propriétaire positionné pour un visiteur resterait
         * collé pour le suivant.
         */
        if ($filters->isEnabled(VisibleContentFilter::NAME)) {
            $filters->disable(VisibleContentFilter::NAME);
        }

        if ($this->visibility->seesEverything()) {
            return;
        }

        $filtre = $filters->enable(VisibleContentFilter::NAME);
        $lecteur = $this->visibility->viewer();

        if (null !== $lecteur && null !== $lecteur->getId()) {
            $filtre->setParameter(VisibleContentFilter::OWNER_PARAM, $lecteur->getId(), Types::INTEGER);
        }
    }
}
