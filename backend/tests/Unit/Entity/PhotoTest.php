<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Photo;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

final class PhotoTest extends TestCase
{
    #[TestDox('CT-UB-13 · recevoir un fichier met à jour la date de modification')]
    public function testNouveauFichierDateLaModification(): void
    {
        $photo = new Photo();
        self::assertNull($photo->getUpdatedAt());

        $photo->setImageFile(new File(__FILE__));

        self::assertNotNull($photo->getUpdatedAt());
    }
}
