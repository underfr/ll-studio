<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Ask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Crée le compte administrateur du back-office (issue #47).
 *
 * Les fixtures sont le seul autre endroit où naît un administrateur, et
 * leurs bundles ne sont pas chargés en production. L'opération POST de l'API
 * exige elle-même ROLE_ADMIN. Sans cette commande, un site fraîchement
 * déployé n'aurait aucun moyen d'ouvrir son back-office.
 *
 * Le mot de passe n'est jamais un argument : passé sur la ligne de commande,
 * il resterait dans l'historique du shell et serait lisible dans la liste
 * des processus. Il est demandé en saisie masquée, deux fois.
 */
#[AsCommand(
    name: 'app:creer-admin',
    description: 'Crée un compte administrateur du back-office',
    help: <<<'AIDE'
        Crée un compte doté de ROLE_ADMIN, capable de se connecter au back-office.

        Les arguments absents sont demandés un par un. Le mot de passe est
        toujours saisi de façon masquée, jamais passé en argument :

          <info>php bin/console app:creer-admin admin@exemple.fr Prénom Nom</info>

        En production, depuis l'hôte :

          <info>docker compose exec php bin/console app:creer-admin</info>

        Les règles sont celles de l'API : adresse valide et inutilisée,
        mot de passe de 8 caractères au moins.
        AIDE,
)]
final readonly class CreerAdminCommand
{
    /**
     * Libellés des champs de l'entité, pour des erreurs lisibles par
     * l'exploitant plutôt que des noms de propriétés PHP.
     */
    private const array LIBELLES = [
        'email' => 'Adresse e-mail',
        'firstName' => 'Prénom',
        'lastName' => 'Nom',
        'plainPassword' => 'Mot de passe',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Adresse e-mail, qui sert d\'identifiant de connexion'), Ask('Adresse e-mail')]
        string $email,
        #[Argument('Prénom affiché dans le back-office'), Ask('Prénom')]
        string $prenom,
        #[Argument('Nom affiché dans le back-office'), Ask('Nom')]
        string $nom,
    ): int {
        $admin = (new User())
            ->setEmail(trim($email))
            ->setFirstName(trim($prenom))
            ->setLastName(trim($nom))
            ->setRoles([Role::ADMIN->value]);

        /*
         * Premier passage avant de demander le mot de passe : une adresse
         * déjà prise est signalée tout de suite, sans faire saisir deux fois
         * un mot de passe pour rien.
         */
        if ($this->signalerErreurs($io, $this->validator->validate($admin))) {
            return Command::INVALID;
        }

        $motDePasse = $io->askHidden('Mot de passe');

        // En mode non interactif, la question renvoie null sans rien demander.
        if (null === $motDePasse) {
            $io->error('Le mot de passe se saisit de façon interactive : relancez la commande sans --no-interaction.');

            return Command::INVALID;
        }

        if ($motDePasse !== $io->askHidden('Confirmation du mot de passe')) {
            $io->error('Les deux saisies du mot de passe ne correspondent pas.');

            return Command::INVALID;
        }

        $admin->setPlainPassword($motDePasse);

        // Mêmes groupes que l'opération POST /api/users.
        if ($this->signalerErreurs($io, $this->validator->validate($admin, groups: ['Default', 'user:create']))) {
            return Command::INVALID;
        }

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $motDePasse));
        $admin->eraseCredentials();

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(\sprintf('Compte administrateur créé pour %s.', $admin->getEmail()));

        return Command::SUCCESS;
    }

    /**
     * Affiche les violations de validation, une ligne par champ fautif.
     *
     * @return bool vrai si au moins une violation a été signalée
     */
    private function signalerErreurs(SymfonyStyle $io, ConstraintViolationListInterface $violations): bool
    {
        if (0 === \count($violations)) {
            return false;
        }

        $lignes = [];
        foreach ($violations as $violation) {
            $champ = $violation->getPropertyPath();
            $lignes[] = \sprintf('%s : %s', self::LIBELLES[$champ] ?? $champ, $violation->getMessage());
        }

        $io->error($lignes);

        return true;
    }
}
