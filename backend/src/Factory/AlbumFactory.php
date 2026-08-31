<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Album;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Album>
 */
final class AlbumFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Album::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        $title = self::faker()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => CategoryFactory::slugify($title),
            'description' => self::faker()->paragraph(),
            'visible' => true,
            'createdAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-2 years', 'now')
            ),
            'category' => CategoryFactory::new(),
        ];
    }

    public function titled(string $title): static
    {
        return $this->with(['title' => $title, 'slug' => CategoryFactory::slugify($title)]);
    }
}
