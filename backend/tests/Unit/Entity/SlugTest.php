<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Album;
use App\Entity\Category;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Format des slugs de série et de catégorie, RG-ALB-02 et RG-CAT-02. On passe
 * par le vrai validateur pour vérifier la contrainte telle qu'elle est
 * déclarée sur l'entité, et non une copie de l'expression.
 */
final class SlugTest extends KernelTestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function slugs(): iterable
    {
        yield 'mots et année' => ['puy-du-fou-2024', true];
        yield 'un mot' => ['nogaro', true];
        yield 'des chiffres' => ['2024', true];
        yield 'une majuscule' => ['Puy', false];
        yield 'une espace' => ['a b', false];
        yield 'tiret initial' => ['-a', false];
        yield 'tiret final' => ['a-', false];
        yield 'double tiret' => ['a--b', false];
        yield 'accent' => ['été', false];
    }

    #[DataProvider('slugs')]
    #[TestDox('CT-UB-14 · le slug « $slug » est accepté : $valide')]
    public function testFormatDuSlug(string $slug, bool $valide): void
    {
        $validateur = static::getContainer()->get(ValidatorInterface::class);

        foreach ([Album::class, Category::class] as $classe) {
            $violations = $validateur->validatePropertyValue($classe, 'slug', $slug);

            self::assertSame($valide, 0 === \count($violations), $classe.' : '.$slug);
        }
    }
}
