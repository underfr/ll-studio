# Plan de tests de LL Studio

Ce plan décrit comment vérifier les règles de [01-regles-de-gestion.md](01-regles-de-gestion.md) : avec quels outils, dans quel environnement, sur quelles données, et quels cas de test écrire. Chaque cas renvoie aux règles qu'il vérifie, ce qui permet de contrôler qu'aucune règle n'est oubliée.

Il recouvre les issues #31 (tests API), #32 (tests front) et fournit la trame du jeu d'essai demandé par #51.

## 1. Périmètre

**Inclus** : l'API Symfony telle qu'elle existe aujourd'hui, et le site public Nuxt (accueil, galerie, albums, page d'une série, visionneuse, navigation, pages d'erreur).

**Exclus**, faute d'exister encore : le back-office (#25 à #30), le formulaire de contact (#41), les mentions légales (#43). Les écrans `/contact` et `/admin` sont des pages d'attente et ne sont pas testés. Les audits d'accessibilité (#33) et de performance (#35) restent des chantiers à part.

## 2. Organisation de la branche `tests`

- La branche `tests` a été créée le 8 octobre 2026 à partir de `main` (`35c0e0f`) et poussée sur GitHub.
- **Elle n'est jamais fusionnée dans `main`** et ne fait l'objet d'aucune pull request.
- Elle est mise à jour après chaque évolution de `main`, par fusion :

  ```bash
  git checkout tests
  git fetch origin
  git merge origin/main
  ```

  Fusion plutôt que rebase : la branche est publiée, et un rebase réécrirait son historique à chaque synchronisation, ce qui imposerait un push forcé.

- **La branche n'ajoute que des tests et de la configuration de test. Elle ne modifie jamais le code applicatif.** Si un test révèle un défaut ou un besoin de testabilité, la correction passe par une issue et une branche sur `main`, puis arrive sur `tests` à la synchronisation suivante. C'est ce qui garde les fusions sans conflit.
- La configuration de test vit dans des fichiers dédiés chaque fois que c'est possible (`config/packages/test/`, `.env.test`, projet `e2e/` autonome), pour ne pas toucher aux fichiers que `main` fait évoluer.
- Deux fichiers partagés sont inévitables : `frontend/package.json` et `frontend/pnpm-lock.yaml`, qui recevront les dépendances de Vitest. En cas de conflit sur un fichier de verrouillage, on prend la version de `main` puis on réinstalle les dépendances de test dans le conteneur pour le régénérer. On ne fusionne jamais un fichier de verrouillage à la main.
- Un commit par lot cohérent, message en français.

**Point de vigilance.** Comme ces tests ne rejoignent jamais `main`, ils ne protègent `main` que si on les exécute après chaque synchronisation. La CI prévue à l'issue #37 devra donc cibler la branche `tests`, et le dossier de certification devra renvoyer vers elle, puisque `main` n'en contiendra aucune trace.

## 3. Niveaux de test et outils

| Niveau | Ce qu'il vérifie | Outils | Où il s'exécute |
|---|---|---|---|
| Unitaire back | une classe isolée : voter, filtre, écouteur, processeur, entité | PHPUnit (`TestCase`, ou `KernelTestCase` quand un service Symfony est nécessaire) | conteneur `php` |
| Fonctionnel API | une requête HTTP traitée par le noyau Symfony complet, base de données comprise | PHPUnit, `ApiTestCase` d'API Platform, Foundry | conteneur `php` et base `llstudio_test` |
| Unitaire front | fonctions et composables | Vitest | conteneur `frontend` |
| Composant front | un composant ou une page monté, ses rendus et ses interactions, API simulée | Vitest, `@nuxt/test-utils`, `@vue/test-utils`, `happy-dom` | conteneur `frontend` |
| Bout en bout | le parcours d'un visiteur dans un vrai navigateur, sur l'application complète | Cypress, `@testing-library/cypress` | machine hôte, voir 4.3 |

Les règles de visibilité (RG-VIS) ne se testent **qu'au niveau fonctionnel**. Le filtre de visibilité n'est activé que pendant une requête HTTP : un test qui interrogerait Doctrine directement verrait le contenu masqué et donnerait un faux sentiment de sécurité.

## 4. Environnement d'exécution

L'examen de l'environnement actuel a mis au jour plusieurs obstacles, traités par la phase 0 (section 9).

### 4.1 Backend

