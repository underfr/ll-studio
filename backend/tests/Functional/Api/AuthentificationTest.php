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
use Symfony\Component\BrowserKit\Cookie as CookieNavigateur;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Authentification, règles RG-AUTH.
 *
 * Ces tests passent par les vraies routes /api/login, /api/token/refresh et
 * /api/token/invalidate, limiteur de tentatives compris. Le jeton de
 * rafraîchissement circule par cookie (issue #62) : le client de test le
 * conserve et le renvoie comme le ferait un navigateur.
 */
final class AuthentificationTest extends FunctionalTestCase
{
    private const string MOT_DE_PASSE = 'password';

    private const string MESSAGE_VERROUILLAGE = 'Trop de tentatives de connexion échouées, veuillez réessayer dans 15 minutes.';

    #[TestDox('CT-FB-11 · une connexion valide renvoie un jeton d\'accès et un jeton de rafraîchissement')]
    public function testConnexionValide(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();

        $reponse = $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseStatusCodeSame(200);
        self::assertNotEmpty($reponse['token'] ?? null);
        self::assertNotEmpty($this->jetonDeRafraichissement($client));
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
        $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);
        $premier = $this->jetonDeRafraichissement($client);

        // Sans corps : le cookie suffit.
        $renouvele = $client->request('POST', '/api/token/refresh')->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertNotEmpty($renouvele['token']);
        self::assertNotSame($premier, $this->jetonDeRafraichissement($client));

        $this->presenterJeton($client, $premier);
        $client->request('POST', '/api/token/refresh');
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

    #[TestDox('CT-FB-26 · le jeton de rafraîchissement voyage dans un cookie HttpOnly, SameSite=Strict, limité à /api/token')]
    public function testJetonDeRafraichissementEnCookie(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();

        $reponse = $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseStatusCodeSame(200);
        self::assertArrayNotHasKey('refresh_token', $reponse);

        $cookie = $this->cookieRecu($client);
        self::assertNotNull($cookie);
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
        self::assertSame('/api/token', $cookie->getPath());
        // Secure est désactivé en test, qui tourne en HTTP ; la production
        // le garde (RG-AUTH-11).
        self::assertFalse($cookie->isSecure());
    }

    #[TestDox('CT-FB-27 · la déconnexion révoque le jeton de rafraîchissement et efface le cookie')]
    public function testDeconnexion(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->anonyme();
        $this->connexion($client, $compte->getEmail(), self::MOT_DE_PASSE);
        $jeton = $this->jetonDeRafraichissement($client);

        $client->request('POST', '/api/token/invalidate');
        self::assertResponseStatusCodeSame(200);
        self::assertTrue($this->cookieRecu($client)?->isCleared());

        $this->presenterJeton($client, $jeton);
        $client->request('POST', '/api/token/refresh');
        self::assertResponseStatusCodeSame(401);

        $this->anonyme()->request('POST', '/api/token/invalidate');
        self::assertResponseStatusCodeSame(400);

        $this->anonyme()->request('GET', '/api/token/invalidate');
        self::assertResponseStatusCodeSame(405);
    }

    #[TestDox('CT-FB-28 · /api/me renvoie le compte du porteur du jeton sous son IRI canonique')]
    public function testCompteCourant(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $moi = $this->connecte($admin)->request('GET', '/api/me')->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('/api/users/'.$admin->getId(), $moi['@id']);
        self::assertSame($admin->getEmail(), $moi['email']);
        self::assertContains('ROLE_ADMIN', $moi['roles']);

        $compte = UserFactory::createOne();
        $lui = $this->connecte($compte)->request('GET', '/api/me')->toArray();
        self::assertSame('/api/users/'.$compte->getId(), $lui['@id']);
        self::assertArrayNotHasKey('roles', $lui);

        $this->anonyme()->request('GET', '/api/me');
        self::assertResponseStatusCodeSame(401);
    }

    #[TestDox('CT-FB-29 · les requêtes avec identifiants ne sont autorisées qu\'aux origines prévues')]
    public function testCorsAvecIdentifiants(): void
    {
        $preflight = fn (string $origine) => $this->anonyme()->request('OPTIONS', '/api/token/refresh', [
            'headers' => ['Origin' => $origine, 'Access-Control-Request-Method' => 'POST'],
        ]);

        $preflight('http://localhost:3000');
        self::assertResponseHeaderSame('access-control-allow-origin', 'http://localhost:3000');
        self::assertResponseHeaderSame('access-control-allow-credentials', 'true');

        $preflight('https://site-tiers.example');
        self::assertResponseNotHasHeader('access-control-allow-origin');
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

    /** Cookie du jeton de rafraîchissement posé par la dernière réponse. */
    private function cookieRecu(Client $client): ?Cookie
    {
        foreach ($client->getKernelBrowser()->getResponse()->headers->getCookies() as $cookie) {
            if ('refresh_token' === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    /** Jeton de rafraîchissement que le client renverrait, comme un navigateur. */
    private function jetonDeRafraichissement(Client $client): ?string
    {
        return $client->getKernelBrowser()->getCookieJar()->get('refresh_token', '/api/token')?->getValue();
    }

    /** Remplace le cookie du client, pour rejouer un ancien jeton. */
    private function presenterJeton(Client $client, string $jeton): void
    {
        $client->getKernelBrowser()->getCookieJar()->set(new CookieNavigateur('refresh_token', $jeton, null, '/api/token'));
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
