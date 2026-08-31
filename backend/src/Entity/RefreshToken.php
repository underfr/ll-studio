<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * Jeton de rafraîchissement persisté, consommé par POST /api/token/refresh.
 *
 * L'entité n'est volontairement pas exposée par API Platform : elle ne
 * transite jamais autrement que dans la réponse de login et le corps de la
 * requête de rafraîchissement.
 */
#[ORM\Entity]
#[ORM\Table(name: 'refresh_token')]
class RefreshToken extends BaseRefreshToken
{
}
