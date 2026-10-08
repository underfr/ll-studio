<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use App\Tests\Support\FakerPartage;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Socle des tests fonctionnels de l'API.
 *
 * Chaque test part d'une base vide (ResetDatabase) et y crée ses propres
 * données avec les factories Foundry : aucun test ne dépend d'un autre, ni des
 * fixtures de développement.
 */
abstract class FunctionalTestCase extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    /**
     * Fixé explicitement : laissé à null, API Platform émet une dépréciation,
     * et phpunit.dist.xml fait échouer la suite sur toute dépréciation.
     */
    protected static ?bool $alwaysBootKernel = true;

    protected const string MERGE_PATCH = 'application/merge-patch+json';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // La base vient d'être remise à zéro : la mémoire d'unicité de Faker
        // doit l'être aussi, sinon elle s'épuiserait au fil de la suite.
        FakerPartage::instance()->unique(true);

        // Le limiteur de tentatives de connexion garde son état dans un cache
        // qui survit d'un test à l'autre : un test qui provoque des échecs
        // verrouillerait les suivants.
        static::getContainer()->get('cache.rate_limiter')->clear();

        (new Filesystem())->remove(self::dossierEnvois());
    }

    /** Client sans jeton, comme un visiteur du site public. */
    protected function anonyme(): Client
    {
        return static::createClient();
    }

    /** Client authentifié pour le compte donné. */
    protected function connecte(User $user): Client
    {
        return static::createClient([], [
            'headers' => ['Authorization' => 'Bearer '.$this->jeton($user)],
        ]);
    }

    /**
     * Jeton d'accès émis directement par Lexik. Plus rapide qu'un passage par
     * /api/login, et sans effet sur le limiteur de tentatives : les tests
     * d'authentification, eux, passent par la vraie route.
     */
    protected function jeton(User $user): string
    {
        return static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
    }

    /** Modification partielle, au seul format accepté par API Platform. */
    protected function patch(Client $client, string $url, array $donnees): void
    {
        $client->request('PATCH', $url, [
            'headers' => ['Content-Type' => self::MERGE_PATCH],
            'json' => $donnees,
        ]);
    }

    /**
     * Envoi d'une photo en multipart/form-data. Les valeurs structurées
     * (booléens, IRI) sont encodées en JSON, comme le fera le back-office :
     * c'est ce que MultipartDecoder sait relire.
     *
     * @param array<string, mixed> $champs
     */
    protected function envoyerPhoto(Client $client, array $champs, ?UploadedFile $fichier = null): void
    {
        $parametres = array_map(
            static fn (mixed $valeur): string => \is_string($valeur) ? $valeur : json_encode($valeur, \JSON_THROW_ON_ERROR),
            $champs,
        );

        $client->request('POST', '/api/photos', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => $parametres,
                'files' => null === $fichier ? [] : ['imageFile' => $fichier],
            ],
        ]);
    }

    /**
     * Copie d'une image de référence, prête à être envoyée. On n'envoie jamais
     * l'original : VichUploader déplace le fichier reçu.
     */
    protected function image(string $nom): UploadedFile
    {
        $source = __DIR__.'/../fixtures/images/'.$nom;
        $copie = sys_get_temp_dir().'/'.uniqid('envoi-', true).'-'.$nom;
        copy($source, $copie);

        return new UploadedFile($copie, $nom, null, null, true);
    }

    /** Dossier où atterrissent les fichiers envoyés en environnement de test. */
    protected static function dossierEnvois(): string
    {
        return static::getContainer()->getParameter('kernel.project_dir').'/var/test/uploads/photos';
    }

    /**
     * Messages de validation d'une réponse 422, regroupés par champ.
     *
     * @param array<string, mixed> $reponse
     *
     * @return array<string, list<string>>
     */
    protected static function violations(array $reponse): array
    {
        $parChamp = [];

        foreach ($reponse['violations'] ?? [] as $violation) {
            $parChamp[$violation['propertyPath']][] = $violation['message'];
        }

        return $parChamp;
    }

    /**
     * Identifiants des éléments d'une collection JSON-LD.
     *
     * @param array<string, mixed> $collection
     *
     * @return list<string>
     */
    protected static function iris(array $collection, string $cle = 'member'): array
    {
        return array_map(static fn (array $element): string => $element['@id'], $collection[$cle] ?? []);
    }
}
