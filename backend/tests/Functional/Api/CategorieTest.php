<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Factory\CategoryFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Catégories, règles RG-CAT.
 */
final class CategorieTest extends FunctionalTestCase
{
    private Client $admin;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->connecte(UserFactory::new()->admin()->create());
    }

    #[TestDox('CT-FB-60 · une catégorie valide est créée')]
    public function testCreationValide(): void
    {
        $this->admin->request('POST', '/api/categories', ['json' => ['name' => 'Spectacle', 'slug' => 'spectacle']]);

        self::assertResponseStatusCodeSame(201);
    }

    /**
     * @return iterable<string, array{array<string, string>, string, string}>
     */
    public static function categoriesInvalides(): iterable
    {
        yield 'nom déjà pris' => [['name' => 'Voiture', 'slug' => 'automobile'], 'name', 'Cette catégorie existe déjà.'];
        yield 'slug déjà pris' => [['name' => 'Automobile', 'slug' => 'voiture'], 'slug', 'Ce slug est déjà utilisé.'];
        yield 'slug invalide' => [['name' => 'Automobile', 'slug' => 'Automobile'], 'slug', 'Le slug ne peut contenir que des minuscules, des chiffres et des tirets.'];
    }

    /**
     * @param array<string, string> $corps
     */
    #[DataProvider('categoriesInvalides')]
    #[TestDox('CT-FB-60 · $_dataName : refus sur le champ $champ')]
    public function testCreationInvalide(array $corps, string $champ, string $message): void
    {
        CategoryFactory::new()->named('Voiture')->create();

        $reponse = $this->admin->request('POST', '/api/categories', ['json' => $corps])->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($message, self::violations($reponse)[$champ] ?? []);
    }

    #[TestDox('CT-FB-61 · la collection n\'est pas paginée et suit l\'ordre alphabétique')]
    public function testCollectionCompleteEtTriee(): void
    {
        CategoryFactory::createMany(30);

        $reponse = $this->anonyme()->request('GET', '/api/categories')->toArray();

        self::assertSame(30, $reponse['totalItems']);
        self::assertCount(30, $reponse['member']);
        $noms = array_column($reponse['member'], 'name');
        $tries = $noms;
        sort($tries);
        self::assertSame($tries, $noms);
    }

    #[TestDox('CT-FB-62 · une catégorie sans rattachement se supprime')]
    public function testSuppressionLibre(): void
    {
        $libre = CategoryFactory::createOne();

        $this->admin->request('DELETE', '/api/categories/'.$libre->getId());

        self::assertResponseStatusCodeSame(204);
    }

    #[TestDox('CT-FB-63 · une catégorie encore utilisée n\'est pas supprimée (code de refus en attente, RG-Q-01)')]
    public function testSuppressionRefuseeSiUtilisee(): void
    {
        $utilisee = CategoryFactory::createOne();
        PhotoFactory::createOne(['category' => $utilisee]);

        $this->admin->request('DELETE', '/api/categories/'.$utilisee->getId());
        $statut = $this->admin->getResponse()->getStatusCode();

        // Partie établie, RG-CAT-04 : la catégorie survit à la tentative.
        self::assertGreaterThanOrEqual(400, $statut);
        $this->admin->request('GET', '/api/categories/'.$utilisee->getId());
        self::assertResponseStatusCodeSame(200);

        // Partie en attente, RG-Q-01 : quel code renvoyer ?
        self::markTestIncomplete(\sprintf('RG-Q-01 en attente de décision : la suppression est refusée avec un %d.', $statut));
    }
}
