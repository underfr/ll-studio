<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Rôles applicatifs.
 *
 * Symfony manipule les rôles comme de simples chaînes ; les regrouper dans
 * une énumération donne une source unique de vérité, réutilisée par la
 * validation de l'entité User, les fixtures et les règles d'accès.
 */
enum Role: string
{
    /**
     * Tout compte authentifié. Attribué implicitement par User::getRoles().
     */
    case USER = 'ROLE_USER';

    /**
     * Accès complet au back-office : photos, albums, catégories, messages
     * et comptes.
     */
    case ADMIN = 'ROLE_ADMIN';

    /**
     * Valeurs acceptées par la validation du champ `roles`.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::USER => 'Utilisateur',
            self::ADMIN => 'Administrateur',
        };
    }
}
