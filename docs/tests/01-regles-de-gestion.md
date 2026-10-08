# Règles de gestion de LL Studio

Ce document recense les règles que l'application applique aujourd'hui. Elles ont été établies par lecture du code de la branche `main` au commit `35c0e0f` (8 octobre 2026), puis vérifiées sur l'application en fonctionnement quand c'était possible.

Il sert de référence aux tests : **un test vérifie une règle**. Si un test échoue, soit le code a un défaut, soit la règle doit être révisée. On ne modifie jamais un test pour qu'il passe sans avoir tranché lequel des deux.

## Conventions

- Chaque règle porte un identifiant stable, `RG-<domaine>-<numéro>`, repris par le plan de tests.
- La colonne **Source** indique où la règle est codée, pour qu'un écart puisse être retrouvé.
- Les comportements constatés dont l'intention n'est pas certaine sont regroupés à la fin, en **points à trancher** (`RG-Q-xx`). Ils ne doivent pas être figés par un test avant décision.

Profils utilisés dans tout le document :

| Profil | Définition |
|---|---|
| Anonyme | visiteur sans jeton |
| Compte | compte authentifié qui n'a que `ROLE_USER` |
| Propriétaire | compte authentifié, auteur du contenu concerné |
| Admin | compte authentifié qui a `ROLE_ADMIN` |

---

## 1. Visibilité des contenus (RG-VIS)

| ID | Règle | Source |
|---|---|---|
| RG-VIS-01 | Une photo ou une série masquée (`visible = false`) n'est jamais exposée à un visiteur anonyme, quel que soit le chemin d'accès : son point d'entrée propre répond 404, et elle est absente des collections, des relations imbriquées, des couvertures et des filtres par jointure. | `VisibleContentExtension`, `VisibleContentFilter` |
| RG-VIS-02 | L'administrateur voit tout le contenu, publié ou non. | `ContentVisibility::seesEverything` |
| RG-VIS-03 | Un compte non administrateur voit le contenu publié, plus le contenu masqué dont il est propriétaire, et rien d'autre. | `VisibleContentFilter`, `VisibleContentExtension` |
| RG-VIS-04 | Les compteurs `photoCount` d'une série et d'une catégorie ne dénombrent que ce que l'appelant peut consulter. | `Album::getPhotoCount`, `Category::getPhotoCount` |
| RG-VIS-05 | Si la couverture d'une série est une photo que l'appelant ne peut pas voir, `coverPhoto` est renvoyée nulle. | `VisibleContentFilter` |
| RG-VIS-06 | Les photos d'une série masquée ne sont pas listables par le filtre `?albums.slug=`. | `VisibleContentFilter` |
| RG-VIS-07 | Les réponses de l'API varient selon l'en-tête `Authorization` (en-tête `Vary`), pour qu'un cache partagé ne serve jamais la réponse d'un administrateur à un anonyme. | `api_platform.yaml` |

## 2. Authentification (RG-AUTH)

| ID | Règle | Source |
|---|---|---|
| RG-AUTH-01 | On se connecte par adresse e-mail et mot de passe sur `POST /api/login`. En cas de succès, l'API renvoie un jeton d'accès et un jeton de rafraîchissement. | `security.yaml` |
| RG-AUTH-02 | Le jeton d'accès est valable une heure. Au-delà, il est refusé. | `lexik_jwt_authentication.yaml` |
| RG-AUTH-03 | Le jeton de rafraîchissement est valable 30 jours. Il est à usage unique : chaque rafraîchissement le consomme et en délivre un nouveau, dont la durée repart à 30 jours. | `gesdinet_jwt_refresh_token.yaml` |
| RG-AUTH-04 | Un compte a au plus 5 jetons de rafraîchissement actifs, soit 5 appareils connectés. | `gesdinet_jwt_refresh_token.yaml` |
| RG-AUTH-05 | Des identifiants refusés donnent un 401 avec le message « Identifiants invalides. », que l'adresse existe ou non : la réponse ne permet pas de savoir si un compte existe. | `security.yaml`, traductions Symfony |
| RG-AUTH-06 | Au-delà de 5 échecs en 15 minutes pour un même identifiant, la connexion est refusée en 429, avec l'en-tête `Retry-After: 900` et le message « Trop de tentatives de connexion échouées, veuillez réessayer dans 15 minutes. ». Le refus vaut même si le bon mot de passe est fourni avant la fin du délai. | `security.yaml` (`login_throttling`), `LoginFailureListener` |
| RG-AUTH-07 | Le verrouillage d'un identifiant n'empêche pas un autre identifiant de se connecter depuis la même adresse. | `login_throttling` |
| RG-AUTH-08 | Les messages d'erreur fournis par Symfony sont en français. | `translation.yaml` (`default_locale: fr`) |
| RG-AUTH-09 | Le mot de passe n'est jamais renvoyé par l'API, ni en clair ni haché. | `User` (aucun groupe sur `password`, `plainPassword` en écriture seule) |
| RG-AUTH-10 | Un jeton absent, altéré ou mal signé donne un 401 sur toute opération qui exige une authentification. | Lexik |

