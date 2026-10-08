<?php

declare(strict_types=1);

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Photo;
use App\Entity\User;
use App\State\PhotoUploadProcessor;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class PhotoUploadProcessorTest extends KernelTestCase
{
    #[TestDox('CT-UB-12 · une photo envoyée a pour auteur le compte connecté')]
    public function testAuteurEstLeCompteConnecte(): void
    {
        $expediteur = (new User())->setRoles(['ROLE_ADMIN']);
        static::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($expediteur, 'api', $expediteur->getRoles()));

        $persistance = $this->createStub(ProcessorInterface::class);
        $persistance->method('process')->willReturnArgument(0);
        $processeur = new PhotoUploadProcessor($persistance, static::getContainer()->get(Security::class));

        $photo = $processeur->process(new Photo(), new Post());

        self::assertSame($expediteur, $photo->getOwner());
    }
}
