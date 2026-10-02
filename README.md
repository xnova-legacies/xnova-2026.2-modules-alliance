# Alliance

[![Contrôles du module](https://github.com/xnova-legacies/xnova-2026.2-modules-alliance/actions/workflows/controles.yml/badge.svg)](https://github.com/xnova-legacies/xnova-2026.2-modules-alliance/actions/workflows/controles.yml)
[![Coeur](https://img.shields.io/badge/coeur-%3E%3D%201.0.1-8892BF.svg)](https://github.com/xnova-legacies/xnova-2026.2)
[![Minimum PHP](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)
[![Licence](https://img.shields.io/badge/licence-GPL--3.0-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

Le module **alliance** donne au jeu son alliance : la fonder, la présenter, y poser sa
candidature, gérer ses membres et leurs rôles, publier une circulaire, fixer ses lois, et
chercher une alliance pour la rejoindre.

C'est un **module** de XNova : il se dépose dans `modules/alliance/` et le Coeur fait le
reste. Retiré, le jeu tourne sans alliance — ses pages, ses routes JSON et son entrée de
menu disparaissent avec lui, sans rien casser ailleurs.

## 📋 Ce qu'il faut

| | |
|---|---|
| **Coeur de l'application** | XNova `>= 1.0.1` — c'est ce que déclare `dependencies.core` du manifeste, et le Coeur refuse le module en dessous |
| **PHP / MySQL** | ceux du Coeur : **PHP 8.3** et **MySQL 5.7** |
| **Docker** | facultatif, pour le déploiement rapide ci-dessous |

## 🚀 Déploiement rapide

Pour éprouver le module dans un Coeur de développement (`..\xnova-2026.2` par défaut),
sans passer par la page d'administration des modules :

```powershell
.\deploy.ps1                            # copie, migrations, registre des modules
.\deploy.ps1 -Core D:\jeu               # un autre dossier de Coeur
.\deploy.ps1 -FilesOnly                 # copie seulement (pas de base)
.\deploy.ps1 -Tests                     # + les tests du module dans le conteneur
```

Le script copie les fichiers dans `modules/alliance/` du Coeur, applique les migrations du
module, puis sème le registre des modules. Le module naît **allumé** : il n'y a rien à
cocher. Il ne touche à rien d'autre — ni au Coeur, ni à `configs/`.

En jeu : <http://localhost:8080/game/alliance>

À la main, les trois mêmes gestes sont :

1. copier ce dossier dans `modules/alliance/` du Coeur (le Coeur monte ses sources en
   volume : la copie **est** le déploiement) ;
2. `docker compose exec app php db/migrate.php migrate` — le migrateur lit aussi
   `modules/*/db/migrations/` ;
3. `docker compose exec app php db/modules.php` — le registre que le jeu interroge.

## 🧩 Ce que le module apporte

### Les pages

| Adresse | Ce qu'on y fait |
|---|---|
| `/game/alliance` | l'alliance du joueur : accueil, menu, membres, administration, circulaire |
| `/game/alliance/info` | la présentation d'une alliance, et la candidature |

Ses anciennes adresses suivent : `alliance.php` et `ainfo.php` mènent aux mêmes pages.

### Les routes JSON

`/game/api/alliance/…` : `make` (fonder), `apply` (candidater), `request` (traiter une
candidature), `rename` (renommer), `leave` (quitter), `circular` (publier une circulaire).
Comme partout dans le jeu, le formulaire classique reste : le tout fonctionne **sans
JavaScript**.

### Le schéma

Il voyage avec le module (`db/migrations/001_alliance.php`) :

- la table **`alliance`** (`IF NOT EXISTS` : rien à faire si un jeu ancien l'a déjà) ;
- **six colonnes** sur `users` : `ally_id`, `ally_name`, `ally_request`,
  `ally_request_text`, `ally_register_time`, `ally_rank_id` — une instruction par colonne,
  parce que MySQL 5.7 n'a pas de `ADD COLUMN IF NOT EXISTS`, et le rejeu est inoffensif.

C'est ici que la propriété s'écrit : ces tables et ces colonnes appartiennent au module, et
non plus au schéma du Coeur.

### La permission

`module.alliance` — un rôle qui la porte **ouvre** le module, un rôle qui ne la porte pas le
ferme à ses comptes.

## ✅ Tests et qualité

Le module est éprouvé **dans le Coeur**, jamais tout seul : sa place est un point de
surcharge, pas un dépôt isolé.

```powershell
.\deploy.ps1 -Tests                               # les tests du module, dans le conteneur
docker compose exec app composer test             # la suite complète (Coeur + module)
docker compose exec app composer cs               # le style (PSR-12)
```

Le workflow de ce dépôt (`.github/workflows/controles.yml`) fait la même chose à chaque
poussée : il pose le module dans un checkout du **Coeur public**
(`xnova-legacies/xnova-2026.2`) et lance ses contrôles — les tests du module y sont
découverts (`phpunit.xml` porte `<directory>modules</directory>`) comme son style
(`phpcs.xml` porte `<file>modules</file>`). Deux références sont éprouvées : `develop` pour
voir venir la casse (sans bloquer) et `master`, la branche qui publie, dont un échec bloque.

## 🗂️ Comment c'est fait

```
controllers/    les pages du jeu et les routes JSON
services/       la logique métier (une classe par domaine)
repositories/   l'accès aux données (requêtes préparées)
entities/       les enregistrements
view/           les gabarits .tpl, un par bloc affiché
language/fr/    les libellés (le module voyage avec ses traductions)
db/migrations/  son schéma, chez lui
tests/          PHPUnit ; le Coeur les découvre tout seul
package.json    le manifeste : nom, version, pages, routes, dépendances, permission
```

Les conventions sont celles du Coeur : `app/` décrit le découpage, `modules/README.md`
explique ce qu'un module a le droit de faire, et `MVC.md` les règles d'architecture.

## 📄 Licence

GPL-3.0-or-later, comme le Coeur de l'application.