## 3. Droits (RG-DRT)

### RG-DRT-01 : matrice des droits

| Ressource | Opération | Anonyme | Compte | Propriétaire | Admin |
|---|---|---|---|---|---|
| Photo | lister, consulter | oui | oui | oui | oui |
| Photo | créer | non | non | non | oui |
| Photo | modifier, supprimer | non | non | oui | oui |
| Série | lister, consulter | oui | oui | oui | oui |
| Série | créer | non | non | non | oui |
| Série | modifier, supprimer | non | non | oui | oui |
| Catégorie | lister, consulter | oui | oui | oui | oui |
| Catégorie | créer, modifier, supprimer | non | non | non | oui |
| Message | envoyer | oui | oui | oui | oui |
| Message | lister, consulter, marquer lu, supprimer | non | non | non | oui |
| Compte | lister, créer, supprimer | non | non | non | oui |
| Compte | consulter, modifier | non | le sien seulement | le sien seulement | oui |

Source : attributs `security` des `#[ApiResource]`, `ContentVoter`.

| ID | Règle | Source |
|---|---|---|
| RG-DRT-02 | Sans jeton, une opération protégée répond 401. Avec un jeton valide mais des droits insuffisants, elle répond 403. | Symfony Security |
| RG-DRT-03 | Un contenu masqué que l'appelant n'a pas le droit de voir répond 404, y compris en modification ou en suppression : son existence n'est pas révélée. | `VisibleContentExtension::applyToItem` |
| RG-DRT-04 | Seul un administrateur peut lire ou modifier les rôles d'un compte. Un compte qui modifie son propre profil ne peut pas s'attribuer `ROLE_ADMIN`. | `User::$roles` (`security` sur la propriété) |
| RG-DRT-05 | Les rôles acceptés sont `ROLE_USER` et `ROLE_ADMIN`, sans doublon. Tout compte possède au moins `ROLE_USER`, et `ROLE_ADMIN` inclut `ROLE_USER`. | `Role`, `User::getRoles`, `role_hierarchy` |
| RG-DRT-06 | L'auteur d'une photo est le compte qui l'envoie. Il n'est pas transmissible dans la requête. | `PhotoUploadProcessor` |

## 4. Photographies (RG-PHO)

