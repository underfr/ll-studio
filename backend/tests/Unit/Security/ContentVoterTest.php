<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Album;
use App\Entity\Category;
use App\Entity\Photo;
use App\Entity\User;
use App\Security\Voter\ContentVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ContentVoterTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function attributsEtContenus(): iterable
    {
        foreach ([ContentVoter::EDIT, ContentVoter::DELETE] as $attribut) {
            foreach ([Photo::class, Album::class] as $classe) {
                yield $attribut.' sur '.$classe => [$attribut, $classe];
            }
        }
    }

    #[DataProvider('attributsEtContenus')]
    #[TestDox('CT-UB-05 · $attribut sur $classe : seuls l\'administrateur et le propriétaire passent')]
    public function testQuiPeutModifierOuSupprimer(string $attribut, string $classe): void
    {
        $voter = new ContentVoter();
        $proprietaire = new User();
        $contenu = (new $classe())->setOwner($proprietaire);
        $sansAuteur = new $classe();
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $contenu, [$attribut]), 'anonyme');
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->jeton($admin), $contenu, [$attribut]), 'administrateur');
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->jeton($proprietaire), $contenu, [$attribut]), 'propriétaire');
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->jeton(new User()), $contenu, [$attribut]), 'autre compte');
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->jeton(new User()), $sansAuteur, [$attribut]), 'contenu sans auteur');
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->jeton($admin), $sansAuteur, [$attribut]), 'contenu sans auteur, administrateur');
    }

    #[TestDox('CT-UB-06 · le voter s\'abstient hors de son périmètre')]
    public function testAbstentionHorsPerimetre(): void
    {
        $voter = new ContentVoter();
        $jeton = $this->jeton((new User())->setRoles(['ROLE_ADMIN']));

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($jeton, new Photo(), ['CONTENT_PUBLISH']));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($jeton, new Category(), [ContentVoter::EDIT]));
    }

    private function jeton(User $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'api', $user->getRoles());
    }
}
