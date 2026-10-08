<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Security\Role;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[TestDox('CT-UB-01 · tout compte possède ROLE_USER, jamais en double')]
    public function testRoleUtilisateurToujoursPresent(): void
    {
        self::assertSame(['ROLE_USER'], (new User())->getRoles());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], (new User())->setRoles(['ROLE_ADMIN'])->getRoles());
        self::assertSame(['ROLE_USER'], (new User())->setRoles(['ROLE_USER'])->getRoles());
    }

    #[TestDox('CT-UB-02 · seul ROLE_ADMIN fait un administrateur')]
    public function testEstAdministrateur(): void
    {
        self::assertFalse((new User())->isAdmin());
        self::assertFalse((new User())->setRoles(['ROLE_USER'])->isAdmin());
        self::assertTrue((new User())->setRoles([Role::ADMIN->value])->isAdmin());
    }

    #[TestDox('CT-UB-03 · le nom complet est « Prénom Nom », sans espace parasite')]
    public function testNomComplet(): void
    {
        self::assertSame('Loïck Laurent', (new User())->setFirstName('Loïck')->setLastName('Laurent')->getFullName());
        self::assertSame('Laurent', (new User())->setLastName('Laurent')->getFullName());
        self::assertSame('Loïck', (new User())->setFirstName('Loïck')->getFullName());
    }
}
