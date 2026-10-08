<?php

declare(strict_types=1);

/*
 * Génère les images de référence des tests d'envoi (plan de tests, section 4.1).
 *
 *     php tests/fixtures/images/generer.php
 *
 * Les fichiers produits sont versionnés : ce script sert à les reproduire, pas
 * à les régénérer à chaque exécution. Ils sont volontairement unis, donc
 * légers. Le fichier de plus de 8 Mo n'est pas versionné : les tests le
 * fabriquent à la volée.
 */

$dossier = __DIR__;

/** Image unie, au format et aux dimensions demandés. */
$generer = static function (string $nom, int $largeur, int $hauteur, string $format) use ($dossier): void {
    $image = imagecreatetruecolor($largeur, $hauteur);
    imagefill($image, 0, 0, imagecolorallocate($image, 201, 162, 75));

    $chemin = $dossier.'/'.$nom;

    match ($format) {
        'jpeg' => imagejpeg($image, $chemin, 80),
        'png' => imagepng($image, $chemin, 9),
        'webp' => imagewebp($image, $chemin, 80),
        'gif' => imagegif($image, $chemin),
    };

    echo $nom, ' ', $largeur, ' x ', $hauteur, PHP_EOL;
};

$generer('valide.jpg', 1800, 1200, 'jpeg');
$generer('valide.png', 1800, 1200, 'png');
$generer('valide.webp', 1800, 1200, 'webp');
$generer('format-refuse.gif', 1800, 1200, 'gif');
$generer('limite-basse.jpg', 800, 600, 'jpeg');
$generer('trop-etroite.jpg', 799, 600, 'jpeg');
$generer('trop-basse.jpg', 800, 599, 'jpeg');
$generer('trop-large.png', 8001, 800, 'png');

// Un document PDF minimal : format refusé.
file_put_contents($dossier.'/format-refuse.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
echo 'format-refuse.pdf', PHP_EOL;

// Du texte déguisé en JPEG par son seul nom de fichier.
file_put_contents($dossier.'/texte-renomme.jpg', "Ceci n'est pas une image.\n");
echo 'texte-renomme.jpg', PHP_EOL;

// Une signature JPEG valide suivie de données sans aucun sens : le type annoncé
// est image/jpeg, mais rien n'est décodable.
file_put_contents($dossier.'/jpeg-corrompu.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01".str_repeat("\x5A\x13\xC7", 4000));
echo 'jpeg-corrompu.jpg', PHP_EOL;
