<?php

declare(strict_types=1);

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Requalifie un verrouillage pour trop de tentatives en 429.
 *
 * Lexik répond 401 quelle que soit la cause de l'échec. Un écran de connexion
 * ne pourrait alors distinguer un compte verrouillé d'un mot de passe erroné
 * qu'en analysant le texte du message, ce qui casserait à la première
 * reformulation. Le code de statut porte désormais l'information, et
 * l'en-tête `Retry-After` dit au bout de combien de temps réessayer.
 *
 * L'interception passe par l'événement de Lexik et non par une décoration du
 * service `lexik_jwt_authentication.handler.authentication_failure` : le bundle
 * de sécurité n'utilise pas ce service, il en construit une instance anonyme
 * en ligne à partir de sa définition, et toute décoration resterait donc sans
 * effet sur le pare-feu. Vérifié dans le conteneur compilé.
 */
#[AsEventListener(event: Events::AUTHENTICATION_FAILURE)]
final readonly class LoginFailureListener
{
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return;
        }

        $response = $event->getResponse();

        if (!$response instanceof JWTAuthenticationFailureResponse) {
            return;
        }

        $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS);

        /*
         * Lexik recopie le statut dans le champ « code » du corps au moment de
         * composer les données. Sans cette recomposition, la réponse
         * annoncerait 429 en en-tête et 401 dans son corps.
         */
        $response->setMessage($response->getMessage());

        $minutes = $exception->getMessageData()['%minutes%'] ?? null;

        if (\is_int($minutes) && $minutes > 0) {
            $response->headers->set('Retry-After', (string) ($minutes * 60));
        }
    }
}