| Constat | Conséquence | Mesure prévue |
|---|---|---|
| Doctrine suffixe déjà la base par `_test` en environnement de test. | Les tests utilisent `llstudio_test`. | Rien à changer. |
| L'image MySQL officielle ne donne à l'utilisateur applicatif des droits que sur `llstudio`. | Il ne peut pas créer `llstudio_test`. | Création de la base et attribution des droits par le compte `root`, une fois, par une commande documentée. |
| En environnement de test, Symfony ne charge pas `.env.local`. Or c'est là que l'entrypoint écrit `JWT_PASSPHRASE`. | Aucune connexion ne fonctionnerait dans les tests. | Un script de préparation écrit un `.env.test.local`, non versionné, avec la phrase secrète. |
| Le limiteur de tentatives de connexion garde son état dans un cache. | Un test qui provoque des échecs verrouillerait les suivants. | En environnement de test, adaptateur de cache en mémoire, remis à zéro à chaque démarrage du noyau (`config/packages/test/`). |
| VichUploader écrit dans `public/uploads/photos`. | Les tests d'envoi pollueraient les images de développement. | En environnement de test, destination dans `var/`, vidée après chaque test d'envoi. |
| Foundry est installé, avec ses traits `ResetDatabase` et `Factories`. | Isolation des tests sans dépendance supplémentaire. | Chaque test construit ses propres données. Les fixtures de développement ne sont pas utilisées par les tests PHP. |
| `phpunit.dist.xml` fait échouer la suite sur la moindre dépréciation. | Le premier passage peut révéler des dépréciations existantes. | On garde ce réglage ; une dépréciation trouvée devient une issue sur `main`. |
| L'image PHP n'a ni `pcov` ni `xdebug`. | Pas de mesure de couverture de code. | Décision à prendre, section 11. |

Images de référence pour les tests d'envoi, rangées dans `backend/tests/fixtures/images/` : un JPEG de 1800 × 1200, un PNG, un WebP, un GIF, un PDF, une image de 799 × 600, une de 800 × 599, une de 800 × 600 exactement, une de 8001 × 800, et un fichier texte renommé en `.jpg`. Le fichier de plus de 8 Mo est généré à la volée et n'est pas versionné.

### 4.2 Front, Vitest

- Dépendances de développement ajoutées : `vitest`, `@nuxt/test-utils`, `@vue/test-utils`, `happy-dom`. Installation **dans le conteneur**, puisque `node_modules` est un volume nommé.
- Configuration dans un fichier dédié `frontend/vitest.config.ts`, en environnement `nuxt`. `nuxt.config.ts` n'est pas modifié.
- Aucun appel réseau : les composables d'API sont remplacés par des doublures (`mockNuxtImport`), ou l'instance `$api` est simulée pour observer les URL appelées.
- Les fonctions non exportées, comme la construction de la chaîne de requête, sont vérifiées au travers des composables publics qui les utilisent, pour ne pas modifier le code applicatif.

### 4.3 Bout en bout, Cypress

| Constat | Conséquence |
|---|---|
| L'image du conteneur `frontend` est en Alpine. | Cypress ne s'y exécute pas. |
| Dans un conteneur Cypress dédié, le navigateur appellerait l'API sur `localhost:8000`, qui désignerait le conteneur lui-même. | Les images et les appels faits par le navigateur échoueraient. |
| La machine hôte dispose de Node 24 et de pnpm. | Cypress peut y tourner, contre `http://localhost:3000`, avec la configuration actuelle. |

**Recommandation** : un projet `e2e/` autonome à la racine du dépôt, avec son propre `package.json`, exécuté sur la machine hôte contre la pile Docker démarrée. Il ne dépend pas du front et n'entre donc jamais en conflit avec `frontend/package.json`. L'exécution en conteneur (`cypress/included`, pile dédiée) sera traitée avec la CI de l'issue #37.

Règles d'écriture propres à Cypress :

- Sélection par rôle, intitulé et texte avec Testing Library, jamais par classe CSS ni par attribut `data-cy`. Ajouter des `data-cy` modifierait le code applicatif, et sélectionner par rôle vérifie au passage que l'interface est accessible.
- Aucune attente fixe (`cy.wait(1000)`). On attend une requête interceptée ou un élément.
- Les attendus qui dépendent des données sont calculés à partir de l'API quand c'est possible, plutôt qu'écrits en dur.

## 5. Données de test

**Tests PHP** : chaque test construit ses données avec les factories Foundry (`PhotoFactory::new()->hidden()`, `UserFactory::new()->admin()`, etc.) sur une base remise à zéro. Aucun test ne dépend d'un autre.

**Tests Vitest** : données fabriquées dans le test, au format JSON-LD de l'API.

**Tests Cypress** : jeu de référence des fixtures, rechargé avant chaque campagne par `doctrine:fixtures:load`. Ce jeu contient :

| Élément | Contenu |
|---|---|
| Catégories | 7 : Animaux, Astronomie, Divers, Paysage, Portrait, Spectacle, Voiture |
| Photos | 12, dont 2 masquées, soit **10 publiées** : Spectacle 5 publiées sur 6, Voiture 3, Animaux 2, Astronomie 0 sur 1 |
| Photo masquée n° 8 | seule photo d'Astronomie, dans aucune série |
| Photo masquée n° 9 | Spectacle, dans la série Puy du Fou 2024 |
| Série Puy du Fou 2024 | Spectacle, 7 photos dont 1 masquée, soit **6 visibles** |
| Série Nogaro 2024 | Voiture, 3 photos |
| Messages | 4, dont 2 non lus |
| Compte | `admin@ll-studio.test`, administrateur |

