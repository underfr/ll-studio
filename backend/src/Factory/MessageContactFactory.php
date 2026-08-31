<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\MessageContact;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<MessageContact>
 */
final class MessageContactFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return MessageContact::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
            'email' => self::faker()->safeEmail(),
            'subject' => self::faker()->sentence(4),
            'message' => self::faker()->paragraph(3),
            'read' => false,
            'createdAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-3 months', 'now')
            ),
        ];
    }

    /**
     * Message déjà consulté depuis le back-office.
     */
    public function alreadyRead(): static
    {
        return $this->with(['read' => true]);
    }
}
