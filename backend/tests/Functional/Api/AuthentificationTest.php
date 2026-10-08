<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\RefreshToken;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Authentification, règles RG-AUTH.
 *
 * Ces tests passent par les vraies routes /api/login et /api/token/refresh,
 * limiteur de tentatives compris.
 */
final class AuthentificationTest extends FunctionalTestCase
{
    private const string MOT_DE_PASSE = 'password';

    private const string MESSAGE_VERROUILLAGE = 'Trop de tentatives de connexion échouées, veuillez réessayer dans 15 minutes.';

    #[TestDox('CT-FB-11 · une connexion valide renvoie un jeton d\'accès et un jeton de rafraîchissement')]
    public function testConnexionValide(): void
    {
        $compte = UserFactory::createOne();

        $reponse = $this->connexion($this->anonyme(), $compte->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseStatusCodeSame(200);
        self::assertNotEmpty($reponse['token'] ?? null);
        self::assertNotEmpty($reponse['refresh_token'] ?? null);
    }

    #[TestDox('CT-FB-12 · un mauvais mot de passe et une adresse inconnue reçoivent la même réponse')]
    public function testIdentifiantsInvalidesIndiscernables(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();

        $mauvaisMotDePasse = $this->connexion($client, $compte->getEmail(), 'pas-le-bon');
        self::assertResponseStatusCodeSame(401);

        $adresseInconnue = $this->connexion($client, 'personne@ll-studio.test', 'pas-le-bon');
        self::assertResponseStatusCodeSame(401);

        self::assertSame('Identifiants invalides.', $mauvaisMotDePasse['message']);
        self::assertSame($mauvaisMotDePasse, $adresseInconnue);
    }

    #[TestDox('CT-FB-13 · la sixième tentative échouée est refusée en 429 avec un délai d\'attente')]
    public function testVerrouillageApresCinqEchecs(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();

        $statuts = [];
        for ($i = 1; $i <= 6; ++$i) {
            $reponse = $this->connexion($client, $compte->getEmail(), 'pas-le-bon');
            $statuts[] = $client->getResponse()->getStatusCode();
        }

        self::assertSame([401, 401, 401, 401, 401, 429], $statuts);
        self::assertResponseHeaderSame('retry-after', '900');
        self::assertSame(429, $reponse['code']);
        self::assertSame(self::MESSAGE_VERROUILLAGE, $reponse['message']);
    }

    #[TestDox('CT-FB-14 · une fois verrouillé, même le bon mot de passe est refusé')]
    public function testVerrouillageResisteAuBonMotDePasse(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->verrouiller($compte->getEmail());

        $reponse = $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseStatusCodeSame(429);
        self::assertArrayNotHasKey('token', $reponse);
    }

    #[TestDox('CT-FB-15 · le verrouillage d\'un compte n\'empêche pas un autre de se connecter')]
    public function testVerrouillageLimiteAuCompteVise(): void
    {
        $verrouille = UserFactory::createOne();
        $autre = UserFactory::createOne();
        $client = $this->verrouiller($verrouille->getEmail());

        $this->connexion($client, $autre->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseStatusCodeSame(200);
    }

    #[TestDox('CT-FB-16 · un jeton de rafraîchissement ne sert qu\'une fois')]
    public function testRafraichissementAUsageUnique(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();
        $premier = $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE)['refresh_token'];

        $renouvele = $client->request('POST', '/api/token/refresh', ['json' => ['refresh_token' => $premier]])->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertNotEmpty($renouvele['token']);
        self::assertNotSame($premier, $renouvele['refresh_token']);

        $client->request('POST', '/api/token/refresh', ['json' => ['refresh_token' => $premier]]);
        self::assertResponseStatusCodeSame(401);
    }

    #[TestDox('CT-FB-17 · un compte garde au plus cinq jetons de rafraîchissement actifs')]
    public function testCinqAppareilsAuPlus(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();

        for ($i = 1; $i <= 6; ++$i) {
            $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);
            self::assertResponseStatusCodeSame(200);
        }

        $actifs = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(RefreshToken::class)
            ->count(['username' => $compte->getEmail()]);

        // Fixé à 5 exactement plutôt qu'à « au plus 5 » : un comptage nul
        // passerait sinon pour un succès.
        self::assertSame(5, $actifs);
    }

    #[TestDox('CT-FB-18 · un jeton d\'accès expiré est refusé')]
    public function testJetonExpire(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $expire = static::getContainer()->get(JWTTokenManagerInterface::class)
            ->createFromPayload($admin, ['exp' => time() - 60]);

        $this->appelProtege($expire);

        self::assertResponseStatusCodeSame(401);
    }

    #[TestDox('CT-FB-19 · un jeton dont la signature est altérée est refusé')]
    public function testJetonAltere(): void
    {
        $jeton = $this->jeton(UserFactory::new()->admin()->create());
        $altere = substr($jeton, 0, -4).(str_ends_with($jeton, 'AAAA') ? 'BBBB' : 'AAAA');

        $this->appelProtege($altere);

        self::assertResponseStatusCodeSame(401);
    }

    #[TestDox('CT-FB-20 · aucune réponse sur les comptes ne contient de mot de passe')]
    public function testMotDePasseJamaisRenvoye(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $client = $this->connecte($admin);

        $reponses = [
            $client->request('GET', '/api/users')->toArray(),
            $client->request('GET', '/api/users/'.$admin->getId())->toArray(),
            $client->request('POST', '/api/users', ['json' => [
                'email' => 'nouveau@ll-studio.test',
                'firstName' => 'Nouveau',
                'lastName' => 'Compte',
                'plainPassword' => 'UnMotDePasse!2026',
            ]])->toArray(),
        ];
        $this->patch($client, '/api/users/'.$admin->getId(), ['plainPassword' => 'AutreMotDePasse!2026']);
        $reponses[] = $client->getResponse()->toArray();

        foreach ($reponses as $reponse) {
            $json = json_encode($reponse, \JSON_THROW_ON_ERROR);
            self::assertStringNotContainsString('"password"', $json);
            self::assertStringNotContainsString('"plainPassword"', $json);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function connexion(Client $client, string $email, string $motDePasse): array
    {
        return $client->request('POST', '/api/login', [
            'json' => ['email' => $email, 'password' => $motDePasse],
        ])->toArray(false);
    }

    /** Provoque le verrouillage d'un identifiant et rend le client utilisé. */
    private function verrouiller(string $email): Client
    {
        $client = $this->anonyme();

        for ($i = 1; $i <= 6; ++$i) {
            $this->connexion($client, $email, 'pas-le-bon');
        }

        self::assertResponseStatusCodeSame(429);

        return $client;
    }

    /** Appel d'une opération réservée à l'administrateur avec le jeton donné. */
    private function appelProtege(string $jeton): void
    {
        static::createClient()->request('GET', '/api/users', [
            'headers' => ['Authorization' => 'Bearer '.$jeton],
        ]);
    }
}
