<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Factory\AlbumFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Comptes, règles RG-USR.
 */
final class CompteTest extends FunctionalTestCase
{
    private Client $admin;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->connecte(UserFactory::new()->admin()->create(['email' => 'admin@ll-studio.test']));
    }

    /**
     * @return iterable<string, array{array<string, string|null>, string, string}>
     */
    public static function comptesInvalides(): iterable
    {
        yield 'adresse déjà utilisée' => [['email' => 'admin@ll-studio.test'], 'email', 'Un compte existe déjà avec cette adresse e-mail.'];
        yield 'adresse invalide' => [['email' => 'pas-une-adresse'], 'email', 'Cette valeur n\'est pas une adresse email valide.'];
        yield 'sans mot de passe' => [['plainPassword' => null], 'plainPassword', 'Cette valeur ne doit pas être vide.'];
        yield 'mot de passe de 7 caractères' => [['plainPassword' => 'Court!7'], 'plainPassword', 'Cette chaîne est trop courte. Elle doit avoir au minimum 8 caractères.'];
        yield 'sans prénom' => [['firstName' => ''], 'firstName', 'Cette valeur ne doit pas être vide.'];
    }

    /**
     * @param array<string, string|null> $surcharge
     */
    #[DataProvider('comptesInvalides')]
    #[TestDox('CT-FB-80 à 83 · $_dataName : refus sur le champ $champ')]
    public function testCreationInvalide(array $surcharge, string $champ, string $message): void
    {
        $corps = array_filter(
            [
                'email' => 'nouveau@ll-studio.test',
                'firstName' => 'Nouveau',
                'lastName' => 'Compte',
                'plainPassword' => 'UnMotDePasse!2026',
                ...$surcharge,
            ],
            static fn (?string $valeur): bool => null !== $valeur,
        );

        $reponse = $this->admin->request('POST', '/api/users', ['json' => $corps])->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($message, self::violations($reponse)[$champ] ?? []);
    }

    #[TestDox('CT-FB-82 · un nouveau mot de passe remplace l\'ancien ; sans mot de passe, rien ne change')]
    public function testChangementDeMotDePasse(): void
    {
        $compte = UserFactory::createOne(['email' => 'compte@ll-studio.test', 'password' => 'Ancien!2026']);
        $client = $this->connecte($compte);

        $this->patch($client, '/api/users/'.$compte->getId(), ['firstName' => 'Inchangé']);
        self::assertResponseStatusCodeSame(200);
        self::assertSame(200, $this->connexion('compte@ll-studio.test', 'Ancien!2026'));

        $this->patch($client, '/api/users/'.$compte->getId(), ['plainPassword' => 'Nouveau!2026']);
        self::assertResponseStatusCodeSame(200);
        self::assertSame(401, $this->connexion('compte@ll-studio.test', 'Ancien!2026'));
        self::assertSame(200, $this->connexion('compte@ll-studio.test', 'Nouveau!2026'));
    }

    #[TestDox('CT-FB-83 · les comptes sont triés par nom, avec leur nom complet')]
    public function testListeTrieeParNom(): void
    {
        UserFactory::createOne(['firstName' => 'Zoé', 'lastName' => 'Bernard']);
        UserFactory::createOne(['firstName' => 'Adam', 'lastName' => 'Moreau']);

        $comptes = $this->admin->request('GET', '/api/users')->toArray()['member'];

        $noms = array_column($comptes, 'lastName');
        $tries = $noms;
        sort($tries);
        self::assertSame($tries, $noms);
        self::assertContains('Zoé Bernard', array_column($comptes, 'fullName'));
    }

    #[TestDox('CT-FB-84 · supprimer un compte laisse ses photos et ses séries, sans auteur')]
    public function testSuppressionSansPerteDeContenu(): void
    {
        $auteur = UserFactory::createOne();
        $photo = PhotoFactory::createOne(['owner' => $auteur]);
        $serie = AlbumFactory::createOne(['owner' => $auteur]);

        $this->admin->request('DELETE', '/api/users/'.$auteur->getId());
        self::assertResponseStatusCodeSame(204);

        $photoApres = $this->admin->request('GET', '/api/photos/'.$photo->getId())->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertNull($photoApres['owner'] ?? null);

        $serieApres = $this->admin->request('GET', '/api/albums/'.$serie->getId())->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertNull($serieApres['owner'] ?? null);
    }

    private function connexion(string $email, string $motDePasse): int
    {
        $client = $this->anonyme();
        $client->request('POST', '/api/login', ['json' => ['email' => $email, 'password' => $motDePasse]]);

        return $client->getResponse()->getStatusCode();
    }
}