## 6. Conventions d'écriture

- **Traçabilité** : chaque test porte son identifiant de cas dans son intitulé, par exemple `CT-FB-03 · un anonyme ne voit pas la photo masquée d'une série`. En PHP par l'attribut `#[TestDox]`, en Vitest et Cypress dans `describe` et `it`.
- **Un test, un comportement.** L'intitulé décrit le comportement attendu, en français.
- **Arborescence** :

  ```text
  backend/tests/
    Unit/            Entity, Security, Doctrine, EventListener, State
    Functional/Api/  Visibilite, Authentification, Droits, Photo, Album,
                     Categorie, Message, Compte
    fixtures/images/
  frontend/tests/
    unit/
    composants/
  e2e/
    cypress/e2e/
  ```

- Priorités : **P0** sécurité, visibilité et authentification ; **P1** règles fonctionnelles ; **P2** confort.

## 7. Cas de test

### 7.1 Unitaires back (CT-UB)

| ID | Règles | Scénario | Résultat attendu | Prio |
|---|---|---|---|---|
| CT-UB-01 | RG-DRT-05 | `getRoles()` sur un compte sans rôle, avec `ROLE_ADMIN`, puis avec `ROLE_USER` déjà présent | contient toujours `ROLE_USER`, jamais en double | P1 |
| CT-UB-02 | RG-DRT-05 | `isAdmin()` avec et sans `ROLE_ADMIN` | vrai seulement avec | P1 |
| CT-UB-03 | RG-USR-04 | `getFullName()` avec prénom et nom, puis avec l'un des deux vide | « Prénom Nom », sans espace parasite | P2 |
| CT-UB-04 | RG-DRT-05 | `Role::values()` et `label()` | les deux rôles, libellés français | P2 |
| CT-UB-05 | RG-DRT-01 | `ContentVoter`, attributs modifier et supprimer, sur une photo puis une série : anonyme, admin, propriétaire, autre compte, contenu sans auteur | refusé, accordé, accordé, refusé, refusé | P0 |
| CT-UB-06 | RG-DRT-01 | `ContentVoter` avec un attribut inconnu, puis un sujet qui n'est ni photo ni série | abstention | P1 |
| CT-UB-07 | RG-VIS-01, RG-VIS-03 | `VisibleContentFilter` : entité non concernée, photo sans paramètre de propriétaire, photo avec | chaîne vide ; condition sur `visible` ; condition `visible` OU `owner_id` égal à l'identifiant | P0 |
| CT-UB-08 | RG-VIS-02, RG-VIS-03 | `VisibleContentFilterListener` : admin, compte, anonyme, sous-requête | filtre désactivé ; activé avec propriétaire ; activé sans ; aucune action | P0 |
| CT-UB-09 | RG-AUTH-06 | `LoginFailureListener` avec un verrouillage de 15 minutes | 429, champ `code` à 429 dans le corps, `Retry-After: 900` | P0 |
| CT-UB-10 | RG-AUTH-06 | `LoginFailureListener` avec une autre exception, puis un verrouillage sans durée | réponse inchangée ; 429 sans `Retry-After` | P1 |
| CT-UB-11 | RG-USR-02, RG-USR-03, RG-AUTH-09 | `UserPasswordHasherProcessor` avec puis sans `plainPassword` | mot de passe haché et `plainPassword` effacé ; mot de passe inchangé | P0 |
| CT-UB-12 | RG-DRT-06 | `PhotoUploadProcessor` avec un compte connecté | auteur égal au compte connecté | P0 |
| CT-UB-13 | RG-PHO-14 | `Photo::setImageFile()` avec un fichier | date de modification renseignée | P2 |
| CT-UB-14 | RG-ALB-02, RG-CAT-02 | expression du slug sur `puy-du-fou-2024`, `nogaro`, `2024`, `Puy`, `a b`, `-a`, `a-`, `a--b`, `été` | seules les trois premières valeurs sont acceptées | P1 |

### 7.2 Fonctionnels API (CT-FB)

