<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use App\Security\Role;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    /**
     * « password » contient ici le mot de passe en clair : il est haché dans
     * initialize(), juste après l'instanciation de l'entité.
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
            'roles' => [],
            'password' => 'password',
        ];
    }

    public function admin(): static
    {
        return $this->with(['roles' => [Role::ADMIN->value]]);
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $user->getPassword())
            );
        });
    }
}
