<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Album;
use App\Entity\Category;
use App\Entity\MessageContact;
use App\Entity\Photo;
use App\Entity\User;
use App\Factory\AlbumFactory;
use App\Factory\CategoryFactory;
use App\Factory\MessageContactFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Droits d'accès, règles RG-DRT.
 *
 * La matrice de RG-DRT-01 est rejouée case par case : une ressource, une
 * opération, un profil, un statut attendu. Chaque case part d'une base neuve,
 * si bien qu'une suppression ne fausse pas la case suivante.
 */
final class DroitsTest extends FunctionalTestCase
{
    private const array PROFILS = ['anonyme', 'compte', 'proprietaire', 'admin'];

    /**
     * Opération, puis statut attendu pour chaque profil dans l'ordre de
     * PROFILS. Reproduit le tableau RG-DRT-01 des règles de gestion.
     */
    private const array MATRICE = [
        'lister les photos' => ['GET photos', [200, 200, 200, 200]],
        'consulter une photo' => ['GET photo', [200, 200, 200, 200]],
        'créer une photo' => ['POST photo', [401, 403, 403, 201]],
        'modifier une photo' => ['PATCH photo', [401, 403, 200, 200]],
        'supprimer une photo' => ['DELETE photo', [401, 403, 204, 204]],
        'lister les séries' => ['GET series', [200, 200, 200, 200]],
        'consulter une série' => ['GET serie', [200, 200, 200, 200]],
        'créer une série' => ['POST serie', [401, 403, 403, 201]],
        'modifier une série' => ['PATCH serie', [401, 403, 200, 200]],
        'supprimer une série' => ['DELETE serie', [401, 403, 204, 204]],
        'lister les catégories' => ['GET categories', [200, 200, 200, 200]],
        'consulter une catégorie' => ['GET categorie', [200, 200, 200, 200]],
        'créer une catégorie' => ['POST categorie', [401, 403, 403, 201]],
        'modifier une catégorie' => ['PATCH categorie', [401, 403, 403, 200]],
        'supprimer une catégorie' => ['DELETE categorie', [401, 403, 403, 204]],
        'envoyer un message' => ['POST message', [201, 201, 201, 201]],
        'lister les messages' => ['GET messages', [401, 403, 403, 200]],
        'consulter un message' => ['GET message', [401, 403, 403, 200]],
        'marquer un message lu' => ['PATCH message', [401, 403, 403, 200]],
        'supprimer un message' => ['DELETE message', [401, 403, 403, 204]],
        'lister les comptes' => ['GET comptes', [401, 403, 403, 200]],
        'consulter son compte' => ['GET compte-soi', [401, 200, 200, 200]],
        'consulter le compte d\'un autre' => ['GET compte-autre', [401, 403, 403, 200]],
        'créer un compte' => ['POST compte', [401, 403, 403, 201]],
        'modifier son compte' => ['PATCH compte-soi', [401, 200, 200, 200]],
        'modifier le compte d\'un autre' => ['PATCH compte-autre', [401, 403, 403, 200]],
        'supprimer un compte' => ['DELETE compte-autre', [401, 403, 403, 204]],
    ];

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function matrice(): iterable
    {
        foreach (self::MATRICE as $libelle => [$operation, $attendus]) {
            foreach (self::PROFILS as $rang => $profil) {
                yield $libelle.', '.$profil => [$profil, $operation, $attendus[$rang]];
            }
        }
    }

    #[DataProvider('matrice')]
    #[TestDox('CT-FB-21 · $operation par le profil $profil répond $attendu')]
    public function testMatriceDesDroits(string $profil, string $operation, int $attendu): void
    {
        $monde = $this->monde();
        $client = 'anonyme' === $profil ? $this->anonyme() : $this->connecte($monde[$profil]);

        [$methode, $cible] = explode(' ', $operation);
        $this->executer($client, $methode, $cible, $monde, $profil);

        self::assertResponseStatusCodeSame($attendu);
    }

    #[TestDox('CT-FB-22 · modifier ou supprimer le contenu masqué d\'un autre répond 404, pas 403')]
    public function testContenuMasqueDUnAutreIntrouvable(): void
    {
        $masquee = PhotoFactory::new()->hidden()->create(['owner' => UserFactory::createOne()]);
        $client = $this->connecte(UserFactory::createOne());

        $this->patch($client, '/api/photos/'.$masquee->getId(), ['title' => 'Détournée']);
        self::assertResponseStatusCodeSame(404);

        $client->request('DELETE', '/api/photos/'.$masquee->getId());
        self::assertResponseStatusCodeSame(404);
    }

    #[TestDox('CT-FB-23 · un compte ne peut ni lire ni s\'attribuer de rôle')]
    public function testCompteNePeutPasSAttribuerDeRole(): void
    {
        $compte = UserFactory::createOne();
        $client = $this->connecte($compte);

        $this->patch($client, '/api/users/'.$compte->getId(), ['roles' => ['ROLE_ADMIN'], 'firstName' => 'Ambitieux']);
        self::assertResponseIsSuccessful();

        $soi = $client->request('GET', '/api/users/'.$compte->getId())->toArray();
        self::assertSame('Ambitieux', $soi['firstName']);
        self::assertArrayNotHasKey('roles', $soi);

        $vuParLAdmin = $this->connecte(UserFactory::new()->admin()->create())
            ->request('GET', '/api/users/'.$compte->getId())->toArray();
        self::assertNotContains('ROLE_ADMIN', $vuParLAdmin['roles']);
    }