**Visibilité, P0**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-01 | RG-VIS-01 | Anonyme, `GET` d'une photo masquée | 404 |
| CT-FB-02 | RG-VIS-01 | Anonyme, `GET /api/photos` avec 2 photos publiées et 1 masquée | 2 éléments, `totalItems` 2 |
| CT-FB-03 | RG-VIS-01, RG-VIS-04 | Anonyme, `GET` d'une série de 2 photos publiées et 1 masquée | `photos` ne contient pas la masquée, `photoCount` 2 |
| CT-FB-04 | RG-VIS-05 | Anonyme, `GET /api/albums`, série dont la couverture est masquée | `coverPhoto` nulle |
| CT-FB-05 | RG-VIS-04 | Anonyme, `GET /api/categories`, catégorie dont l'unique photo est masquée | `photoCount` 0 |
| CT-FB-06 | RG-VIS-01, RG-VIS-06 | Anonyme, série masquée : `GET` de la série, puis `GET /api/photos?albums.slug=` | 404, puis `totalItems` 0 |
| CT-FB-07 | RG-VIS-01 | Anonyme, `GET /api/photos?visible=false` | aucun élément |
| CT-FB-08 | RG-VIS-02 | Admin rejoue CT-FB-01 à CT-FB-06 | tout est visible, compteurs complets |
| CT-FB-09 | RG-VIS-03 | Compte propriétaire d'une photo masquée, puis compte tiers | le premier la voit, en 200 et dans sa série ; le second reçoit 404 |
| CT-FB-10 | RG-VIS-07 | `GET /api/photos` | l'en-tête `Vary` contient `Authorization` |

**Authentification, P0**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-11 | RG-AUTH-01 | Connexion valide | 200, `token` et `refresh_token` |
| CT-FB-12 | RG-AUTH-05, RG-AUTH-08 | Mauvais mot de passe, puis adresse inconnue | deux 401 identiques, « Identifiants invalides. » |
| CT-FB-13 | RG-AUTH-06 | Six échecs sur le même identifiant | cinq 401 puis un 429, `Retry-After: 900`, message en français |
| CT-FB-14 | RG-AUTH-06 | Après verrouillage, bon mot de passe | toujours 429 |
| CT-FB-15 | RG-AUTH-07 | Après verrouillage du compte A, connexion du compte B | 200 |
| CT-FB-16 | RG-AUTH-03 | Rafraîchissement, puis réemploi de l'ancien jeton de rafraîchissement | 200 avec un nouveau couple ; puis 401 |
| CT-FB-17 | RG-AUTH-04 | Six connexions successives du même compte | au plus 5 jetons de rafraîchissement actifs ; le sort du plus ancien est à constater |
| CT-FB-18 | RG-AUTH-02 | Jeton d'accès expiré | 401 |
| CT-FB-19 | RG-AUTH-10 | Jeton dont la signature a été altérée | 401 |
| CT-FB-20 | RG-AUTH-09 | `GET`, `POST` et `PATCH` sur `/api/users` | aucune réponse ne contient `password` ni `plainPassword` |

**Droits, P0**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-21 | RG-DRT-01, RG-DRT-02 | Matrice complète jouée par fournisseur de données : chaque ressource, chaque opération, chaque profil | le statut prévu par la matrice : 200, 201, 204, 401 ou 403 |
| CT-FB-22 | RG-DRT-03 | Compte tiers, `PATCH` puis `DELETE` d'une photo masquée qui ne lui appartient pas | 404 et non 403 |
| CT-FB-23 | RG-DRT-04 | Compte non admin : `PATCH` de son profil avec `roles: ["ROLE_ADMIN"]`, puis `GET` de son profil | rôle inchangé ; champ `roles` absent de la réponse |
| CT-FB-24 | RG-DRT-05 | Admin crée un compte avec un rôle inconnu, puis avec un rôle en double | 422 dans les deux cas |
| CT-FB-25 | RG-DRT-06 | Admin envoie une photo en précisant un autre auteur | l'auteur enregistré est l'admin |

**Photographies, P1**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-30 | RG-PHO-04, RG-PHO-10, RG-PHO-12 | Envoi d'un JPEG de 1800 × 1200 | 201, `contentUrl` en `/uploads/photos/`, fichier présent sur le disque, photo publiée |
| CT-FB-31 | RG-PHO-05 | Envoi d'un PNG, puis d'un WebP | 201 |
| CT-FB-32 | RG-PHO-05 | Envoi d'un GIF, puis d'un PDF | 422, message sur le format |
| CT-FB-33 | RG-PHO-06 | Envoi d'un fichier de plus de 8 Mo | 422, message sur le poids |
| CT-FB-34 | RG-PHO-07 | Envoi en 799 × 600, 800 × 599, 8001 × 800 | 422, message sur la dimension en cause |
| CT-FB-35 | RG-PHO-07 | Envoi en 800 × 600 exactement | 201, les bornes sont incluses |
| CT-FB-36 | RG-PHO-08 | Fichier texte renommé en `.jpg` | 422 |
| CT-FB-37 | RG-PHO-01, RG-PHO-02, RG-PHO-03, RG-PHO-04 | Sans fichier ; sans titre ; titre de 121 caractères ; sans texte alternatif ; sans catégorie | 422 avec le message prévu pour chaque champ |
| CT-FB-38 | RG-PHO-09 | Le même fichier envoyé deux fois | deux `filePath` différents, deux fichiers sur le disque |
| CT-FB-39 | RG-PHO-11 | `DELETE` d'une photo rattachée à une série | 204, fichier supprimé du disque, série conservée sans cette photo |
| CT-FB-40 | RG-PHO-14, RG-COL-03 | `PATCH` du titre ; `PATCH` de `createdAt` ; `PATCH` envoyé en `application/json` | 200 et date de modification changée ; date de création inchangée ; 415 |
| CT-FB-41 | RG-PHO-13, RG-COL-01 | 70 photos : sans paramètre ; `itemsPerPage=60` ; `itemsPerPage=100` ; `pagination=false` | 24 éléments, plus récente en tête ; 60 ; 60 ; 24 |
| CT-FB-42 | RG-PHO-16 | Chaque filtre et chaque tri, un par un | seuls les éléments attendus, dans l'ordre attendu |
| CT-FB-43 | RG-PHO-15 | Collection, puis détail | `updatedAt`, `owner` et `albums` présents au détail seulement |

