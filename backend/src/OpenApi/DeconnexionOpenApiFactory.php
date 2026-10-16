<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Documente POST /api/token/invalidate dans la spécification OpenAPI.
 *
 * Lexik documente /api/login et le bundle de rafraîchissement documente
 * /api/token/refresh, mais la déconnexion n'est qu'un écouteur de pare-feu :
 * API Platform n'a ni ressource ni contrôleur à découvrir. Sans ce complément,
 * la seule route qui ferme une session serait absente de la documentation.
 */
#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class DeconnexionOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $openApi->getPaths()->addPath('/api/token/invalidate', new PathItem(post: new Operation(
            operationId: 'token_invalidate_post',
            // Même rubrique que la connexion et le rafraîchissement.
            tags: ['Login Check'],
            responses: [
                '200' => new Response(description: 'Session fermée : le jeton est supprimé et le cookie effacé. Même réponse si le jeton était déjà invalide.'),
                '400' => new Response(description: 'Aucun jeton de rafraîchissement dans la requête.'),
            ],
            summary: 'Ferme la session en cours',
            description: 'Lit le jeton de rafraîchissement dans le cookie « refresh_token », le supprime de la base '
                ."et efface le cookie. Aucun corps ni jeton d'accès n'est nécessaire : la déconnexion doit rester "
                .'possible une fois le jeton d\'accès expiré.',
        )));

        return $openApi;
    }
}
