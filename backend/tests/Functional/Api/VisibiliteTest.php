<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Factory\AlbumFactory;
use App\Factory\CategoryFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Visibilité des contenus, règles RG-VIS.
 *
 * Ces règles ne se vérifient qu'au niveau fonctionnel : le filtre de
 * visibilité n'est activé que pendant une requête HTTP. Interroger Doctrine
 * directement montrerait le contenu masqué et ferait passer le test à tort.
 */
final class VisibiliteTest extends FunctionalTestCase
{
    #[TestDox('CT-FB-01 · un anonyme reçoit 404 sur une photo masquée')]
    public function testAnonymeNeVoitPasUnePhotoMasquee(): void
    {
        $photo = PhotoFactory::new()->hidden()->create();

        $this->anonyme()->request('GET', '/api/photos/'.$photo->getId());

        self::assertResponseStatusCodeSame(404);
    }

    #[TestDox('CT-FB-02 · la collection de photos ne contient que les photos publiées')]
    public function testCollectionDePhotosSansLesMasquees(): void
    {
        PhotoFactory::createMany(2);
        $masquee = PhotoFactory::new()->hidden()->create();

        $reponse = $this->anonyme()->request('GET', '/api/photos')->toArray();

        self::assertSame(2, $reponse['totalItems']);
        self::assertCount(2, $reponse['member']);
        self::assertNotContains('/api/photos/'.$masquee->getId(), self::iris($reponse));
    }

    #[TestDox('CT-FB-03 · une série ne laisse sortir ni ne compte sa photo masquée')]
    public function testSerieSansSaPhotoMasquee(): void
    {
        $visibles = PhotoFactory::createMany(2);
        $masquee = PhotoFactory::new()->hidden()->create();
        $serie = AlbumFactory::createOne(['photos' => [...$visibles, $masquee]]);

        $reponse = $this->anonyme()->request('GET', '/api/albums/'.$serie->getId())->toArray();

        self::assertNotContains('/api/photos/'.$masquee->getId(), self::iris($reponse, 'photos'));
        self::assertCount(2, $reponse['photos']);
        self::assertSame(2, $reponse['photoCount']);
    }

    #[TestDox('CT-FB-04 · une couverture masquée est renvoyée nulle dans la collection de séries')]
    public function testCouvertureMasqueeNulle(): void
    {
        $couverture = PhotoFactory::new()->hidden()->create();
        AlbumFactory::createOne(['coverPhoto' => $couverture, 'photos' => [$couverture]]);

        $reponse = $this->anonyme()->request('GET', '/api/albums')->toArray();

        self::assertCount(1, $reponse['member']);
        self::assertNull($reponse['member'][0]['coverPhoto'] ?? null);
    }

    #[TestDox('CT-FB-05 · le compteur d\'une catégorie ignore ses photos masquées')]
    public function testCompteurDeCategorieSansLesMasquees(): void
    {
        $categorie = CategoryFactory::new()->named('Astronomie')->create();
        PhotoFactory::new()->hidden()->create(['category' => $categorie]);

        $reponse = $this->anonyme()->request('GET', '/api/categories?slug=astronomie')->toArray();

        self::assertSame(0, $reponse['member'][0]['photoCount']);
    }

    #[TestDox('CT-FB-06 · une série masquée est introuvable, même au travers du filtre de photos')]
    public function testSerieMasqueeIntrouvable(): void
    {
        $photo = PhotoFactory::createOne();
        $serie = AlbumFactory::createOne(['visible' => false, 'photos' => [$photo]]);
        $client = $this->anonyme();

        $client->request('GET', '/api/albums/'.$serie->getId());
        self::assertResponseStatusCodeSame(404);

        $reponse = $client->request('GET', '/api/photos?albums.slug='.$serie->getSlug())->toArray();
        self::assertSame(0, $reponse['totalItems']);
    }

    #[TestDox('CT-FB-07 · le filtre visible=false ne permet pas de lister les photos masquées')]
    public function testFiltreVisibleNeContournePasLaRegle(): void
    {
        PhotoFactory::createOne();
        PhotoFactory::new()->hidden()->create();

        $reponse = $this->anonyme()->request('GET', '/api/photos?visible=false')->toArray();

        self::assertSame(0, $reponse['totalItems']);
    }

    #[TestDox('CT-FB-08 · un administrateur voit tout le contenu masqué, compteurs compris')]
    public function testAdministrateurVoitTout(): void
    {
        $categorie = CategoryFactory::new()->named('Astronomie')->create();
        $visible = PhotoFactory::createOne(['category' => $categorie]);
        $masquee = PhotoFactory::new()->hidden()->create(['category' => $categorie]);
        $serie = AlbumFactory::createOne(['coverPhoto' => $masquee, 'photos' => [$visible, $masquee]]);
        $serieMasquee = AlbumFactory::createOne(['visible' => false, 'photos' => [$visible]]);
        $client = $this->connecte(UserFactory::new()->admin()->create());

        $client->request('GET', '/api/photos/'.$masquee->getId());
        self::assertResponseStatusCodeSame(200);

        self::assertSame(2, $client->request('GET', '/api/photos')->toArray()['totalItems']);

        $detail = $client->request('GET', '/api/albums/'.$serie->getId())->toArray();
        self::assertContains('/api/photos/'.$masquee->getId(), self::iris($detail, 'photos'));
        self::assertSame(2, $detail['photoCount']);
        self::assertSame('/api/photos/'.$masquee->getId(), $detail['coverPhoto']['@id']);

        self::assertSame(2, $client->request('GET', '/api/categories?slug=astronomie')->toArray()['member'][0]['photoCount']);

        $client->request('GET', '/api/albums/'.$serieMasquee->getId());
        self::assertResponseStatusCodeSame(200);
        self::assertSame(1, $client->request('GET', '/api/photos?albums.slug='.$serieMasquee->getSlug())->toArray()['totalItems']);
    }

    #[TestDox('CT-FB-09 · un compte voit son contenu masqué, pas celui des autres')]
    public function testProprietaireVoitSonContenuMasque(): void
    {
        $proprietaire = UserFactory::createOne();
        $tiers = UserFactory::createOne();
        $masquee = PhotoFactory::new()->hidden()->create(['owner' => $proprietaire]);
        $serie = AlbumFactory::createOne(['photos' => [$masquee]]);

        $client = $this->connecte($proprietaire);
        $client->request('GET', '/api/photos/'.$masquee->getId());
        self::assertResponseStatusCodeSame(200);
        self::assertContains(
            '/api/photos/'.$masquee->getId(),
            self::iris($client->request('GET', '/api/albums/'.$serie->getId())->toArray(), 'photos'),
        );

        $this->connecte($tiers)->request('GET', '/api/photos/'.$masquee->getId());
        self::assertResponseStatusCodeSame(404);
    }

    #[TestDox('CT-FB-10 · les réponses varient selon l\'en-tête Authorization')]
    public function testReponsesVarientSelonLeJeton(): void
    {
        $reponse = $this->anonyme()->request('GET', '/api/photos');

        $vary = implode(',', $reponse->getHeaders()['vary'] ?? []);
        self::assertStringContainsStringIgnoringCase('Authorization', $vary);
    }
}
