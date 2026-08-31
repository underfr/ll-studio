<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Photo;
use App\Factory\AlbumFactory;
use App\Factory\CategoryFactory;
use App\Factory\MessageContactFactory;
use App\Factory\PhotoFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Jeu de données de développement.
 *
 * Les photos, titres et descriptions proviennent du portfolio réel : c'est
 * du contenu représentatif (textes longs, accents, apostrophes typographiques)
 * plutôt que du lorem ipsum, ce qui permet de tester la mise en page du front
 * dans des conditions réalistes.
 *
 * Les fichiers images sont versionnés dans backend/fixtures/photos/ et copiés
 * vers public/uploads/photos/ au chargement — public/uploads/ reste hors du
 * dépôt puisqu'il accueillera aussi les envois du back-office (issue #14).
 */
final class AppFixtures extends Fixture
{
    private const SOURCE_DIR = 'fixtures/photos';
    private const HIDDEN_PHOTO = 8;
    private const TARGET_DIR = 'public/uploads/photos';

    public function __construct(
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->copyPhotoFiles();

        $admin = UserFactory::new()->admin()->create([
            'email' => 'loicklaurent28@gmail.com',
            'firstName' => 'Loïck',
            'lastName' => 'Laurent',
            'password' => 'Temporaire123!',
        ]);

        /** @var array<string, Category> $categories */
        $categories = [];
        foreach (['Paysage', 'Portrait', 'Astronomie', 'Voiture', 'Animaux', 'Spectacle', 'Divers'] as $name) {
            $categories[$name] = CategoryFactory::new()->named($name)->create();
        }

        /** @var array<int, Photo> $photos */
        $photos = [];
        foreach ($this->photoData() as $index => $data) {
            $photos[$index] = PhotoFactory::new()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'alt' => $data['alt'],
                'filePath' => \sprintf('uploads/photos/img%02d.jpg', $index),
                // Une photo reste masquée : elle sert à vérifier que le site
                // public ne la voit pas, même en devinant son identifiant.
                'visible' => self::HIDDEN_PHOTO !== $index,
                'createdAt' => new \DateTimeImmutable($data['createdAt']),
                'category' => $categories[$data['category']],
                'owner' => $admin,
            ]);
        }

        AlbumFactory::new()->titled('Puy du Fou 2024')->create([
            'description' => "Deux journées de spectacles, du Dernier Panache aux Vikings : capter le feu, la poussière et l'instant où le geste bascule.",
            'category' => $categories['Spectacle'],
            'owner' => $admin,
            'coverPhoto' => $photos[7],
            'photos' => [$photos[2], $photos[3], $photos[6], $photos[7], $photos[9], $photos[10], $photos[11]],
            'createdAt' => new \DateTimeImmutable('2024-08-18 21:00:00'),
        ]);

        AlbumFactory::new()->titled('Nogaro 2024')->create([
            'description' => 'Les Classic Days sur le circuit Paul Armagnac : mécaniques historiques, lumière rase et odeur de gomme chaude.',
            'category' => $categories['Voiture'],
            'owner' => $admin,
            'coverPhoto' => $photos[1],
            'photos' => [$photos[1], $photos[4], $photos[5]],
            'createdAt' => new \DateTimeImmutable('2024-05-11 16:30:00'),
        ]);

        foreach ($this->messageData() as $data) {
            MessageContactFactory::new()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'read' => $data['read'],
                'createdAt' => new \DateTimeImmutable($data['createdAt']),
            ]);
        }

        $manager->flush();
    }

    /**
     * Copie les images de référence dans le dossier servi par Nginx.
     */
    private function copyPhotoFiles(): void
    {
        $source = $this->projectDir.'/'.self::SOURCE_DIR;
        $target = $this->projectDir.'/'.self::TARGET_DIR;

        $this->filesystem->mkdir($target);

        for ($i = 1; $i <= 12; ++$i) {
            $name = \sprintf('img%02d.jpg', $i);
            $this->filesystem->copy($source.'/'.$name, $target.'/'.$name, true);
        }
    }

    /**
     * @return array<int, array{title: string, description: string, alt: string, category: string, createdAt: string}>
     */
    private function photoData(): array
    {
        return [
            1 => [
                'title' => 'Légende orange à Nogaro',
                'description' => "Un moment vibrant immortalisé lors des Classic Days sur le circuit Paul Armagnac de Nogaro. Cette Porsche Jägermeister, symbole de la course historique, attire les regards autant par ses lignes que par son héritage.",
                'alt' => "Porsche Jägermeister orange n°64 sur le circuit de Nogaro lors d'un événement automobile classique",
                'category' => 'Voiture',
                'createdAt' => '2024-05-11 14:12:00',
            ],
            2 => [
                'title' => 'Cavalier et danseuse — Les Mousquetaires',
                'description' => "Un instant suspendu du spectacle Les Mousquetaires de Richelieu, capturé au cœur du Puy du Fou. Un cavalier masqué surgit dans une scène flamboyante, entre théâtre, danse et prouesse équestre.",
                'alt' => "Scène du spectacle Les Mousquetaires de Richelieu au Puy du Fou avec un cavalier masqué sur un cheval blanc et une danseuse en robe rouge",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-17 18:40:00',
            ],
            3 => [
                'title' => "Sous le feu de l'Histoire",
                'description' => "Moment intense du spectacle Le Dernier Panache, où le héros vendéen affronte l'inévitable au cœur d'une scène théâtrale saisissante. Entre éclats de poudre et silence dramatique, l'histoire prend vie avec émotion et puissance visuelle.",
                'alt' => "Scène du spectacle Le Dernier Panache au Puy du Fou, représentant un soldat face à une ligne de tir, dans un décor de mur de pierre et de forêt",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-17 15:05:00',
            ],
            4 => [
                'title' => "L'attente du rugissement",
                'description' => "Sous le soleil du circuit Paul Armagnac de Nogaro, cette Lamborghini vert acide attire les regards, immobile mais prête à bondir. L'élégance italienne rencontre la passion mécanique dans ce moment de calme avant la tempête.",
                'alt' => "Lamborghini verte garée au circuit de Nogaro, photographiée sous un ciel bleu et en pleine lumière",
                'category' => 'Voiture',
                'createdAt' => '2024-05-11 11:20:00',
            ],
            5 => [
                'title' => "La promesse d'un éclair blanc sur l'asphalte",
                'description' => "Sous le ciel azur de Nogaro, elle attend silencieusement, telle une panthère blanche prête à bondir. La lumière danse sur sa carrosserie, ses lignes fusent déjà vers l'horizon. Entre l'ombre du paddock et l'appel du circuit, elle incarne l'élégance brute et la passion mécanique, figée dans un souffle avant le rugissement.",
                'alt' => "Audi R8 blanche immobile dans le paddock de Nogaro, prête à rugir sous le soleil",
                'category' => 'Voiture',
                'createdAt' => '2024-05-11 10:02:00',
            ],
            6 => [
                'title' => "Quand l'épée danse et la brume murmure",
                'description' => "Sous le velours rouge et les drapés royaux, la brume se lève comme un souffle mystérieux. Le chevalier, dressé fièrement sur sa monture immaculée, tend la main vers la danseuse éprise d'ombre et de lumière. Au sommet, la cour observe, figée dans une élégance baroque, tandis que la scène se mue en un poème vivant.",
                'alt' => "Scène des Mousquetaires de Richelieu au Puy du Fou, entre brume et faste royal",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-17 18:52:00',
            ],
            7 => [
                'title' => "Quand l'eau chante et que l'épée scintille",
                'description' => "Dans une symphonie d'eau et de lumière, les danseuses tourbillonnent comme des flammes vives, tandis que les mousquetaires gardent leur prestance d'acier. Le cavalier blanc s'avance, noble et silencieux, témoin d'une cour où l'honneur se danse et la passion s'invente.",
                'alt' => "Spectacle des Mousquetaires de Richelieu au Puy du Fou, un ballet d'épées et de soie",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-17 19:05:00',
            ],
            8 => [
                'title' => "Un souffle d'éternité",
                'description' => "Dans le vaste océan nocturne, une lueur déchire l'infini, traçant un vœu muet que seuls les rêveurs peuvent entendre. La comète glisse, libre et éphémère, messagère d'un autre monde. Un instant suspendu, une poésie cosmique qui unit la nuit à l'âme.",
                'alt' => "Comète traversant un ciel étoilé profond, fugace et lumineuse",
                'category' => 'Astronomie',
                'createdAt' => '2024-03-22 23:47:00',
            ],
            9 => [
                'title' => "Le cri du feu et l'écho des sagas vikings",
                'description' => "Sous les hurlements du vent et le souffle ardent des flammes, le drakkar se consume, digne et furieux. Les guerriers tombent, se relèvent, dansent avec la mort et l'écume, portés par une histoire écrite dans le sang et le feu.",
                'alt' => "Drakkar en flammes dans le spectacle des Vikings au Puy du Fou, explosion de feu et de bravoure",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-18 16:30:00',
            ],
            10 => [
                'title' => 'Ave Caesar, morituri te salutant',
                'description' => "Quand le sable brûle et que les flammes lèchent le ciel, les gladiateurs avancent, l'âme déjà offerte au destin. Leurs pas résonnent comme un écho funeste dans l'arène, tandis que le peuple retient son souffle.",
                'alt' => "Gladiateurs saluant César dans l'arène du Puy du Fou, flamme et honneur antiques",
                'category' => 'Spectacle',
                'createdAt' => '2024-08-18 14:15:00',
            ],
            11 => [
                'title' => 'Le souffle du vent et la promesse du ciel',
                'description' => "Immobile et souverain, il scrute l'horizon d'un œil patient et indomptable. Sa silhouette se découpe dans la lumière, promesse d'un envol silencieux et puissant. Chaque plume raconte un voyage, chaque battement d'aile contient un rêve d'infini.",
                'alt' => "Rapace noble posé sur un perchoir, regard tourné vers l'horizon",
                'category' => 'Animaux',
                'createdAt' => '2024-08-18 11:40:00',
            ],
            12 => [
                'title' => "Le gardien silencieux de l'ombre verte",
                'description' => "Parmi les feuilles dansantes, un éclat d'émeraude se fige, souverain et secret. Ses écailles racontent des contes anciens, écrits dans la moiteur des jungles oubliées. Immobile, il scrute l'invisible, gardien patient des mystères de la forêt.",
                'alt' => "Lézard émergeant parmi les feuilles, curieux et immobile dans la lumière",
                'category' => 'Animaux',
                'createdAt' => '2024-06-29 12:18:00',
            ],
        ];
    }

    /**
     * Messages repris des maquettes de l'écran « Messages » du back-office :
     * deux non lus, deux déjà traités.
     *
     * @return array<int, array{name: string, email: string, subject: string, message: string, read: bool, createdAt: string}>
     */
    private function messageData(): array
    {
        return [
            [
                'name' => 'Camille Roy',
                'email' => 'camille.roy@email.com',
                'subject' => 'Mariage septembre 2026',
                'message' => "Bonjour, nous nous marions le 12 septembre près d'Annecy et adorons votre travail sur la lumière naturelle. Seriez-vous disponible pour une couverture complète de la journée ? Merci !",
                'read' => false,
                'createdAt' => '2026-07-11 09:24:00',
            ],
            [
                'name' => 'Théo Marchand',
                'email' => 'theo.marchand@email.com',
                'subject' => 'Shooting concert',
                'message' => "Salut ! On sort notre premier album et on cherche un photographe pour la release party du 3 octobre, salle de 300 personnes. Est-ce que ce genre de format vous intéresse ?",
                'read' => false,
                'createdAt' => '2026-07-09 18:02:00',
            ],
            [
                'name' => 'Laura Fontaine',
                'email' => 'laura.fontaine@email.com',
                'subject' => 'Rassemblement youngtimers',
                'message' => "Bonjour, notre club organise un rassemblement de 40 voitures anciennes le 20 septembre sur le circuit de Nogaro. Nous cherchons quelqu'un pour couvrir la journée et fournir une galerie aux participants.",
                'read' => true,
                'createdAt' => '2026-07-05 14:47:00',
            ],
            [
                'name' => 'NASA Space Apps',
                'email' => 'contact@spaceapps-local.org',
                'subject' => 'Expo astrophotographie',
                'message' => "Nous préparons une exposition sur l'astrophotographie amateur en novembre et aimerions présenter deux ou trois de vos clichés de comètes. Pouvons-nous en discuter ?",
                'read' => true,
                'createdAt' => '2026-07-01 10:11:00',
            ],
        ];
    }
}
