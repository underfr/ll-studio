<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Faker\Factory;
use Faker\Generator;

/**
 * Générateur Faker unique pour tout le processus de test.
 *
 * Sans lui, chaque redémarrage du noyau, que provoque tout client de test,
 * construit un nouveau générateur dont la mémoire d'unicité est vide. Deux
 * catégories créées de part et d'autre d'un redémarrage pouvaient alors tirer
 * le même nom et heurter la contrainte d'unicité de la base : le test passait
 * seul et échouait parfois au milieu de la suite.
 *
 * La mémoire d'unicité est remise à zéro au début de chaque test par
 * FunctionalTestCase : la base l'est aussi, l'unicité n'a de sens qu'à
 * l'intérieur d'un test.
 */
final class FakerPartage
{
    private static ?Generator $instance = null;

    public static function instance(): Generator
    {
        return self::$instance ??= Factory::create();
    }
}
