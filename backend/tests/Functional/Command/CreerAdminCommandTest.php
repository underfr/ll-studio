<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande de création du compte administrateur, règle RG-USR-07.
 *
 * Fonctionnel plutôt qu'unitaire : la commande n'a d'intérêt que si le compte
 * créé est réellement enregistré et peut ouvrir une session sur l'API.
 */
final class CreerAdminCommandTest extends FunctionalTestCase
{
    private const string MOT_DE_PASSE = 'Admin-Essai-2026';

    #[TestDox('CT-FB-85 · la commande crée un administrateur capable de se connecter')]
    public function testCreationPuisConnexion(): void
    {
        $testeur = $this->testeur();
        $testeur->setInputs([self::MOT_DE_PASSE, self::MOT_DE_PASSE]);

        $testeur->execute(['email' => 'cree@ll-studio.test', 'prenom' => 'Créé', 'nom' => 'Commande']);

        $testeur->assertCommandIsSuccessful();
        $admin = $this->compte('cree@ll-studio.test');
        self::assertNotNull($admin);
        self::assertTrue($admin->isAdmin());
        self::assertSame('Créé Commande', $admin->getFullName());
        self::assertNotSame(self::MOT_DE_PASSE, $admin->getPassword());

        $jeton = $this->anonyme()->request('POST', '/api/login', [
            'json' => ['email' => 'cree@ll-studio.test', 'password' => self::MOT_DE_PASSE],
        ])->toArray()['token'];
        static::createClient()->request('GET', '/api/users', ['headers' => ['Authorization' => 'Bearer '.$jeton]]);
        self::assertResponseStatusCodeSame(200);
    }

    #[TestDox('CT-FB-85 · les arguments absents sont demandés un par un')]
    public function testArgumentsDemandes(): void
    {
        $testeur = $this->testeur();
        $testeur->setInputs(['demande@ll-studio.test', 'Prénom', 'Nom', self::MOT_DE_PASSE, self::MOT_DE_PASSE]);

        $testeur->execute([]);

        $testeur->assertCommandIsSuccessful();
        self::assertNotNull($this->compte('demande@ll-studio.test'));
    }

    #[TestDox('CT-FB-86 · une adresse prise ou invalide, ou un nom vide, est refusé sans rien enregistrer')]
    public function testIdentiteInvalide(): void
    {
        UserFactory::createOne(['email' => 'pris@ll-studio.test']);
        $avant = UserFactory::count();

        $testeur = $this->testeur();
        $testeur->execute(['email' => 'pris@ll-studio.test', 'prenom' => 'Doublon', 'nom' => 'Compte']);
        self::assertSame(Command::INVALID, $testeur->getStatusCode());
        self::assertStringContainsString('Adresse e-mail : Un compte existe déjà avec cette adresse e-mail.', $this->affichage($testeur));

        $testeur->execute(['email' => 'pas-une-adresse', 'prenom' => 'Jean', 'nom' => '']);
        self::assertSame(Command::INVALID, $testeur->getStatusCode());
        self::assertStringContainsString('Adresse e-mail :', $this->affichage($testeur));
        self::assertStringContainsString('Nom : Cette valeur ne doit pas être vide.', $this->affichage($testeur));

        self::assertSame($avant, UserFactory::count());
    }

    #[TestDox('CT-FB-87 · un mot de passe non confirmé, trop court ou absent est refusé sans rien enregistrer')]
    public function testMotDePasseInvalide(): void
    {
        $argumentsValides = ['email' => 'essai@ll-studio.test', 'prenom' => 'Essai', 'nom' => 'Compte'];
        $testeur = $this->testeur();

        $testeur->setInputs([self::MOT_DE_PASSE, 'autre-chose']);
        $testeur->execute($argumentsValides);
        self::assertSame(Command::INVALID, $testeur->getStatusCode());
        self::assertStringContainsString('ne correspondent pas', $this->affichage($testeur));

        $testeur->setInputs(['court', 'court']);
        $testeur->execute($argumentsValides);
        self::assertSame(Command::INVALID, $testeur->getStatusCode());
        self::assertStringContainsString('Mot de passe : Cette chaîne est trop courte.', $this->affichage($testeur));

        // Le mot de passe n'est jamais un argument : sans saisie possible,
        // la commande refuse plutôt que de créer un compte sans mot de passe.
        $testeur->execute($argumentsValides, ['interactive' => false]);
        self::assertSame(Command::INVALID, $testeur->getStatusCode());
        self::assertStringContainsString('se saisit de façon interactive', $this->affichage($testeur));

        self::assertNull($this->compte('essai@ll-studio.test'));
    }

    private function testeur(): CommandTester
    {
        return new CommandTester((new Application(static::bootKernel()))->find('app:creer-admin'));
    }

    private function compte(string $email): ?User
    {
        return UserFactory::repository()->findOneBy(['email' => $email]);
    }

    /**
     * Sortie de la commande, les blocs de SymfonyStyle remis sur une ligne :
     * leur largeur dépend du terminal et couperait les messages.
     */
    private function affichage(CommandTester $testeur): string
    {
        return (string) preg_replace('/\s+/', ' ', $testeur->getDisplay());
    }
}
