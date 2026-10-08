<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Category;
use App\Factory\AlbumFactory;
use App\Factory\CategoryFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Séries, règles RG-ALB.
 */
final class AlbumTest extends FunctionalTestCase
{
    private Client $admin;

    private Category $categorie;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->categorie = CategoryFactory::createOne();
        $this->admin = $this->connecte(UserFactory::new()->admin()->create());
    }

    #[TestDox('CT-FB-50 · une série valide est créée')]
    public function testCreationValide(): void
    {
        $this->creer([]);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function seriesInvalides(): iterable
    {
        yield 'slug avec majuscules et espaces' => [['slug' => 'Puy Du Fou'], 'slug', 'Le slug ne peut contenir que des minuscules, des chiffres et des tirets.'];
        yield 'slug déjà utilisé' => [['slug' => 'deja-pris'], 'slug', 'Ce slug est déjà utilisé par un autre album.'];
        yield 'sans catégorie' => [['category' => null], 'category', 'Un album doit appartenir à une catégorie.'];
        yield 'titre de 121 caractères' => [['title' => str_repeat('a', 121)], 'title', 'Cette chaîne est trop longue. Elle doit avoir au maximum 120 caractères.'];
    }

    /**
     * @param array<string, mixed> $surcharge
     */
    #[DataProvider('seriesInvalides')]
    #[TestDox('CT-FB-50 · $_dataName : refus sur le champ $champ')]
    public function testCreationInvalide(array $surcharge, string $champ, string $message): void
    {
        AlbumFactory::createOne(['slug' => 'deja-pris']);

        $reponse = $this->creer($surcharge);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($message, self::violations($reponse)[$champ] ?? []);
    }

    #[TestDox('CT-FB-51 · transmettre les photos remplace la composition, sans la compléter')]
    public function testCompositionRemplacee(): void
    {
        [$une, $deux, $trois] = PhotoFactory::createMany(3);
        $serie = AlbumFactory::createOne(['photos' => [$une, $deux]]);

        $this->patch($this->admin, '/api/albums/'.$serie->getId(), ['photos' => ['/api/photos/'.$trois->getId()]]);
        self::assertResponseStatusCodeSame(200);

        $detail = $this->admin->request('GET', '/api/albums/'.$serie->getId())->toArray();
        self::assertSame(['/api/photos/'.$trois->getId()], self::iris($detail, 'photos'));
    }

    #[TestDox('CT-FB-52 · supprimer une série ne supprime pas ses photos')]
    public function testSuppressionSansPerteDePhotos(): void
    {
        $photo = PhotoFactory::createOne();
        $serie = AlbumFactory::createOne(['photos' => [$photo]]);

        $this->admin->request('DELETE', '/api/albums/'.$serie->getId());
        self::assertResponseStatusCodeSame(204);

        $this->admin->request('GET', '/api/photos/'.$photo->getId());
        self::assertResponseStatusCodeSame(200);
    }

    #[TestDox('CT-FB-53 · une photo peut figurer dans deux séries')]
    public function testPhotoDansDeuxSeries(): void
    {
        $photo = PhotoFactory::createOne();
        $premiere = AlbumFactory::createOne(['photos' => [$photo]]);
        $seconde = AlbumFactory::createOne(['photos' => [$photo]]);
        $iri = '/api/photos/'.$photo->getId();

        foreach ([$premiere, $seconde] as $serie) {
            self::assertContains($iri, self::iris($this->admin->request('GET', '/api/albums/'.$serie->getId())->toArray(), 'photos'));
        }
        self::assertCount(2, $this->admin->request('GET', $iri)->toArray()['albums']);
    }

    #[TestDox('CT-FB-54 · la liste des photos n\'apparaît qu\'au détail d\'une série')]
    public function testPhotosAuDetailSeulement(): void
    {
        $serie = AlbumFactory::createOne(['photos' => PhotoFactory::createMany(2)]);

        $element = $this->admin->request('GET', '/api/albums')->toArray()['member'][0];
        $detail = $this->admin->request('GET', '/api/albums/'.$serie->getId())->toArray();

        self::assertArrayNotHasKey('photos', $element);
        self::assertCount(2, $detail['photos']);
        self::assertSame(2, $element['photoCount']);
    }

    #[TestDox('CT-FB-55 · 12 séries par page, de la plus récente à la plus ancienne')]
    public function testPagination(): void
    {
        AlbumFactory::createMany(15);

        $page = $this->anonyme()->request('GET', '/api/albums')->toArray();

        self::assertSame(15, $page['totalItems']);
        self::assertCount(12, $page['member']);
        $dates = array_column($page['member'], 'createdAt');
        $triees = $dates;
        rsort($triees);
        self::assertSame($triees, $dates);
    }

    #[TestDox('CT-FB-56 · une série peut se passer de couverture, y compris quand sa couverture est supprimée')]
    public function testCouvertureFacultative(): void
    {
        $sansCouverture = $this->creer([]);
        self::assertResponseStatusCodeSame(201);
        self::assertNull($sansCouverture['coverPhoto'] ?? null);

        $couverture = PhotoFactory::createOne();
        $serie = AlbumFactory::createOne(['coverPhoto' => $couverture, 'photos' => [$couverture]]);

        $this->admin->request('DELETE', '/api/photos/'.$couverture->getId());
        self::assertResponseStatusCodeSame(204);

        $apres = $this->admin->request('GET', '/api/albums/'.$serie->getId())->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertNull($apres['coverPhoto'] ?? null);
    }

    #[TestDox('CT-FB-57 · chaque filtre et chaque tri ne renvoie que ce qu\'il doit')]
    public function testFiltresEtTris(): void
    {
        $voiture = CategoryFactory::new()->named('Voiture')->create();
        $nogaro = AlbumFactory::createOne(['title' => 'Nogaro 2024', 'slug' => 'nogaro-2024', 'category' => $voiture, 'createdAt' => new \DateTimeImmutable('2024-05-11')]);
        $puy = AlbumFactory::createOne(['title' => 'Puy du Fou 2024', 'slug' => 'puy-du-fou-2024', 'category' => $this->categorie, 'createdAt' => new \DateTimeImmutable('2024-08-18')]);
        $masquee = AlbumFactory::createOne(['title' => 'Brouillon', 'slug' => 'brouillon', 'visible' => false, 'createdAt' => new \DateTimeImmutable('2023-01-01')]);

        $ids = fn (string $requete): array => array_column(
            $this->admin->request('GET', '/api/albums'.$requete)->toArray()['member'],
            'id',
        );

        self::assertSame([$nogaro->getId()], $ids('?slug=nogaro-2024'));
        self::assertSame([$nogaro->getId()], $ids('?category.slug=voiture'));
        self::assertSame([$puy->getId()], $ids('?title=FOU'));
        self::assertSame([$masquee->getId()], $ids('?visible=false'));
        self::assertSame([$puy->getId(), $nogaro->getId(), $masquee->getId()], $ids(''));
        self::assertSame([$masquee->getId(), $nogaro->getId(), $puy->getId()], $ids('?order[createdAt]=asc'));
        self::assertSame([$masquee->getId(), $nogaro->getId(), $puy->getId()], $ids('?order[title]=asc'));
    }

    /**
     * @param array<string, mixed> $surcharge
     *
     * @return array<string, mixed>
     */
    private function creer(array $surcharge): array
    {
        $corps = array_filter(
            [
                'title' => 'Nouvelle série',
                'slug' => 'nouvelle-serie',
                'category' => '/api/categories/'.$this->categorie->getId(),
                ...$surcharge,
            ],
            static fn (mixed $valeur): bool => null !== $valeur,
        );

        return $this->admin->request('POST', '/api/albums', ['json' => $corps])->toArray(false);
    }
}
