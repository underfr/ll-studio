<?php

declare(strict_types=1);

namespace App\Tests\Unit\Doctrine;

use App\Doctrine\Filter\VisibleContentFilter;
use App\Entity\Album;
use App\Entity\Category;
use App\Entity\Photo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * La clause SQL produite par le filtre de visibilité. Le filtre a besoin des
 * vraies métadonnées Doctrine et de la connexion pour échapper ses paramètres :
 * d'où le noyau, sans requête HTTP.
 */
final class VisibleContentFilterTest extends KernelTestCase
{
    #[TestDox('CT-UB-07 · le filtre ne contraint que les photos et les séries, propriétaire compris')]
    public function testClauseDeVisibilite(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $filtre = new VisibleContentFilter($em);

        self::assertSame('', $filtre->addFilterConstraint($em->getClassMetadata(Category::class), 'c0_'));
        self::assertSame('p0_.visible = true', $filtre->addFilterConstraint($em->getClassMetadata(Photo::class), 'p0_'));
        self::assertSame('a0_.visible = true', $filtre->addFilterConstraint($em->getClassMetadata(Album::class), 'a0_'));

        $filtre->setParameter(VisibleContentFilter::OWNER_PARAM, 7, Types::INTEGER);

        self::assertMatchesRegularExpression(
            "/^\\(p0_\\.visible = true OR p0_\\.owner_id = '?7'?\\)$/",
            $filtre->addFilterConstraint($em->getClassMetadata(Photo::class), 'p0_'),
        );
    }

    #[TestDox('CT-UB-07 · l\'identifiant du propriétaire est échappé avant d\'entrer dans le SQL')]
    public function testIdentifiantEchappe(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $filtre = new VisibleContentFilter($em);
        $filtre->setParameter(VisibleContentFilter::OWNER_PARAM, "1) OR (1=1", Types::STRING);

        $clause = $filtre->addFilterConstraint($em->getClassMetadata(Photo::class), 'p0_');

        self::assertStringContainsString("'1) OR (1=1'", $clause);
    }
}
