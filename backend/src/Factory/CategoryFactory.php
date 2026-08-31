<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Category;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Category>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Category::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        $name = self::faker()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => self::slugify($name),
        ];
    }

    /**
     * Crée une catégorie dont le slug découle du nom.
     */
    public function named(string $name): static
    {
        return $this->with(['name' => $name, 'slug' => self::slugify($name)]);
    }

    public static function slugify(string $value): string
    {
        return (new AsciiSlugger('fr'))->slug($value)->lower()->toString();
    }
}