| ID | Règle | Source |
|---|---|---|
| RG-PHO-01 | Le titre est obligatoire, 120 caractères au plus. | `Photo::$title` |
| RG-PHO-02 | Le texte alternatif est obligatoire. Message : « Le texte alternatif est obligatoire pour l'accessibilité. » | `Photo::$alt` |
| RG-PHO-03 | Une photo appartient à exactement une catégorie. Message si absente : « Une photo doit appartenir à une catégorie. » | `Photo::$category` |
| RG-PHO-04 | À la création, le fichier image est obligatoire et transmis en `multipart/form-data` dans `imageFile`. Message si absent : « Une photo doit être accompagnée de son fichier image. » | `Photo::$imageFile`, opération `Post` |
| RG-PHO-05 | Seuls les formats JPEG, PNG et WebP sont acceptés. | `Assert\Image` |
| RG-PHO-06 | Le fichier pèse 8 Mo au plus. | `Assert\Image` |
| RG-PHO-07 | L'image mesure au moins 800 × 600 pixels et au plus 8000 × 8000 pixels. | `Assert\Image` |
| RG-PHO-08 | Un fichier qui se présente comme une image mais n'est pas décodable est refusé. | `Assert\Image` (`detectCorrupted`) |
| RG-PHO-09 | Le fichier stocké porte un nom dérivé du nom d'origine, suffixé d'un identifiant unique : deux envois du même fichier ne s'écrasent pas. | `vich_uploader.yaml` (`SmartUniqueNamer`) |
| RG-PHO-10 | L'API expose une URL publique `contentUrl` de la forme `/uploads/photos/<fichier>`. Elle est calculée et ne peut pas être écrite. | `PhotoContentUrlNormalizer` |
| RG-PHO-11 | Supprimer une photo supprime son fichier du disque. Remplacer son fichier supprime l'ancien. | `vich_uploader.yaml` (`delete_on_remove`, `delete_on_update`) |
| RG-PHO-12 | Une photo est publiée par défaut à sa création. | `Photo::$visible` |
| RG-PHO-13 | La collection est triée de la plus récente à la plus ancienne, 24 éléments par page. | `#[ApiResource]` de `Photo` |
| RG-PHO-14 | Toute modification met à jour la date de modification. La date de création n'est pas modifiable par l'API. | `Photo::touch`, groupes de sérialisation |
| RG-PHO-15 | La vue détail ajoute la date de modification, l'auteur et les séries de la photo ; la collection ne les contient pas. | groupe `photo:item:read` |
| RG-PHO-16 | Filtres disponibles : `category.slug`, `albums.slug`, `visible`, `createdAt[after]` et `createdAt[before]`, recherche insensible à la casse sur `title` et `description`, tri par `order[createdAt]` et `order[title]`. | `#[ApiFilter]` de `Photo` |

## 5. Séries (RG-ALB)

| ID | Règle | Source |
|---|---|---|
| RG-ALB-01 | Le titre est obligatoire, 120 caractères au plus. | `Album::$title` |
| RG-ALB-02 | Le slug est obligatoire, unique, 140 caractères au plus, composé de minuscules, de chiffres et de tirets simples, sans tiret en début ni en fin. Messages : « Le slug ne peut contenir que des minuscules, des chiffres et des tirets. » et « Ce slug est déjà utilisé par un autre album. » | `Album::$slug` |
| RG-ALB-03 | Une série appartient à exactement une catégorie. Message si absente : « Un album doit appartenir à une catégorie. » | `Album::$category` |
| RG-ALB-04 | Une photo peut figurer dans plusieurs séries. | relation ManyToMany |
| RG-ALB-05 | Transmettre `photos` en modification remplace intégralement la composition de la série. | opération `Patch` |
| RG-ALB-06 | Supprimer une série ne supprime pas ses photos, seulement leur rattachement. | `album_photo` (`ON DELETE CASCADE`) |
| RG-ALB-07 | La collection ne contient pas la liste des photos, seulement la couverture et le compteur ; la vue détail embarque la liste. | groupes `album:read` et `album:item:read` |
| RG-ALB-08 | La collection est triée de la plus récente à la plus ancienne, 12 éléments par page. | `#[ApiResource]` de `Album` |
| RG-ALB-09 | La couverture est facultative. | `Album::$coverPhoto` |
| RG-ALB-10 | Supprimer la photo de couverture laisse la série sans couverture, sans supprimer la série. | `cover_photo_id` (`ON DELETE SET NULL`) |
| RG-ALB-11 | Filtres disponibles : `slug`, `category.slug`, `visible`, recherche sur `title`, tri par `order[createdAt]` et `order[title]`. | `#[ApiFilter]` de `Album` |

## 6. Catégories (RG-CAT)

