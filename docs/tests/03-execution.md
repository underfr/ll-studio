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
| PHPUnit, unitaires et fonctionnels | `docker compose -f docker/docker-compose.yml exec php vendor/bin/phpunit` | 45 s environ |
| PHPUnit avec couverture | `docker compose -f docker/docker-compose.yml exec php php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text` | 50 s environ |
| Vitest | `docker compose -f docker/docker-compose.yml exec frontend pnpm test` | 3 s environ |
| Cypress, sans interface | `cd e2e && pnpm test` | 20 s environ |
| Cypress, avec interface | `cd e2e && pnpm ouvrir` | |

**Attention : `pnpm test` dans `e2e/` recharge les fixtures, ce qui efface la base de développement** (décision D2 du plan), et remet à zéro les compteurs du limiteur de connexion. Le mode avec interface ne fait ni l'un ni l'autre.

## Pièges connus

Chacun de ces points a été rencontré pendant l'écriture des tests.

| Situation | Explication | Que faire |
|---|---|---|
| PHPUnit refuse de démarrer : « l'environnement résolu est dev » | Garde-fou de `tests/bootstrap.php` : hors environnement de test, les tests effaceraient la base de développement. C'est arrivé une fois. | Lancer PHPUnit avec sa configuration, jamais avec `--no-configuration`. |
| Une modification de `config/packages/test/` semble ignorée | Le conteneur de test compilé n'a pas été reconstruit. | Supprimer `backend/var/cache/test` puis relancer. |
| Ajout d'une dépendance au front | `node_modules` est un volume nommé, et pnpm recréerait un magasin dans le dépôt. | Dans le conteneur : `pnpm add -D --store-dir=/pnpm/store <paquet>`. |
| pnpm 11 refuse de lancer Cypress | pnpm bloque les scripts d'installation non approuvés. | L'approbation de Cypress est versionnée dans `e2e/pnpm-workspace.yaml`, rien à faire. |
| Deux tests PHPUnit marqués « incomplets » | C'est voulu : ils vérifient la partie établie d'une règle et signalent la partie en attente de décision. | Voir RG-Q-01 et RG-Q-05 ci-dessous. |
| `pnpm test` répond « vitest: not found » dans le conteneur front | `node_modules` est un volume partagé par les branches : quand le conteneur front est recréé pendant que `main` est extraite, son installation retire les dépendances de test. | Sur la branche `tests`, dans le conteneur : `pnpm install --frozen-lockfile --store-dir=/pnpm/store`. |
| Après la synchronisation du 16 octobre, Vitest échoue sur tous les fichiers dès le deuxième passage (« Failed to resolve import ./diagnostics/core.js ») | Vitest 5.0.5, obtenu en régénérant le verrouillage, met en cache des transformations dont un chemin devient invalide au passage suivant. | Outils de test épinglés en version exacte (Vitest 5.0.3, happy-dom 20.14.5, @nuxt/test-utils 4.3.3, @vue/test-utils 2.5.1), ce qui empêche aussi une régénération de les faire monter en silence. |
| Vitest échoue de la même façon après une construction de production lancée dans le conteneur | La construction écrit dans des caches que l'environnement de test réutilise. | Dans le conteneur : `rm -rf node_modules/.cache/vite node_modules/.vitest-cache node_modules/.vite-temp`. |
| Des tentatives de connexion échouées s'accumulent pendant les tests de bout en bout | `/api/login` verrouille un identifiant après 5 échecs en 15 minutes. CT-E2E-16 en provoque un volontairement à chaque passage. | Rien à faire : la connexion réussie qui suit remet le compteur à zéro. |

## Synchronisation du 16 octobre 2026, après #67

