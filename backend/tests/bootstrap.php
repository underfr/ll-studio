<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

/*
 * Garde-fou. Les tests remettent la base de données à zéro avant chacun
 * d'eux : hors environnement de test, c'est la base de développement qui
 * serait effacée. C'est arrivé une fois, quand le conteneur imposait APP_ENV=dev
 * par $_ENV (voir phpunit.dist.xml). On refuse donc de démarrer plutôt que de
 * risquer de recommencer.
 */
$environnement = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;

if ('test' !== $environnement) {
    fwrite(STDERR, sprintf(
        "Tests interrompus : l'environnement résolu est « %s » et non « test ». La base de données de cet environnement serait effacée.\n",
        $environnement ?? 'aucun',
    ));

    exit(1);
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
