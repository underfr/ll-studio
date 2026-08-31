<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Décide qui peut modifier ou supprimer une photo ou un album.
 *
 * Le portfolio n'a qu'un photographe aujourd'hui, mais la règle est écrite
 * en termes de propriété plutôt que de rôle : un second compte pourra être
 * créé sans que quiconque puisse toucher au travail d'un autre.
 *
 * @extends Voter<self::EDIT|self::DELETE, Photo|Album>
 */
final class ContentVoter extends Voter
{
    public const string EDIT = 'CONTENT_EDIT';
    public const string DELETE = 'CONTENT_DELETE';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::DELETE], true)
            && ($subject instanceof Photo || $subject instanceof Album);
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // L'administrateur gère l'ensemble du contenu du site.
        if ($user->isAdmin()) {
            return true;
        }

        // Sinon, chacun ne dispose que de ses propres publications.
        return $subject->getOwner() === $user;
    }
}
