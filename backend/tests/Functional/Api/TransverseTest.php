<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Factory\PhotoFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Règles transverses de l'API, RG-COL.
 */
final class TransverseTest extends FunctionalTestCase
{
    #[TestDox('CT-FB-90 · une collection sur plusieurs pages expose member, totalItems et le lien suivant')]
    public function testFormatJsonLd(): void
    {
        PhotoFactory::createMany(30);

        $reponse = $this->anonyme()->request('GET', '/api/photos')->toArray();

        self::assertArrayHasKey('member', $reponse);
        self::assertSame(30, $reponse['totalItems']);
        self::assertSame('/api/photos?page=2', $reponse['view']['next']);
    }

    #[TestDox('CT-FB-91 · format JSON simple (en attente, RG-Q-05)')]
    public function testFormatJsonSimple(): void
    {
        $client = $this->anonyme();
        $client->request('GET', '/api/photos', ['headers' => ['Accept' => 'application/json']]);

        self::markTestIncomplete(\sprintf(
            'RG-Q-05 en attente de décision : la documentation annonce le format JSON simple, la requête répond %d.',
            $client->getResponse()->getStatusCode(),
        ));
    }
}