| ID | Règle | Source |
|---|---|---|
| RG-CAT-01 | Le nom est obligatoire, unique, 50 caractères au plus. Message en cas de doublon : « Cette catégorie existe déjà. » | `Category::$name` |
| RG-CAT-02 | Le slug est obligatoire, unique, 60 caractères au plus, au même format que celui des séries. Message en cas de doublon : « Ce slug est déjà utilisé. » | `Category::$slug` |
| RG-CAT-03 | La collection n'est pas paginée et est triée par nom croissant. | `#[ApiResource]` de `Category` |
| RG-CAT-04 | Une catégorie à laquelle sont rattachées des photos ou des séries ne peut pas être supprimée. | `ON DELETE RESTRICT` |

## 7. Messages de contact (RG-MSG)

| ID | Règle | Source |
|---|---|---|
| RG-MSG-01 | N'importe quel visiteur peut envoyer un message. C'est la seule opération publique de la ressource. | opération `Post` |
| RG-MSG-02 | Nom obligatoire, 100 caractères au plus. Adresse e-mail obligatoire et valide, 180 au plus. Sujet obligatoire, 150 au plus. Message obligatoire, entre 10 et 5000 caractères. Messages : « Merci d’indiquer votre nom. », « Merci d’indiquer votre adresse e-mail. », « Cette adresse e-mail n’est pas valide. », « Merci d’indiquer un sujet. », « Le message ne peut pas être vide. » (avec l'apostrophe typographique de l'entité) | `MessageContact` |
| RG-MSG-03 | Un message est enregistré non lu. Un visiteur ne peut pas l'envoyer déjà marqué lu. | `MessageContact::$read`, groupe `message:write` |
| RG-MSG-04 | Seul le marqueur « lu » est modifiable : le contenu d'un message reçu ne peut pas être réécrit. | groupe `message:update` |
| RG-MSG-05 | La collection est triée du plus récent au plus ancien, 25 éléments par page, filtrable par `read`. | `#[ApiResource]` de `MessageContact` |

## 8. Comptes (RG-USR)

| ID | Règle | Source |
|---|---|---|
| RG-USR-01 | L'adresse e-mail est obligatoire, valide, unique, 180 caractères au plus. Message en cas de doublon : « Un compte existe déjà avec cette adresse e-mail. » | `User::$email` |
| RG-USR-02 | Le mot de passe est obligatoire à la création, 8 caractères au moins. Il est transmis en clair dans `plainPassword`, haché avant enregistrement, puis oublié. | `User::$plainPassword`, `UserPasswordHasherProcessor` |
| RG-USR-03 | Une modification sans `plainPassword` laisse le mot de passe inchangé. | `UserPasswordHasherProcessor` |
| RG-USR-04 | Prénom et nom sont obligatoires, 50 caractères au plus. Le nom complet est « Prénom Nom ». | `User`, `User::getFullName` |
| RG-USR-05 | Les comptes sont triés par nom. | `#[ApiResource]` de `User` |
| RG-USR-06 | Supprimer un compte ne supprime ni ses photos ni ses séries, qui perdent simplement leur auteur. | `owner_id` (`ON DELETE SET NULL`) |

## 9. Collections, règles transverses de l'API (RG-COL)

| ID | Règle | Source |
|---|---|---|
| RG-COL-01 | Les collections paginées acceptent `?itemsPerPage=` jusqu'à 60. Le client ne peut pas désactiver la pagination. | `api_platform.yaml` |
| RG-COL-02 | Les réponses sont en JSON-LD : une collection expose `member`, `totalItems` et, quand il y a plusieurs pages, `view`. | API Platform |
| RG-COL-03 | Une modification partielle exige l'en-tête `Content-Type: application/merge-patch+json` ; tout autre type est refusé. | API Platform (`patch_formats` par défaut) |

---

## 10. Navigation publique (RG-NAV)

