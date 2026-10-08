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
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Photographies, règles RG-PHO, dont l'envoi de fichiers.
 */
final class PhotoTest extends FunctionalTestCase
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

    #[TestDox('CT-FB-30 · un JPEG valide est enregistré, publié, servi sous /uploads/photos et attribué à son expéditeur')]
    public function testEnvoiValide(): void
    {
        $creee = $this->envoyer('valide.jpg');

        self::assertResponseStatusCodeSame(201);
        self::assertStringStartsWith('/uploads/photos/', $creee['contentUrl']);
        self::assertFileExists(self::dossierEnvois().'/'.$creee['filePath']);
        self::assertTrue($creee['visible']);

        $detail = $this->admin->request('GET', '/api/photos/'.$creee['id'])->toArray();
        self::assertMatchesRegularExpression('#^/api/users/\d+$#', $detail['owner']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formatsAcceptes(): iterable
    {
        yield 'PNG' => ['valide.png'];
        yield 'WebP' => ['valide.webp'];
    }

    #[DataProvider('formatsAcceptes')]
    #[TestDox('CT-FB-31 · le format $fichier est accepté')]
    public function testFormatsAcceptes(string $fichier): void
    {
        $this->envoyer($fichier);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formatsRefuses(): iterable
    {
        yield 'GIF' => ['format-refuse.gif'];
        yield 'PDF' => ['format-refuse.pdf'];
    }

    #[DataProvider('formatsRefuses')]
    #[TestDox('CT-FB-32 · le format de $fichier est refusé')]
    public function testFormatsRefuses(string $fichier): void
    {
        $reponse = $this->envoyer($fichier);

        self::assertResponseStatusCodeSame(422);
        self::assertStringStartsWith('Format non accepté', self::violations($reponse)['imageFile'][0]);
    }

    #[TestDox('CT-FB-33 · un fichier de plus de 8 Mo est refusé')]
    public function testFichierTropLourd(): void
    {
        // Un JPEG valide suivi de 8,5 Mo de remplissage : les décodeurs
        // ignorent ce qui suit la fin de l'image, seul le poids est en cause.
        $lourd = sys_get_temp_dir().'/'.uniqid('lourd-', true).'.jpg';
        copy(__DIR__.'/../../fixtures/images/valide.jpg', $lourd);
        file_put_contents($lourd, str_repeat("\0", 8_500_000), \FILE_APPEND);

        $reponse = $this->envoyer(new UploadedFile($lourd, 'lourd.jpg', null, null, true));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('ne doit pas dépasser', self::violations($reponse)['imageFile'][0]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dimensionsRefusees(): iterable
    {
        yield '799 × 600' => ['trop-etroite.jpg', "L'image fait 799 px de large, le minimum est 800 px."];
        yield '800 × 599' => ['trop-basse.jpg', "L'image fait 599 px de haut, le minimum est 600 px."];
        yield '8001 × 800' => ['trop-large.png', "L'image fait 8001 px de large, le maximum est 8000 px."];
    }

    #[DataProvider('dimensionsRefusees')]
    #[TestDox('CT-FB-34 · $fichier est refusé pour ses dimensions')]
    public function testDimensionsRefusees(string $fichier, string $message): void
    {
        $reponse = $this->envoyer($fichier);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([$message], self::violations($reponse)['imageFile']);
    }

    #[TestDox('CT-FB-35 · une image de 800 × 600 exactement est acceptée, les bornes sont incluses')]
    public function testBorneBasseIncluse(): void
    {
        $this->envoyer('limite-basse.jpg');

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fauxFichiers(): iterable
    {
        yield 'texte renommé en .jpg' => ['texte-renomme.jpg'];
        yield 'signature JPEG sans image' => ['jpeg-corrompu.jpg'];
    }

    #[DataProvider('fauxFichiers')]
    #[TestDox('CT-FB-36 · $fichier est refusé')]
    public function testFauxFichiersRefuses(string $fichier): void
    {
        $reponse = $this->envoyer($fichier);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('imageFile', self::violations($reponse));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool, string, string}>
     */
    public static function champsInvalides(): iterable
    {
        yield 'sans fichier' => [[], false, 'imageFile', 'Une photo doit être accompagnée de son fichier image.'];
        yield 'sans titre' => [['title' => ''], true, 'title', 'Cette valeur ne doit pas être vide.'];
        yield 'titre de 121 caractères' => [['title' => str_repeat('a', 121)], true, 'title', 'Cette chaîne est trop longue. Elle doit avoir au maximum 120 caractères.'];
        yield 'sans texte alternatif' => [['alt' => ''], true, 'alt', "Le texte alternatif est obligatoire pour l'accessibilité."];
        yield 'sans catégorie' => [['category' => null], true, 'category', 'Une photo doit appartenir à une catégorie.'];
    }

    /**
     * @param array<string, mixed> $surcharge
     */
    #[DataProvider('champsInvalides')]
    #[TestDox('CT-FB-37 · $_dataName : refus sur le champ $champ')]
    public function testChampsObligatoires(array $surcharge, bool $avecFichier, string $champ, string $message): void
    {
        $champs = array_filter(
            [...$this->champsValides(), ...$surcharge],
            static fn (mixed $valeur): bool => null !== $valeur,
        );

        $this->envoyerPhoto($this->admin, $champs, $avecFichier ? $this->image('valide.jpg') : null);
        $reponse = $this->admin->getResponse()->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($message, self::violations($reponse)[$champ] ?? []);
    }

    #[TestDox('CT-FB-38 · deux envois du même fichier ne s\'écrasent pas')]
    public function testNomsDeFichiersUniques(): void
    {
        $premiere = $this->envoyer('valide.jpg');
        $seconde = $this->envoyer('valide.jpg');

        self::assertNotSame($premiere['filePath'], $seconde['filePath']);
        self::assertFileExists(self::dossierEnvois().'/'.$premiere['filePath']);
        self::assertFileExists(self::dossierEnvois().'/'.$seconde['filePath']);
    }

    #[TestDox('CT-FB-39 · supprimer une photo efface son fichier et la retire de sa série, sans toucher à la série')]
    public function testSuppressionAvecFichier(): void
    {
        $photo = $this->envoyer('valide.jpg');
        $fichier = self::dossierEnvois().'/'.$photo['filePath'];
        $serie = $this->admin->request('POST', '/api/albums', ['json' => [
            'title' => 'Série',
            'slug' => 'serie',
            'category' => '/api/categories/'.$this->categorie->getId(),
            'photos' => ['/api/photos/'.$photo['id']],
        ]])->toArray();

        $this->admin->request('DELETE', '/api/photos/'.$photo['id']);

        self::assertResponseStatusCodeSame(204);
        self::assertFileDoesNotExist($fichier);
        $apres = $this->admin->request('GET', '/api/albums/'.$serie['id'])->toArray();
        self::assertResponseStatusCodeSame(200);
        self::assertSame([], $apres['photos']);
    }

    #[TestDox('CT-FB-40 · une modification date la photo, sans toucher à sa date de création, et exige le format merge-patch')]
    public function testModificationPartielle(): void
    {
        $photo = PhotoFactory::createOne(['createdAt' => new \DateTimeImmutable('2024-05-11 16:30:00')]);
        $url = '/api/photos/'.$photo->getId();

        $this->patch($this->admin, $url, ['title' => 'Nouveau titre', 'createdAt' => '2020-01-01T00:00:00+00:00']);
        self::assertResponseStatusCodeSame(200);

        $detail = $this->admin->request('GET', $url)->toArray();
        self::assertSame('Nouveau titre', $detail['title']);
        self::assertNotEmpty($detail['updatedAt'] ?? null);
        self::assertStringStartsWith('2024-05-11T16:30:00', $detail['createdAt']);

        $this->admin->request('PATCH', $url, ['json' => ['title' => 'Mauvais format']]);
        self::assertResponseStatusCodeSame(415);
    }

    #[TestDox('CT-FB-41 · 24 photos par page, de la plus récente à la plus ancienne, 60 au plus à la demande')]
    public function testPagination(): void
    {
        PhotoFactory::createMany(70);
        $client = $this->anonyme();

        $defaut = $client->request('GET', '/api/photos')->toArray();
        self::assertCount(24, $defaut['member']);
        $dates = array_column($defaut['member'], 'createdAt');
        $triees = $dates;
        rsort($triees);
        self::assertSame($triees, $dates);

        self::assertCount(60, $client->request('GET', '/api/photos?itemsPerPage=60')->toArray()['member']);
        self::assertCount(60, $client->request('GET', '/api/photos?itemsPerPage=100')->toArray()['member']);
        self::assertCount(24, $client->request('GET', '/api/photos?pagination=false')->toArray()['member']);
    }

    #[TestDox('CT-FB-42 · chaque filtre et chaque tri ne renvoie que ce qu\'il doit')]
    public function testFiltresEtTris(): void
    {
        $voiture = CategoryFactory::new()->named('Voiture')->create();
        $nogaro = PhotoFactory::createOne([
            'title' => 'Légende orange à Nogaro',
            'description' => 'Porsche sur le circuit',
            'category' => $voiture,
            'createdAt' => new \DateTimeImmutable('2024-05-11'),
        ]);
        $aurore = PhotoFactory::createOne([
            'title' => 'Aurore boréale',
            'description' => 'Ciel du Grand Nord',
            'category' => $this->categorie,
            'createdAt' => new \DateTimeImmutable('2025-02-01'),
        ]);
        $masquee = PhotoFactory::new()->hidden()->create(['category' => $this->categorie, 'createdAt' => new \DateTimeImmutable('2023-01-01')]);
        AlbumFactory::createOne(['slug' => 'nogaro-2024', 'photos' => [$nogaro]]);

        $ids = fn (string $requete): array => array_column(
            $this->admin->request('GET', '/api/photos'.$requete)->toArray()['member'],
            'id',
        );

        self::assertSame([$nogaro->getId()], $ids('?category.slug=voiture'));
        self::assertSame([$nogaro->getId()], $ids('?albums.slug=nogaro-2024'));
        self::assertSame([$nogaro->getId()], $ids('?title=ORANGE'));
        self::assertSame([$aurore->getId()], $ids('?description=grand+nord'));
        self::assertSame([$aurore->getId()], $ids('?createdAt[after]=2025-01-01'));
        self::assertSame([$masquee->getId()], $ids('?createdAt[before]=2024-01-01'));
        self::assertSame([$masquee->getId()], $ids('?visible=false'));
        self::assertSame([$aurore->getId(), $nogaro->getId(), $masquee->getId()], $ids(''));
        self::assertSame([$masquee->getId(), $nogaro->getId(), $aurore->getId()], $ids('?order[createdAt]=asc'));
        self::assertSame($aurore->getId(), $ids('?order[title]=asc')[0]);
    }

    #[TestDox('CT-FB-43 · date de modification, auteur et séries n\'apparaissent qu\'au détail')]
    public function testChampsDuDetail(): void
    {
        $photo = PhotoFactory::createOne(['owner' => UserFactory::createOne()]);
        AlbumFactory::createOne(['photos' => [$photo]]);
        $this->patch($this->admin, '/api/photos/'.$photo->getId(), ['title' => 'Datée']);

        $element = $this->admin->request('GET', '/api/photos')->toArray()['member'][0];
        $detail = $this->admin->request('GET', '/api/photos/'.$photo->getId())->toArray();

        foreach (['updatedAt', 'owner', 'albums'] as $champ) {
            self::assertArrayNotHasKey($champ, $element, 'collection : '.$champ);
            self::assertArrayHasKey($champ, $detail, 'détail : '.$champ);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function champsValides(): array
    {
        return [
            'title' => 'Une photographie',
            'alt' => 'Description de la photographie',
            'category' => '/api/categories/'.$this->categorie->getId(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function envoyer(string|UploadedFile $fichier): array
    {
        $this->envoyerPhoto(
            $this->admin,
            $this->champsValides(),
            $fichier instanceof UploadedFile ? $fichier : $this->image($fichier),
        );

        return $this->admin->getResponse()->toArray(false);
    }
}
