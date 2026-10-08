<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Factory\MessageContactFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Messages de contact, règles RG-MSG.
 *
 * Les messages attendus reprennent les chaînes exactes de l'entité, apostrophe
 * typographique comprise.
 */
final class MessageTest extends FunctionalTestCase
{
    private const array MESSAGE_VALIDE = [
        'name' => 'Camille Martin',
        'email' => 'camille@exemple.test',
        'subject' => 'Mariage septembre 2026',
        'message' => 'Bonjour, seriez-vous disponible pour un mariage ?',
    ];

    #[TestDox('CT-FB-70 · un visiteur envoie un message, enregistré non lu')]
    public function testEnvoiParUnVisiteur(): void
    {
        $reponse = $this->anonyme()->request('POST', '/api/messages', ['json' => self::MESSAGE_VALIDE])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertFalse($reponse['read']);
    }

    #[TestDox('CT-FB-71 · un visiteur ne peut pas envoyer un message déjà marqué lu')]
    public function testEnvoiDejaLuIgnore(): void
    {
        $reponse = $this->anonyme()->request('POST', '/api/messages', [
            'json' => [...self::MESSAGE_VALIDE, 'read' => true],
        ])->toArray();

        self::assertResponseStatusCodeSame(201);
        self::assertFalse($reponse['read']);
    }

    /**
     * @return iterable<string, array{array<string, string>, string, string}>
     */
    public static function messagesInvalides(): iterable
    {
        yield 'sans nom' => [['name' => ''], 'name', 'Merci d’indiquer votre nom.'];
        yield 'sans adresse' => [['email' => ''], 'email', 'Merci d’indiquer votre adresse e-mail.'];
        yield 'adresse invalide' => [['email' => 'pas-une-adresse'], 'email', 'Cette adresse e-mail n’est pas valide.'];
        yield 'sans sujet' => [['subject' => ''], 'subject', 'Merci d’indiquer un sujet.'];
        yield 'message vide' => [['message' => ''], 'message', 'Le message ne peut pas être vide.'];
        yield 'message de 9 caractères' => [['message' => str_repeat('a', 9)], 'message', 'Cette chaîne est trop courte. Elle doit avoir au minimum 10 caractères.'];
        yield 'message de 5001 caractères' => [['message' => str_repeat('a', 5001)], 'message', 'Cette chaîne est trop longue. Elle doit avoir au maximum 5000 caractères.'];
        yield 'nom de 101 caractères' => [['name' => str_repeat('a', 101)], 'name', 'Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.'];
        yield 'sujet de 151 caractères' => [['subject' => str_repeat('a', 151)], 'subject', 'Cette chaîne est trop longue. Elle doit avoir au maximum 150 caractères.'];
    }

    /**
     * @param array<string, string> $surcharge
     */
    #[DataProvider('messagesInvalides')]
    #[TestDox('CT-FB-72 · $_dataName : refus sur le champ $champ')]
    public function testValidation(array $surcharge, string $champ, string $message): void
    {
        $reponse = $this->anonyme()->request('POST', '/api/messages', [
            'json' => [...self::MESSAGE_VALIDE, ...$surcharge],
        ])->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($message, self::violations($reponse)[$champ] ?? []);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function longueursLimites(): iterable
    {
        yield '10 caractères' => [10];
        yield '5000 caractères' => [5000];
    }

    #[DataProvider('longueursLimites')]
    #[TestDox('CT-FB-72 · un message de $longueur caractères est accepté')]
    public function testBornesAcceptees(int $longueur): void
    {
        $this->anonyme()->request('POST', '/api/messages', [
            'json' => [...self::MESSAGE_VALIDE, 'message' => str_repeat('a', $longueur)],
        ]);

        self::assertResponseStatusCodeSame(201);
    }

    #[TestDox('CT-FB-73 · seul le marqueur « lu » se modifie, le contenu reste intact')]
    public function testSeulLeMarqueurLuSeModifie(): void
    {
        $message = MessageContactFactory::createOne(['subject' => 'Sujet d\'origine']);
        $admin = $this->connecte(UserFactory::new()->admin()->create());

        $this->patch($admin, '/api/messages/'.$message->getId(), ['read' => true, 'subject' => 'Réécrit']);
        self::assertResponseStatusCodeSame(200);

        $apres = $admin->request('GET', '/api/messages/'.$message->getId())->toArray();
        self::assertTrue($apres['read']);
        self::assertSame('Sujet d\'origine', $apres['subject']);
    }

    #[TestDox('CT-FB-74 · la boîte de réception se filtre sur les non lus, 25 par page, du plus récent au plus ancien')]
    public function testBoiteDeReception(): void
    {
        MessageContactFactory::createMany(20);
        MessageContactFactory::new()->alreadyRead()->many(10)->create();
        $admin = $this->connecte(UserFactory::new()->admin()->create());

        $nonLus = $admin->request('GET', '/api/messages?read=false')->toArray();
        self::assertSame(20, $nonLus['totalItems']);
        self::assertNotContains(true, array_column($nonLus['member'], 'read'));

        $tous = $admin->request('GET', '/api/messages')->toArray();
        self::assertSame(30, $tous['totalItems']);
        self::assertCount(25, $tous['member']);
        $dates = array_column($tous['member'], 'createdAt');
        $triees = $dates;
        rsort($triees);
        self::assertSame($triees, $dates);
    }
}