| ID | Règle | Source |
|---|---|---|
| RG-NAV-01 | La navigation publique compte quatre entrées, dans cet ordre : Accueil, Galerie, Albums, Contact. Les mêmes entrées figurent dans l'en-tête, le menu mobile et le pied de page. | `useNavigationPublique` |
| RG-NAV-02 | L'entrée Accueil n'est active que sur `/` exactement. Les autres restent actives sur leurs sous-pages (`/albums` est active sur `/albums/puy-du-fou-2024`), mais pas sur une adresse qui ne fait que commencer pareil (`/albums` n'est pas active sur `/albumsx`). L'entrée active porte `aria-current="page"`. | `useNavigationPublique::estActif` |
| RG-NAV-03 | Le menu mobile s'ouvre par le bouton burger et se ferme par le bouton de fermeture, la touche Échap, un clic sur le voile ou un clic sur un lien. Pendant l'ouverture, le focus clavier reste dans le panneau et la page ne défile pas. À la fermeture, le focus revient sur le bouton qui l'avait ouvert. Le bouton burger référence le panneau par `aria-controls`. | `AppMenuMobile`, `AppEnTete` |
| RG-NAV-04 | Chaque page propose un lien d'évitement « Aller au contenu ». | `layouts/default.vue` |
| RG-NAV-05 | Le bouton de langue EN est affiché mais désactivé. | `AppEnTete` |
| RG-NAV-06 | Un lien « Admin » figure dans l'en-tête et dans le menu mobile. | `AppEnTete`, `AppMenuMobile` |
| RG-NAV-07 | Tant qu'aucun lien Instagram n'est renseigné, le bloc Instagram du pied de page s'affiche sans être cliquable. | `AppPiedDePage` |

## 11. Accueil (RG-ACC)

| ID | Règle | Source |
|---|---|---|
| RG-ACC-01 | Le bandeau d'ouverture affiche la photo publiée la plus récente. Sans photo, il s'affiche sans image. | `pages/index.vue`, `AccueilHero` |
| RG-ACC-02 | La section « Albums en vedette » affiche au plus 4 séries, les plus récentes, et disparaît s'il n'y en a aucune. Son lien « Tout voir » mène à `/albums`. | `pages/index.vue` |
| RG-ACC-03 | Le bandeau des univers n'apparaît que s'il existe au moins une catégorie. | `pages/index.vue` |
| RG-ACC-04 | La section de présentation utilise la deuxième photo la plus récente et annonce le nombre d'univers, égal au nombre de catégories. | `AccueilApropos` |
| RG-ACC-05 | « Voir la galerie » mène à `/galerie`. « Me réserver » et « Prendre contact » mènent à `/contact`. | `AccueilHero`, `AccueilAppel` |

## 12. Galerie (RG-GAL)

| ID | Règle | Source |
|---|---|---|
| RG-GAL-01 | La galerie affiche les photos publiées de la plus récente à la plus ancienne, par tranches de 24. Le bouton « Charger plus » ajoute une tranche et n'apparaît que s'il reste des photos. | `pages/galerie/index.vue` |
| RG-GAL-02 | Le filtre par catégorie vit dans l'URL (`?categorie=<slug>`). « Tout » retire le filtre. Changer de filtre repart de la première tranche. | `AppFiltresCategories`, `pages/galerie/index.vue` |
| RG-GAL-03 | Le mode d'affichage vit dans l'URL : mosaïque par défaut, grille avec `?vue=grille`. Changer de filtre conserve le mode, changer de mode conserve le filtre. | `GalerieBascule`, `AppFiltresCategories` |
| RG-GAL-04 | Le compteur « N photographie(s) » est accordé et égal au total renvoyé par l'API pour le filtre courant. | `pages/galerie/index.vue` |
| RG-GAL-05 | Cliquer sur une tuile ouvre la visionneuse sur cette photo. | `pages/galerie/index.vue` |

## 13. Séries côté site (RG-SER)

| ID | Règle | Source |
|---|---|---|
| RG-SER-01 | `/albums` liste les séries publiées, 24 au plus, avec un filtre par catégorie dans l'URL et un compteur « N série(s) » accordé. Sans résultat, la page affiche « Aucune série publiée dans cette catégorie pour le moment. » | `pages/albums/index.vue` |
| RG-SER-02 | Une carte de série mène à `/albums/<slug>` et affiche la couverture si elle existe, la catégorie, le nombre de photos accordé (« 1 photo », « 3 photos ») et le titre. | `AppCarteAlbum` |
| RG-SER-03 | La page d'une série affiche son titre, sa catégorie, son nombre de photos accordé, sa description si elle existe, un lien « Retour aux albums » et la mosaïque de ses photos publiées, 48 au plus. Les tuiles n'y portent pas de légende. | `pages/albums/[slug].vue` |
| RG-SER-04 | Sans couverture visible, la page d'une série utilise sa première photo publiée en bandeau. | `pages/albums/[slug].vue` |
| RG-SER-05 | Une série inconnue répond 404 avec le message « Cette série n'existe pas. » | `pages/albums/[slug].vue` |
| RG-SER-06 | Une série sans photo publiée affiche « Cette série ne contient encore aucune photographie publiée. » | `pages/albums/[slug].vue` |
| RG-SER-07 | L'ancienne adresse `/galerie/<slug>` redirige en 301 vers `/albums/<slug>`. | `pages/galerie/[slug].vue` |
| RG-SER-08 | Le titre de la page est le titre de la série. Sa description pour les moteurs est la description de la série, à défaut « Série photographique <titre>. » | `pages/albums/[slug].vue` |

## 14. Visionneuse (RG-LBX)

| ID | Règle | Source |
|---|---|---|
| RG-LBX-01 | La visionneuse s'ouvre sur la photo choisie et affiche l'image avec son texte alternatif, sa catégorie si elle existe, son titre, sa description si elle existe, et un compteur « i / n ». | `AppVisionneuse` |
| RG-LBX-02 | On passe à la photo précédente ou suivante par les boutons ou par les flèches du clavier. La navigation boucle : après la dernière vient la première, avant la première vient la dernière. | `AppVisionneuse::deplacer` |
| RG-LBX-03 | Les boutons de navigation sont absents quand il n'y a qu'une photo. | `AppVisionneuse` |
| RG-LBX-04 | La visionneuse se ferme par le bouton de fermeture, la touche Échap ou un clic hors de la photo. Un clic sur la photo, sa légende ou une commande ne la ferme pas. | `AppVisionneuse::surClic` |
| RG-LBX-05 | À l'ouverture, le focus va sur le bouton de fermeture. Il reste captif pendant l'ouverture et revient à son point de départ à la fermeture. La page ne défile pas pendant l'ouverture. | `AppVisionneuse` |
| RG-LBX-06 | La visionneuse est un dialogue modal accessible : `role="dialog"`, `aria-modal="true"`, intitulé « Photographie : <titre> ». | `AppVisionneuse` |
| RG-LBX-07 | Les photos voisines de celle affichée sont préchargées. | `AppVisionneuse::prechargerVoisines` |

## 15. Tuiles, cartes et médias (RG-TUI)

| ID | Règle | Source |
|---|---|---|
| RG-TUI-01 | Une tuile photo est un bouton intitulé « Agrandir : <titre> ». Elle affiche l'image avec son texte alternatif et, sauf dans la mosaïque d'une série, une légende faite de la catégorie et du titre. | `AppCartePhoto` |
| RG-TUI-02 | Les premières images d'une page sont chargées immédiatement, les suivantes à l'approche du défilement : la première sur l'accueil, les 3 premières en galerie et sur une série, les 4 premières sur `/albums`. | pages, `AppCartePhoto`, `AppCarteAlbum` |
| RG-TUI-03 | L'URL d'un média est l'origine publique de l'API suivie du chemin renvoyé par l'API. Un chemin vide donne une chaîne vide, un chemin sans « / » initial est complété. | `useApi::urlMedia` |

## 16. Erreurs (RG-ERR)

| ID | Règle | Source |
|---|---|---|
| RG-ERR-01 | Toute erreur affiche une page dans la charte du site, avec l'en-tête, le pied de page et le lien d'évitement, et conserve le vrai code HTTP. | `error.vue` |
| RG-ERR-02 | Sur une 404, la page affiche « Cette page n'existe pas. » suivi du message fourni par l'application s'il existe, à défaut « Cette adresse ne correspond à aucune page du site. ». L'adresse demandée n'est jamais réaffichée. | `error.vue` |
| RG-ERR-03 | Sur toute autre erreur, la page affiche « Quelque chose s'est mal passé. » et un message fixe. Aucune trace technique n'apparaît en production. | `error.vue` |
| RG-ERR-04 | La page d'erreur n'est pas indexable. Elle propose toujours « Retour à l'accueil », et « Voir la galerie » sur une 404 seulement. Ces liens font réellement quitter l'écran d'erreur. | `error.vue` |
| RG-ERR-05 | Une erreur d'API est traduite en texte lisible : les messages de validation mis bout à bout, à défaut le détail, la description ou le titre de l'erreur, à défaut « Le service est momentanément indisponible. » | `useApi::messageErreur` |

## 17. Référencement (RG-IDX)

| ID | Règle | Source |
|---|---|---|
| RG-IDX-01 | `robots.txt` interdit l'indexation de `/admin`. | `public/robots.txt` |
| RG-IDX-02 | Le titre de chaque page se termine par « · LL Studio » et la langue du document est le français. | `nuxt.config.ts` |

---

## 18. Points à trancher (RG-Q)

Comportements constatés dont l'intention n'est pas établie. Les tests correspondants restent **en attente** tant qu'une décision n'est pas prise, pour ne pas figer un défaut.

| ID | Constat | Question |
|---|---|---|
| RG-Q-01 | Supprimer une catégorie encore utilisée bute sur une contrainte de base de données. La réponse HTTP n'est pas maîtrisée et risque d'être une 500. | Quelle réponse attend-on : 409 ou 422, avec quel message ? |
| RG-Q-02 | Le fichier image d'une photo masquée reste téléchargeable par son URL directe, alors que la photo répond 404. | Faut-il protéger les fichiers ? |
| RG-Q-03 | L'auteur (`owner`) d'une photo ou d'une série est exposé à un visiteur anonyme sous forme d'IRI `/api/users/<id>`. | Faut-il le retirer des vues publiques ? |
| RG-Q-04 | Les filtres de la galerie et de `/albums` proposent toutes les catégories, y compris vides, et la galerie n'a pas d'état vide (issue #24). | Masquer les catégories vides, ou prévoir un état vide ? |
| RG-Q-05 | La description de l'API annonce 20 éléments par page et un format JSON simple. En réalité les tailles sont fixées par ressource et `Accept: application/json` est refusé. | Corriger la documentation ou la configuration ? |
| RG-Q-06 | Les slugs de série et de catégorie ne sont pas générés à partir du titre : ils doivent être saisis. | Faut-il les générer automatiquement ? |
| RG-Q-07 | Une série créée par l'API n'a pas d'auteur, contrairement à une photo. | Doit-elle en avoir un ? |
| RG-Q-08 | Un compte non administrateur ne peut rien créer, mais peut modifier ce qu'il possède. Avec un seul administrateur, la notion de propriétaire ne sert pas aujourd'hui. | Le modèle à plusieurs auteurs est-il voulu ? |
| RG-Q-09 | Un administrateur peut supprimer son propre compte, y compris le dernier administrateur. | Faut-il l'interdire ? |
| RG-Q-10 | L'envoi de message n'a ni limitation de débit ni protection contre les robots. | À traiter avec la page Contact (issue #41) ? |
| RG-Q-11 | La couverture d'une série peut être une photo masquée ou une photo qui n'appartient pas à la série. | Faut-il le refuser à la validation ? |
| RG-Q-12 | « Albums en vedette » affiche les 4 séries les plus récentes, sans notion de mise en avant (issue #30). | Comportement provisoire assumé ? |

## 19. Hors périmètre

Ne sont pas couverts ici, faute d'exister encore : le back-office (issues #25 à #30), le formulaire de contact (#41), les mentions légales (#43) et l'internationalisation (#50). Leurs règles seront ajoutées à ce document au fil des itérations.
