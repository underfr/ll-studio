<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Photo;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Photo>
 */
final class PhotoFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Photo::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->unique()->sentence(4),
            'description' => self::faker()->paragraph(),
            'alt' => self::faker()->sentence(10),
            'filePath' => self::faker()->unique()->slug(3).'.jpg',
            'visible' => true,
            'createdAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-2 years', 'now')
            ),
            'category' => CategoryFactory::new(),
        ];
    }

    /**
     * Photo masquée du site public.
     */
    public function hidden(): static
    {
        return $this->with(['visible' => false]);
    }
}
