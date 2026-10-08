<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\Role;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    #[TestDox('CT-UB-04 · deux rôles exactement, avec leur libellé français')]
    public function testRolesEtLibelles(): void
    {
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], Role::values());
        self::assertSame('Utilisateur', Role::USER->label());
        self::assertSame('Administrateur', Role::ADMIN->label());
    }
}
