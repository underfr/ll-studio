<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\State\UserPasswordHasherProcessor;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPasswordHasherProcessorTest extends TestCase
{
    #[TestDox('CT-UB-11 · le mot de passe transmis est haché puis oublié')]
    public function testMotDePasseHacheEtOublie(): void
    {
        $user = (new User())->setPlainPassword('secret-en-clair');
        $hacheur = $this->createMock(UserPasswordHasherInterface::class);
        $hacheur->expects(self::once())->method('hashPassword')->with($user, 'secret-en-clair')->willReturn('empreinte');

        (new UserPasswordHasherProcessor($this->persistance(), $hacheur))->process($user, new Patch());

        self::assertSame('empreinte', $user->getPassword());
        self::assertNull($user->getPlainPassword());
    }

    #[TestDox('CT-UB-11 · sans mot de passe transmis, l\'ancien est conservé')]
    public function testSansMotDePasseRienNeChange(): void
    {
        $user = (new User())->setPassword('ancienne-empreinte');
        $hacheur = $this->createMock(UserPasswordHasherInterface::class);
        $hacheur->expects(self::never())->method('hashPassword');

        (new UserPasswordHasherProcessor($this->persistance(), $hacheur))->process($user, new Patch());

        self::assertSame('ancienne-empreinte', $user->getPassword());
    }

    /** @return ProcessorInterface<User, User> */
    private function persistance(): ProcessorInterface
    {
        $persistance = $this->createStub(ProcessorInterface::class);
        $persistance->method('process')->willReturnArgument(0);

        return $persistance;
    }
}