Synchronisation avec `main` au commit `951fc79` : socle Tailwind CSS et daisyUI, back-office migré (#67).

- **Verrouillage** : en conflit, comme prévu par le plan. Version de `main` reprise, dépendances de test réinstallées dans le conteneur, puis outils de test épinglés (voir les pièges).
- **Ce qui a cassé** : 4 tests Vitest sur 110, parce que deux blocs du site public ont été renommés pour ne plus porter un nom de composant daisyUI : `.hero` devient `.ouverture` (CT-CF-34), `.menu__*` devient `.menu-mobile__*` (CT-CF-20). Sélecteurs mis à jour, assertions inchangées. Les tests du gabarit, de la connexion et du champ sont passés sans modification grâce aux classes d'accroche conservées.
- **Ajouts** : CT-CF-56 et CT-E2E-19 pour le panneau de navigation mobile du back-office (RG-ADM-12), qui remplace la bande défilante.
- **Mutations** : un focus donné avant que daisyUI ait rendu le panneau visible n'est détecté que par CT-E2E-19, ce qui justifie ce test dans un vrai navigateur ; un contenu jamais rendu inerte est détecté par CT-CF-56.
- **Limiteur de connexion** : au troisième passage consécutif, les parcours qui se connectent ont échoué en 429. Les campagnes du jour, mutations comprises, avaient dépassé le seuil de tentatives depuis la même adresse : c'est RG-AUTH-06 qui fonctionne. Les tests ne doivent pas dépendre de ce qui a tourné avant eux : la configuration Cypress vide désormais le cache du limiteur avant chaque campagne, comme les tests PHP le font avant chaque test. Depuis, neuf passages consécutifs sont verts. Un échec isolé, survenu juste après ce correctif, n'a pas été diagnostiqué faute d'avoir conservé sa sortie, et ne s'est pas reproduit.

| Niveau | Tests | Résultat |
|---|---|---|
| PHP, unitaires et fonctionnels | 246 | verts, dont 2 en attente de décision |
| Unitaires et composants front | 113 | tous verts |
| Bout en bout | 19 | tous verts |
| **Total** | **378** | **les 155 cas du plan sont couverts** |

## Bilan de la campagne du 16 octobre 2026

Branche `tests`, après synchronisation avec `main` au commit `c484590`, qui livre l'itération 7 : commande de création de l'administrateur (#47), gabarit d'administration (#26), session par cookie avec `/api/me` et déconnexion (#62), écran de connexion et garde (#25).

| Niveau | Tests | Résultat |
|---|---|---|
| Unitaires PHP | 34 | tous verts |
| Fonctionnels API | 212 | verts, dont 2 en attente de décision |
| Unitaires et composants front | 110 | tous verts |
| Bout en bout | 18 | tous verts |
| **Total** | **374** | **les 153 cas du plan sont couverts** |

Le contrôle de traçabilité par script confirme que chaque cas du plan a son test, qu'aucun test ne porte un identifiant absent du plan, et que les 127 règles établies sont vérifiées par au moins un cas. Chaque suite a été exécutée trois fois de suite avec le même résultat.

### Ce que la synchronisation a cassé

Avant tout ajout, la synchronisation seule a fait échouer **deux tests sur 305** : CT-FB-11 et CT-FB-16 lisaient le jeton de rafraîchissement dans le corps de `/api/login`. Ce n'est pas un défaut de l'application mais **un changement de contrat décidé** le 16 octobre (#62) : le jeton passe dans un cookie `HttpOnly`. Conformément à la règle du plan, la règle a été révisée d'abord (RG-AUTH-01, RG-AUTH-03, nouvelle RG-AUTH-11), puis les deux tests réécrits pour vérifier le cookie. Ils n'ont pas été assouplis : CT-FB-16 vérifie toujours l'usage unique, désormais par le cookie.

Les 59 tests Vitest et les 15 parcours Cypress existants sont passés sans modification.

### Ajouts

| Lot | Cas |
|---|---|
| Session côté API : cookie, déconnexion, `/api/me`, CORS, documentation | CT-UB-15, CT-FB-26 à CT-FB-29, CT-FB-92, une ligne de plus dans la matrice CT-FB-21 |
| Commande `app:creer-admin` | CT-FB-85 à CT-FB-87 |
| Session côté front : retour après connexion, jeton en mémoire, 401 rejouée, rafraîchissement unique, déconnexion | CT-UF-09 à CT-UF-15 |
| Navigation et garde du back-office | CT-UF-16, CT-UF-17 |
| Champ de formulaire, écran de connexion, gabarit | CT-CF-50 à CT-CF-55 |
| Parcours du back-office | CT-E2E-16 à CT-E2E-18 |

### Couverture du code PHP

Les trois classes ajoutées par l'itération, `CreerAdminCommand`, `CurrentUserProvider` et `DeconnexionOpenApiFactory`, sont couvertes à 100 %. L'ensemble de `src/` hors fixtures et noyau passe de 86,8 % à **88,5 %** ; l'ensemble de `src/`, fixtures comprises, de 58,1 % à 61,8 %.

### Vérification par mutation

Quatre défauts introduits temporairement dans le code applicatif, sans être commités, puis retirés :

| Défaut introduit | Détecté par |
|---|---|
| Le cookie du jeton de rafraîchissement n'est plus `HttpOnly` | CT-FB-26 |
| Chaque requête expirée lance son propre rafraîchissement | CT-UF-13 |
| Le paramètre de retour accepte n'importe quel chemin | CT-UF-09, CT-UF-17, CT-CF-53 (8 tests) |
| La garde ne vérifie plus le rôle `ROLE_ADMIN` | CT-UF-17 |

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
