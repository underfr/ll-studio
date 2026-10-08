# Exécution des tests

Comment lancer les trois suites, ce qu'il faut préparer, les pièges connus, et le bilan de la première campagne complète. Les cas eux-mêmes sont décrits dans [02-plan-de-tests.md](02-plan-de-tests.md).

## Préparation, une seule fois

La pile Docker doit être démarrée. Depuis la racine du dépôt :

```bash
sh backend/tests/preparer-environnement.sh
```

Ce script crée la base `llstudio_test` avec le compte `root`, et recopie la phrase secrète JWT dans `backend/.env.test.local`. Il est à relancer si le volume de la base de données est recréé.

Pour les tests de bout en bout, installer une fois le projet `e2e/` sur la machine hôte :

```bash
cd e2e && pnpm install
```

## Lancer les suites

| Suite | Commande, depuis la racine | Durée |
|---|---|---|
| PHPUnit, unitaires et fonctionnels | `docker compose -f docker/docker-compose.yml exec php vendor/bin/phpunit` | 30 s environ |
| PHPUnit avec couverture | `docker compose -f docker/docker-compose.yml exec php php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text` | 35 s environ |
| Vitest | `docker compose -f docker/docker-compose.yml exec frontend pnpm test` | 3 s environ |
| Cypress, sans interface | `cd e2e && pnpm test` | 15 s environ |
| Cypress, avec interface | `cd e2e && pnpm ouvrir` | |

**Attention : `pnpm test` dans `e2e/` recharge les fixtures, ce qui efface la base de développement** (décision D2 du plan). Le mode avec interface ne les recharge pas.

## Pièges connus

Chacun de ces points a été rencontré pendant l'écriture des tests.

| Situation | Explication | Que faire |
|---|---|---|
| PHPUnit refuse de démarrer : « l'environnement résolu est dev » | Garde-fou de `tests/bootstrap.php` : hors environnement de test, les tests effaceraient la base de développement. C'est arrivé une fois. | Lancer PHPUnit avec sa configuration, jamais avec `--no-configuration`. |
| Une modification de `config/packages/test/` semble ignorée | Le conteneur de test compilé n'a pas été reconstruit. | Supprimer `backend/var/cache/test` puis relancer. |
| Ajout d'une dépendance au front | `node_modules` est un volume nommé, et pnpm recréerait un magasin dans le dépôt. | Dans le conteneur : `pnpm add -D --store-dir=/pnpm/store <paquet>`. |
| pnpm 11 refuse de lancer Cypress | pnpm bloque les scripts d'installation non approuvés. | L'approbation de Cypress est versionnée dans `e2e/pnpm-workspace.yaml`, rien à faire. |
| Deux tests PHPUnit marqués « incomplets » | C'est voulu : ils vérifient la partie établie d'une règle et signalent la partie en attente de décision. | Voir RG-Q-01 et RG-Q-05 ci-dessous. |

## Bilan de la campagne du 8 octobre 2026

Branche `tests`, après synchronisation avec `main` au commit `4e21828`.

| Niveau | Tests | Résultat |
|---|---|---|
| Unitaires PHP | 32 | tous verts |
| Fonctionnels API | 199 | verts, dont 2 en attente de décision |
| Unitaires et composants front | 59 | tous verts |
| Bout en bout | 15 | tous verts |
| **Total** | **305** | **les 126 cas du plan sont couverts** |

Chaque suite a été exécutée trois fois de suite avec le même résultat, comme l'exige le plan pour clore une phase. La suite PHP l'a aussi été avec les deux graines Faker qui la faisaient échouer avant correction.

### Couverture du code PHP

| Périmètre | Lignes couvertes |
|---|---|
| `src/Security/`, `src/Doctrine/`, `src/EventListener/`, `src/State/` | 100 % |
| `src/Serializer/` | 96 % |
| `src/Entity/` | 77 % |
| Ensemble de `src/` hors fixtures de développement et noyau | 86,8 % |
| Ensemble de `src/` | 58,1 % |

Le dernier chiffre est tiré vers le bas par `AppFixtures.php`, 175 instructions que les tests n'exécutent jamais par choix : chaque test construit ses propres données.

### Vérification par mutation

Pour s'assurer que les tests détectent réellement une régression, cinq défauts ont été introduits temporairement dans le code applicatif, sans être commités, puis retirés. Celui de la page d'erreur a été essayé contre les deux suites :

| Défaut introduit | Détecté par |
|---|---|
| La galerie ne repart plus de la première tranche au changement de filtre | CT-CF-31 |
| La page d'erreur affiche le message technique de Nuxt | CT-CF-41, CT-E2E-13 |
| La visionneuse ne rend plus le focus | CT-CF-05 |
| La navigation de la visionneuse ne boucle plus | CT-CF-02 |
| `robots.txt` n'exclut plus `/admin` | CT-E2E-15 |

### Ce que les tests ont révélé

| Constat | Suite donnée |
|---|---|
| Supprimer une catégorie encore utilisée renvoie une **500** (RG-Q-01) | En attente de décision. |
| `Accept: application/json` renvoie **406**, contrairement à ce qu'annonce la documentation de l'API (RG-Q-05) | En attente de décision. |
| `Photo.php` importait les attributs VichUploader depuis un espace de noms déprécié | Corrigé sur `main`, issue #59. |
| Les tests PHP démarraient le noyau en environnement de développement | Corrigé dans `phpunit.dist.xml`, garde-fou ajouté. |
| Des noms de catégorie en double faisaient échouer la suite PHP de façon intermittente | Générateur Faker partagé en environnement de test. |