**Séries, P1**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-50 | RG-ALB-01, RG-ALB-02, RG-ALB-03 | Création valide ; slug invalide ; slug en double ; sans catégorie ; titre de 121 caractères | 201 ; puis 422 avec le message prévu |
| CT-FB-51 | RG-ALB-05 | `PATCH` avec une nouvelle liste de photos | composition remplacée, et non complétée |
| CT-FB-52 | RG-ALB-06 | `DELETE` d'une série | 204, ses photos existent toujours |
| CT-FB-53 | RG-ALB-04 | Une photo rattachée à deux séries | présente dans les deux |
| CT-FB-54 | RG-ALB-07 | Collection, puis détail | `photos` absent de la collection, présent au détail |
| CT-FB-55 | RG-ALB-08 | 15 séries | 12 par page, plus récente en tête |
| CT-FB-56 | RG-ALB-09, RG-ALB-10 | Série sans couverture ; puis suppression de la photo de couverture d'une autre série | 201 sans couverture ; série conservée, `coverPhoto` nulle |
| CT-FB-57 | RG-ALB-11 | Chaque filtre et chaque tri | seuls les éléments attendus, dans l'ordre attendu |

**Catégories, P1**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-60 | RG-CAT-01, RG-CAT-02 | Création valide ; nom en double ; slug en double ; slug invalide | 201 ; puis 422 avec le message prévu |
| CT-FB-61 | RG-CAT-03 | 30 catégories | toutes renvoyées en une fois, triées par nom |
| CT-FB-62 | RG-CAT-04 | `DELETE` d'une catégorie sans rattachement | 204 |
| CT-FB-63 | RG-CAT-04, RG-Q-01 | `DELETE` d'une catégorie utilisée | suppression refusée ; code attendu **en attente de décision** |

**Messages, P1**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-70 | RG-MSG-01, RG-MSG-03 | Anonyme envoie un message valide | 201, `read` faux |
| CT-FB-71 | RG-MSG-03 | Anonyme envoie un message avec `read: true` | message enregistré non lu |
| CT-FB-72 | RG-MSG-02, RG-AUTH-08 | Chaque champ vide ou hors limites, dont un message de 9 et de 5001 caractères et une adresse invalide ; puis messages de 10 et de 5000 caractères | 422 avec le message français prévu ; puis 201 aux bornes |
| CT-FB-73 | RG-MSG-04 | Admin envoie un `PATCH` avec `read: true` et un sujet modifié | `read` vrai, sujet inchangé |
| CT-FB-74 | RG-MSG-05 | 30 messages : `?read=false`, puis sans filtre | non lus seulement ; 25 par page, plus récent en tête |

**Comptes, P1**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-80 | RG-USR-01 | Création avec une adresse existante, puis avec une adresse invalide | 422, message dédié au doublon |
| CT-FB-81 | RG-USR-02 | Création sans mot de passe, puis avec 7 caractères | 422 |
| CT-FB-82 | RG-USR-02, RG-USR-03 | `PATCH` avec un nouveau mot de passe, puis connexion avec l'ancien et le nouveau ; `PATCH` sans mot de passe | ancien refusé, nouveau accepté ; mot de passe inchangé |
| CT-FB-83 | RG-USR-04, RG-USR-05 | Création sans prénom ; liste des comptes | 422 ; liste triée par nom, `fullName` correct |
| CT-FB-84 | RG-USR-06 | Suppression d'un compte auteur de photos et de séries | photos et séries conservées, sans auteur |

**Transverse**

| ID | Règles | Scénario | Résultat attendu |
|---|---|---|---|
| CT-FB-90 | RG-COL-02 | Collection paginée sur plusieurs pages | `member`, `totalItems` et `view.next` présents |
| CT-FB-91 | RG-Q-05 | Requête avec `Accept: application/json` | **en attente de décision** |

### 7.3 Unitaires front (CT-UF)

