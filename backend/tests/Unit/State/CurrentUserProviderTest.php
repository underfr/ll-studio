<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Get;
use App\Entity\User;
use App\State\CurrentUserProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;

final class CurrentUserProviderTest extends KernelTestCase
{
    #[TestDox('CT-UB-15 · /api/me fournit le compte connecté')]
    public function testFournitLeCompteConnecte(): void
    {
        $compte = (new User())->setEmail('moi@ll-studio.test');
        $this->connecter($compte);

        self::assertSame($compte, $this->fournisseur()->provide(new Get()));
    }

    #[TestDox('CT-UB-15 · sans compte de l\'application, /api/me refuse au lieu de renvoyer null')]
    public function testRefuseSansCompte(): void
    {
        $fournisseur = $this->fournisseur();

        try {
            $fournisseur->provide(new Get());
            self::fail('Un appel anonyme aurait dû être refusé.');
        } catch (AccessDeniedException) {
        }

        // Un utilisateur qui n'est pas une entité User est refusé de même.
        $this->connecter(new InMemoryUser('externe', null));
        $this->expectException(AccessDeniedException::class);
        $fournisseur->provide(new Get());
    }

    private function connecter(UserInterface $utilisateur): void
    {
        static::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($utilisateur, 'api', $utilisateur->getRoles()));
    }

    private function fournisseur(): CurrentUserProvider
    {
        return new CurrentUserProvider(static::getContainer()->get(Security::class));
    }
}
