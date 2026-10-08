<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Doctrine\Filter\VisibleContentFilter;
use App\Entity\User;
use App\EventListener\VisibleContentFilterListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class VisibleContentFilterListenerTest extends KernelTestCase
{
    #[TestDox('CT-UB-08 · l\'administrateur n\'est pas filtré')]
    public function testAdministrateurNonFiltre(): void
    {
        $this->connecter((new User())->setRoles(['ROLE_ADMIN']));

        $this->ecouter(HttpKernelInterface::MAIN_REQUEST);

        self::assertFalse($this->filtres()->isEnabled(VisibleContentFilter::NAME));
    }

    #[TestDox('CT-UB-08 · un compte est filtré, avec son identifiant de propriétaire')]
    public function testCompteFiltreAvecProprietaire(): void
    {
        $this->connecter($this->compte(42));

        $this->ecouter(HttpKernelInterface::MAIN_REQUEST);

        self::assertTrue($this->filtres()->isEnabled(VisibleContentFilter::NAME));
        self::assertTrue($this->filtres()->getFilter(VisibleContentFilter::NAME)->hasParameter(VisibleContentFilter::OWNER_PARAM));
    }

    #[TestDox('CT-UB-08 · un anonyme est filtré, sans propriétaire, même après un compte')]
    public function testAnonymeFiltreSansProprietaire(): void
    {
        // Un compte d'abord, puis un anonyme : l'identifiant du premier ne doit
        // pas rester collé au second, cas d'un processus qui survivrait à la
        // requête.
        $this->connecter($this->compte(42));
        $this->ecouter(HttpKernelInterface::MAIN_REQUEST);

        static::getContainer()->get('security.token_storage')->setToken(null);
        $this->ecouter(HttpKernelInterface::MAIN_REQUEST);

        self::assertTrue($this->filtres()->isEnabled(VisibleContentFilter::NAME));
        self::assertFalse($this->filtres()->getFilter(VisibleContentFilter::NAME)->hasParameter(VisibleContentFilter::OWNER_PARAM));
    }

    #[TestDox('CT-UB-08 · une sous-requête ne touche pas à l\'état du filtre')]
    public function testSousRequeteIgnoree(): void
    {
        static::getContainer()->get('security.token_storage')->setToken(null);

        $this->ecouter(HttpKernelInterface::SUB_REQUEST);

        self::assertFalse($this->filtres()->isEnabled(VisibleContentFilter::NAME));
    }

    private function ecouter(int $type): void
    {
        static::getContainer()->get(VisibleContentFilterListener::class)(
            new RequestEvent(static::$kernel, new Request(), $type),
        );
    }

    private function filtres(): FilterCollection
    {
        return static::getContainer()->get(EntityManagerInterface::class)->getFilters();
    }

    private function connecter(User $user): void
    {
        static::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($user, 'api', $user->getRoles()));
    }

    /** Compte non administrateur muni d'un identifiant, sans passer par la base. */
    private function compte(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }
}