| ID | Règles | Scénario | Résultat attendu | Prio |
|---|---|---|---|---|
| CT-UF-01 | RG-ERR-05 | `messageErreur` avec deux violations | les deux messages, séparés par une espace | P1 |
| CT-UF-02 | RG-ERR-05 | Avec `detail` seul, `description` seule, `title` seul, puis les trois | le premier présent, dans cet ordre de priorité | P1 |
| CT-UF-03 | RG-ERR-05 | Avec `null`, `undefined`, une chaîne, un objet sans `data`, une liste de violations vide | « Le service est momentanément indisponible. » | P1 |
| CT-UF-04 | RG-TUI-03 | `urlMedia` avec `null`, `undefined`, `''`, `/uploads/a.jpg`, `uploads/a.jpg` | `''`, `''`, `''`, origine + chemin, origine + `/` + chemin | P1 |
| CT-UF-05 | RG-NAV-02 | `estActif` sur `/`, `/galerie`, `/albums`, `/albums/puy-du-fou-2024`, `/albumsx` | Accueil seul sur `/` ; Albums sur `/albums` et sa sous-page ; aucune entrée sur `/albumsx` | P1 |
| CT-UF-06 | RG-NAV-01 | Liste des liens | quatre entrées, dans l'ordre et avec les libellés prévus | P2 |
| CT-UF-07 | RG-GAL-02 | `usePhotos` avec des paramètres vides, `undefined`, `false`, `0` et accentués ; URL observée sur `$api` simulé | vides et `undefined` omis ; `false` et `0` conservés ; valeurs encodées | P1 |
| CT-UF-08 | RG-SER-05 | `useAlbum` avec une collection d'une série, puis vide | `album` vaut la série ; puis `null` | P1 |

### 7.4 Composants et pages front (CT-CF)

| ID | Règles | Scénario | Résultat attendu | Prio |
|---|---|---|---|---|
| CT-CF-01 | RG-LBX-01, RG-LBX-06 | Visionneuse ouverte à l'index 1 sur 5 photos | image et texte alternatif, catégorie, titre, description, compteur « 2 / 5 », `role="dialog"`, `aria-modal`, intitulé « Photographie : <titre> » | P1 |
| CT-CF-02 | RG-LBX-02 | Suivant depuis la dernière ; précédent depuis la première ; mêmes gestes au clavier | index 0 ; index 4 ; mêmes résultats | P1 |
| CT-CF-03 | RG-LBX-03 | Une seule photo | aucun bouton de navigation | P1 |
| CT-CF-04 | RG-LBX-04 | Échap ; clic sur le fond ; clic sur la photo, la légende, une flèche | fermée ; fermée ; reste ouverte dans les trois cas | P1 |
| CT-CF-05 | RG-LBX-05 | Ouverture depuis un bouton qui a le focus ; Tab sur le dernier bouton ; Maj+Tab sur le premier ; fermeture | focus sur la fermeture ; reste captif ; revient au bouton d'origine ; défilement bloqué puis rétabli | P1 |
| CT-CF-06 | RG-LBX-07 | Ouverture à l'index 0 sur 3 photos | les photos d'index 2 et 1 sont préchargées | P2 |
| CT-CF-07 | RG-LBX-01 | Photo sans catégorie ni description | ni l'une ni l'autre n'est affichée | P2 |
| CT-CF-10 | RG-TUI-01 | `AppCartePhoto` avec et sans légende | bouton « Agrandir : <titre> », texte alternatif, événement `ouvrir` au clic ; légende seulement quand `avecLegende` est vrai | P1 |
| CT-CF-11 | RG-TUI-02 | `AppCartePhoto` prioritaire, puis non | chargement `eager`, puis `lazy` | P2 |
| CT-CF-12 | RG-SER-02 | `AppCarteAlbum` avec 1 puis 3 photos, avec puis sans couverture | lien vers `/albums/<slug>` ; « 1 photo », « 3 photos » ; image seulement avec couverture | P1 |
| CT-CF-13 | RG-GAL-02, RG-GAL-03 | `AppFiltresCategories` sur `/galerie?vue=grille&categorie=voiture` | « Tout » mène à `/galerie?vue=grille` ; « Spectacle » pose `categorie=spectacle` et garde `vue` ; `aria-current` sur l'actif | P1 |
| CT-CF-14 | RG-SER-01 | Le même composant avec `base="/albums"` | liens vers `/albums` | P1 |
| CT-CF-15 | RG-GAL-03 | `GalerieBascule` sur `/galerie?categorie=voiture` | Grille pose `vue=grille` et garde `categorie` ; Mosaïque retire `vue` et garde `categorie` ; `aria-current` sur l'actif | P1 |
| CT-CF-20 | RG-NAV-03 | `AppMenuMobile` : ouverture, puis fermeture par Échap, par clic sur le voile, par clic sur un lien | à l'ouverture, focus sur la fermeture et panneau d'identifiant `menu-mobile` ; fermé dans chaque cas, focus rendu, défilement rétabli | P1 |
| CT-CF-21 | RG-NAV-01, RG-NAV-05, RG-NAV-06 | `AppEnTete` | quatre liens, bouton EN désactivé, lien Admin, `aria-controls="menu-mobile"` sur le burger | P1 |
| CT-CF-22 | RG-NAV-01, RG-NAV-07 | `AppPiedDePage` | liens de navigation ; bloc Instagram non cliquable | P2 |
| CT-CF-30 | RG-GAL-01, RG-GAL-04 | Galerie, API simulée : 30 photos au total, 24 renvoyées | « 30 photographies » et bouton « Charger plus » ; après clic, 48 demandées ; bouton absent quand tout est chargé | P1 |
| CT-CF-31 | RG-GAL-02 | Changement de catégorie après un « Charger plus » | la quantité demandée repart à 24 | P1 |
| CT-CF-32 | RG-GAL-04, RG-GAL-05 | Galerie avec 1 photo ; clic sur sa tuile | « 1 photographie » ; visionneuse ouverte sur cette photo | P2 |
| CT-CF-33 | RG-SER-01 | `/albums` sans aucune série | message d'état vide et « 0 série » | P1 |
| CT-CF-34 | RG-ACC-01, RG-ACC-02, RG-ACC-03, RG-ACC-04 | Accueil avec 5 séries et 3 catégories ; puis sans photo, sans série ni catégorie | 4 séries affichées, deuxième photo dans la présentation, « 3 » univers ; puis bandeau sans image, sections des séries et des univers absentes | P1 |
| CT-CF-35 | RG-SER-04, RG-SER-06 | Série sans couverture ; série sans photo publiée | bandeau sur la première photo ; message d'absence de photo | P1 |
| CT-CF-40 | RG-ERR-02 | `error.vue`, 404 avec `data.detail` | « Cette page n'existe pas. » et le message fourni | P1 |
| CT-CF-41 | RG-ERR-02 | 404 sans `detail`, avec un `statusMessage` « Page not found: /x » | message générique, « /x » absent de la page | P1 |
| CT-CF-42 | RG-ERR-03, RG-ERR-04 | 500 | « Quelque chose s'est mal passé. », message fixe, pas de lien vers la galerie | P1 |
| CT-CF-43 | RG-ERR-04 | Clic sur « Retour à l'accueil » puis sur « Voir la galerie » | `clearError` appelé avec la redirection prévue ; balise `robots` à `noindex` | P1 |

