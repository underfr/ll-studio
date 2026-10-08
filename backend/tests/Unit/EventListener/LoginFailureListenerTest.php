<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\LoginFailureListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

final class LoginFailureListenerTest extends TestCase
{
    #[TestDox('CT-UB-09 · un verrouillage de 15 minutes devient un 429 avec Retry-After: 900')]
    public function testVerrouillageRequalifie(): void
    {
        $reponse = $this->echec(new TooManyLoginAttemptsAuthenticationException(15));

        self::assertSame(429, $reponse->getStatusCode());
        self::assertSame(429, json_decode((string) $reponse->getContent(), true)['code']);
        self::assertSame('900', $reponse->headers->get('Retry-After'));
    }

    #[TestDox('CT-UB-10 · un autre échec reste un 401, sans délai')]
    public function testAutreEchecInchange(): void
    {
        $reponse = $this->echec(new BadCredentialsException());

        self::assertSame(401, $reponse->getStatusCode());
        self::assertFalse($reponse->headers->has('Retry-After'));
    }

    #[TestDox('CT-UB-10 · un verrouillage sans durée connue devient un 429 sans Retry-After')]
    public function testVerrouillageSansDuree(): void
    {
        $reponse = $this->echec(new TooManyLoginAttemptsAuthenticationException());

        self::assertSame(429, $reponse->getStatusCode());
        self::assertFalse($reponse->headers->has('Retry-After'));
    }

    #[TestDox('CT-UB-10 · une réponse remplacée par un autre écouteur est laissée telle quelle')]
    public function testReponseEtrangereIgnoree(): void
    {
        $reponse = new JsonResponse(['erreur' => 'autre'], 401);

        (new LoginFailureListener())(new AuthenticationFailureEvent(new TooManyLoginAttemptsAuthenticationException(15), $reponse));

        self::assertSame(401, $reponse->getStatusCode());
        self::assertFalse($reponse->headers->has('Retry-After'));
    }

    private function echec(AuthenticationException $exception): JWTAuthenticationFailureResponse
    {
        $reponse = new JWTAuthenticationFailureResponse('Échec', 401);

        (new LoginFailureListener())(new AuthenticationFailureEvent($exception, $reponse));

        return $reponse;
    }
}