    #[TestDox('CT-FB-24 · un rôle inconnu ou en double est refusé')]
    public function testRolesInvalides(): void
    {
        $client = $this->connecte(UserFactory::new()->admin()->create());

        foreach ([['ROLE_SUPER_ADMIN'], ['ROLE_ADMIN', 'ROLE_ADMIN']] as $rang => $roles) {
            $client->request('POST', '/api/users', ['json' => [
                'email' => 'roles-'.$rang.'@ll-studio.test',
                'firstName' => 'Test',
                'lastName' => 'Roles',
                'plainPassword' => 'UnMotDePasse!2026',
                'roles' => $roles,
            ]]);

            self::assertResponseStatusCodeSame(422);
        }
    }

    #[TestDox('CT-FB-25 · l\'auteur d\'une photo est son expéditeur, quoi qu\'en dise la requête')]
    public function testAuteurDeduitDuJeton(): void
    {
        $admin = UserFactory::new()->admin()->create();
        $usurpe = UserFactory::createOne();
        $client = $this->connecte($admin);

        $this->envoyerPhoto($client, [
            'title' => 'Envoi',
            'alt' => 'Envoi',
            'category' => '/api/categories/'.CategoryFactory::createOne()->getId(),
            'owner' => '/api/users/'.$usurpe->getId(),
        ], $this->image('valide.jpg'));
        self::assertResponseStatusCodeSame(201);

        $creee = $client->getResponse()->toArray();
        $detail = $client->request('GET', '/api/photos/'.$creee['id'])->toArray();

        self::assertSame('/api/users/'.$admin->getId(), $detail['owner']);
    }

    /**
     * Comptes et contenus d'une case de la matrice.
     *
     * @return array{compte: User, proprietaire: User, admin: User, tiers: User, photo: Photo, serie: Album, categorie: Category, categorieLibre: Category, message: MessageContact}
     */
    private function monde(): array
    {
        $proprietaire = UserFactory::createOne();
        $categorie = CategoryFactory::createOne();

        return [
            'compte' => UserFactory::createOne(),
            'proprietaire' => $proprietaire,
            'admin' => UserFactory::new()->admin()->create(),
            'tiers' => UserFactory::createOne(),
            'photo' => PhotoFactory::createOne(['owner' => $proprietaire, 'category' => $categorie]),
            'serie' => AlbumFactory::createOne(['owner' => $proprietaire, 'category' => $categorie]),
            'categorie' => $categorie,
            // Sans photo ni série rattachée : seule une catégorie libre peut
            // être supprimée (RG-CAT-04).
            'categorieLibre' => CategoryFactory::createOne(),
            'message' => MessageContactFactory::createOne(),
        ];
    }

    /**
     * @param array<string, object> $monde
     */
    private function executer(Client $client, string $methode, string $cible, array $monde, string $profil): void
    {
        // « Son » compte : celui du profil connecté. L'anonyme n'en a pas, on
        // vise alors celui du propriétaire.
        $soi = $monde['anonyme' === $profil ? 'proprietaire' : $profil];
        $categorieIri = '/api/categories/'.$monde['categorie']->getId();

        $url = match ($cible) {
            'photos' => '/api/photos',
            'photo' => '/api/photos/'.$monde['photo']->getId(),
            'series' => '/api/albums',
            'serie' => 'POST' === $methode ? '/api/albums' : '/api/albums/'.$monde['serie']->getId(),
            'categories' => '/api/categories',
            'categorie' => match ($methode) {
                'POST' => '/api/categories',
                'DELETE' => '/api/categories/'.$monde['categorieLibre']->getId(),
                default => '/api/categories/'.$monde['categorie']->getId(),
            },
            'message' => 'POST' === $methode ? '/api/messages' : '/api/messages/'.$monde['message']->getId(),
            'messages' => '/api/messages',
            'comptes' => '/api/users',
            'compte' => '/api/users',
            'compte-soi' => '/api/users/'.$soi->getId(),
            'compte-autre' => '/api/users/'.$monde['tiers']->getId(),
        };

        if ('POST' === $methode && 'photo' === $cible) {
            $this->envoyerPhoto($client, ['title' => 'Nouvelle', 'alt' => 'Nouvelle', 'category' => $categorieIri], $this->image('valide.jpg'));

            return;
        }

        $corps = match ($methode.' '.$cible) {
            'POST serie' => ['title' => 'Nouvelle série', 'slug' => 'nouvelle-serie', 'category' => $categorieIri],
            'POST categorie' => ['name' => 'Nouvelle', 'slug' => 'nouvelle'],
            'POST message' => ['name' => 'Visiteur', 'email' => 'visiteur@exemple.test', 'subject' => 'Bonjour', 'message' => 'Un message assez long.'],
            'POST compte' => ['email' => 'cree@ll-studio.test', 'firstName' => 'Créé', 'lastName' => 'Compte', 'plainPassword' => 'UnMotDePasse!2026'],
            'PATCH photo', 'PATCH serie' => ['title' => 'Titre modifié'],
            'PATCH categorie' => ['name' => 'Renommée'],
            'PATCH message' => ['read' => true],
            'PATCH compte-soi', 'PATCH compte-autre' => ['firstName' => 'Modifié'],
            default => null,
        };

        match ($methode) {
            'PATCH' => $this->patch($client, $url, $corps),
            'POST' => $client->request('POST', $url, ['json' => $corps]),
            default => $client->request($methode, $url),
        };
    }
}