### 7.5 Bout en bout (CT-E2E)

Sur le jeu de référence décrit en section 5, après rechargement des fixtures.

| ID | Règles | Parcours | Résultat attendu | Prio |
|---|---|---|---|---|
| CT-E2E-01 | RG-ACC-01, RG-ACC-02, RG-ACC-05 | Accueil | image d'ouverture égale à la photo la plus récente selon l'API ; 2 séries en vedette ; « Tout voir », « Voir la galerie », « Me réserver » et « Prendre contact » mènent aux pages prévues | P1 |
| CT-E2E-02 | RG-NAV-01, RG-NAV-02 | Navigation par l'en-tête vers les quatre rubriques | page atteinte, entrée correspondante active | P1 |
| CT-E2E-03 | RG-NAV-03 | En 375 px de large : ouvrir le menu et aller à Albums ; rouvrir, Échap | arrivée sur `/albums` ; menu fermé, focus sur le burger | P1 |
| CT-E2E-04 | RG-NAV-04 | Tab depuis le haut de la page, puis Entrée | lien « Aller au contenu » visible, puis focus sur le contenu principal | P2 |
| CT-E2E-05 | RG-GAL-01, RG-GAL-04 | Galerie | 10 tuiles, « 10 photographies », pas de bouton « Charger plus » | P1 |
| CT-E2E-06 | RG-GAL-02 | Filtre Spectacle, puis Tout, puis page précédente du navigateur | 5 tuiles et `?categorie=spectacle` ; 10 tuiles ; retour à 5 | P1 |
| CT-E2E-07 | RG-GAL-03 | Mode grille, puis filtre Voiture, puis rechargement | `?vue=grille&categorie=voiture`, état conservé après rechargement | P1 |
| CT-E2E-08 | RG-LBX-01, RG-LBX-02, RG-LBX-04, RG-LBX-05 | Galerie : ouvrir la 3e tuile, flèche droite, flèche gauche, Échap ; rouvrir, cliquer à côté de la photo | compteur « 3 / 10 », « 4 / 10 », « 3 / 10 », fermeture avec focus sur la tuile ; fermeture | P1 |
| CT-E2E-09 | RG-SER-01 | `/albums`, filtre Voiture, filtre Animaux | 2 séries ; 1 ; état vide | P1 |
| CT-E2E-10 | RG-SER-02, RG-SER-03, RG-SER-08 | Carte Puy du Fou 2024, puis « Retour aux albums » | page de la série, « 6 photos », 6 tuiles, titre d'onglet ; retour sur `/albums` | P1 |
| CT-E2E-11 | RG-VIS-01 | Parcours de toutes les pages publiques | aucun des deux titres de photos masquées n'apparaît | P0 |
| CT-E2E-12 | RG-SER-05, RG-ERR-01, RG-ERR-04 | `/albums/inexistant`, puis « Retour à l'accueil » | 404, page dans la charte avec « Cette série n'existe pas. » ; arrivée sur l'accueil | P1 |
| CT-E2E-13 | RG-ERR-02 | Adresse inconnue | 404, message générique, adresse non réaffichée | P1 |
| CT-E2E-14 | RG-SER-07 | Requête sur `/galerie/puy-du-fou-2024` sans suivre la redirection | 301 vers `/albums/puy-du-fou-2024` | P1 |
| CT-E2E-15 | RG-IDX-01, RG-IDX-02 | `robots.txt` ; titres d'onglet et langue | `Disallow: /admin` ; titres terminés par « · LL Studio », `lang="fr"` | P2 |

## 8. Critères d'entrée et de sortie

**Pour commencer une campagne** : outillage de la phase 0 en place, base `llstudio_test` créée et migrée, pile Docker démarrée, fixtures rechargées pour les tests de bout en bout.

**Pour clore une phase** :

- tous les cas P0 de la phase passent ;
- aucun test instable : trois exécutions consécutives donnent le même résultat ;
- chaque règle établie du périmètre est vérifiée par au moins un cas ;
- les cas liés à un point à trancher sont marqués en attente, avec leur identifiant `RG-Q`.

**Quand un test échoue sur une règle établie**, c'est un défaut de l'application : on ouvre une issue et on corrige sur `main`. On ne corrige pas sur `tests`, et on n'assouplit pas le test.

## 9. Ordre de réalisation

| Phase | Contenu | Cas |
|---|---|---|
| 0 | Outillage : base de test, secrets de l'environnement de test, configuration de test, images de référence ; Vitest ; projet Cypress. Un commit par brique. | aucun |
| 1 | Sécurité côté API : visibilité, authentification, droits | CT-UB-05 à CT-UB-12, CT-FB-01 à CT-FB-25 |
| 2 | Ressources et validation côté API | CT-UB-01 à CT-UB-04, CT-UB-13, CT-UB-14, CT-FB-30 à CT-FB-91 |
| 3 | Front, fonctions et composants | CT-UF, CT-CF |
| 4 | Parcours de bout en bout | CT-E2E |
| 5 | Rapport d'exécution, qui devient le jeu d'essai du dossier (#51) | aucun |

## 10. Risques

| Risque | Mesure |
|---|---|
| La branche `tests` décroche de `main` faute de synchronisation. | Synchroniser après chaque fusion sur `main` ; à terme, CI sur `tests` (#37). |
| Conflits sur les fichiers de verrouillage du front. | Prendre la version de `main` et régénérer dans le conteneur. |
| Une évolution des fixtures sur `main` casse les tests de bout en bout. | Attendus calculés à partir de l'API quand c'est possible. |
| Le premier passage de PHPUnit échoue sur des dépréciations existantes. | Les traiter comme des issues sur `main`. |
| Un test de visibilité écrit au niveau Doctrine passe à tort. | Visibilité testée uniquement en fonctionnel. |
| Tests d'envoi lents à cause des images. | Images de référence petites, gros fichier généré à la volée. |

## 11. Décisions à valider

| N° | Décision | Recommandation |
|---|---|---|
| D1 | Où exécuter Cypress en local. | Sur la machine hôte, projet `e2e/` autonome. Le conteneur sera réservé à la CI. |
| D2 | Les tests de bout en bout rechargent les fixtures dans la base de développement, ce qui efface son contenu. | Acceptable tant que cette base ne contient que les fixtures. À revoir si du contenu réel y est saisi. |
| D3 | Les points à trancher (RG-Q-01 à RG-Q-12). | Laisser leurs tests en attente et ouvrir une issue par point retenu. |
| D4 | Mesure de la couverture de code. | Ajouter `pcov` à l'image PHP de développement. Cela modifie le Dockerfile, donc passe par `main`. |
| D5 | Mode de synchronisation de la branche. | Fusion de `origin/main` dans `tests`, jamais de rebase. |

## 12. Modèle de rapport d'exécution

À remplir à chaque campagne. Ce tableau constitue le jeu d'essai attendu par le dossier professionnel (#51) : données en entrée, résultat attendu, résultat obtenu.

| Cas | Date | Entrée | Résultat attendu | Résultat obtenu | Statut |
|---|---|---|---|---|---|
| CT-FB-01 | | photo masquée, appel anonyme | 404 | | |
