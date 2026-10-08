# API REST Artists & Albums (PHP Slim 4)

API RESTful réalisée dans le cadre du **BTS CIEL** (lycée Saint-Michel, Annecy).
Elle gère une base musicale (artistes, albums, notes) avec **PHP Slim 4**, **MySQL**, une **authentification JWT** et le **pattern Repository**.

## Sommaire

- [Technologies](#technologies)
- [Architecture](#architecture)
- [Installation](#installation)
- [Base de données](#base-de-données)
- [Endpoints](#endpoints)
- [Authentification JWT](#authentification-jwt)
- [Exemples de requêtes](#exemples-de-requêtes)
- [Déploiement](#déploiement)
- [Auteur](#auteur)

## Technologies

| Outil | Rôle |
|---|---|
| PHP 8.2 | Langage (8.4 non recommandé : erreurs `Deprecated` avec php-di) |
| Slim Framework 4 | Micro-framework et routage |
| PHP-DI | Injection de dépendances |
| MySQL / PDO | Base de données |
| firebase/php-jwt | Génération et validation des tokens JWT |
| Composer | Gestion des dépendances |
| Postman | Tests des endpoints |

## Architecture

```
apirepository/
├── app/
│   ├── dependencies.php        # Connexion PDO (injection)
│   ├── middleware.php
│   ├── repositories.php
│   ├── routes.php
│   ├── settings.example.php    # Modèle de configuration (à copier en settings.php)
│   └── settings.php            # Configuration locale (non versionnée)
├── public/
│   └── index.php               # Point d'entrée de l'application
└── src/
    ├── Entity/
    │   └── Artist.php          # Entité Artist (JsonSerializable)
    ├── Repository/
    │   ├── BaseRepository.php  # Méthodes génériques : findAll, findById, delete
    │   ├── ArtistRepository.php# + create, update, findByYear
    │   └── AlbumRepository.php # + create, update, findByArtist
    ├── Middleware/
    │   ├── JwtHelper.php       # Génération / validation du token
    │   └── JwtMiddleware.php   # Protection des routes (401 si token invalide)
    └── routes/
        ├── routesJWT.php       # /login, /protected
        ├── routesArtist.php    # CRUD artistes + recherche par année
        └── routesAlbum.php     # CRUD albums + albums d'un artiste
```

### Pattern Repository

- `BaseRepository` (classe abstraite) contient les méthodes communes à toutes les tables : `findAll()`, `findById()`, `delete()`.
- Chaque repository concret (`ArtistRepository`, `AlbumRepository`) déclare sa table, sa clé primaire, sa méthode `hydrate()` et ses **méthodes spécifiques** :
  - `ArtistRepository::findByYear(string $annee)`
  - `AlbumRepository::findByArtist(int $artistId)`
- Les routes ne contiennent aucune requête SQL : elles appellent uniquement les repositories.

## Installation

### Prérequis

- PHP 8.2
- Composer
- MySQL (WAMP, XAMPP ou autre)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/brilandmuja/api-slim-artists.git
cd api-slim-artists

# 2. Installer les dépendances
composer install

# 3. Créer la configuration locale
cp app/settings.example.php app/settings.php
```

Puis éditer `app/settings.php` si besoin (hôte, utilisateur, mot de passe, base) :

```php
'db' => [
    'host'     => 'localhost',
    'username' => 'root',
    'database' => 'music',
    'password' => '',
],
```

```bash
# 4. Importer la base de données (voir ci-dessous), puis lancer le serveur
php -S localhost:8080 -t public
```

L'API est alors disponible sur `http://localhost:8080`.

> Le fichier `app/settings.php` contient les identifiants de base de données : il est volontairement exclu du dépôt (`.gitignore`).

## Base de données

Base `music`, trois tables :

| Table | Colonnes | Remarque |
|---|---|---|
| `artists` | `idArtist`, `Name`, `Annee`, `Description` | |
| `albums` | `idAlbums`, `Titre`, `Artist_idArtist` | clé étrangère vers `artists` |
| `ratings` | `idRatings`, `Grade`, `Albums_idAlbums` | clé étrangère vers `albums` |

Les suppressions respectent l'intégrité référentielle : supprimer un artiste qui a des albums, ou un album qui a des notes, renvoie une erreur **409**.

## Endpoints

### Artistes

| Méthode | Route | Description | Codes |
|---|---|---|---|
| GET | `/artists` | Liste des artistes | 200 |
| GET | `/artists/{id}` | Un artiste | 200, 404 |
| POST | `/artists` | Créer un artiste | 201, 400 |
| PUT | `/artists/{id}` | Modifier un artiste | 200, 400, 404 |
| DELETE | `/artists/{id}` | Supprimer un artiste | 204, 404, 409 |
| GET | `/artists/year/{annee}` | Artistes d'une année (`findByYear`) | 200 |
| GET | `/artists/{id}/albums` | Albums d'un artiste (`findByArtist`) | 200, 404 |

### Albums

| Méthode | Route | Description | Codes |
|---|---|---|---|
| GET | `/albums` | Liste des albums | 200 |
| GET | `/albums/{id}` | Un album | 200, 404 |
| POST | `/albums` | Créer un album | 201, 400, 404 |
| PUT | `/albums/{id}` | Modifier un album | 200, 400, 404 |
| DELETE | `/albums/{id}` | Supprimer un album | 204, 404, 409 |

### Authentification

| Méthode | Route | Description | Codes |
|---|---|---|---|
| POST | `/login` | Obtenir un token JWT | 200, 401 |
| GET | `/protected` | Route protégée (token requis) | 200, 401 |

## Authentification JWT

1. `POST /login` avec un identifiant et un mot de passe valides renvoie un token.
2. Le token est ensuite envoyé dans l'en-tête de chaque requête protégée :

```
Authorization: Bearer <token>
```

3. `JwtMiddleware` vérifie la signature et l'expiration (1 heure). Sans token valide, la réponse est **401**.

Compte de démonstration : `SaintMichel` / `ITcampus`.

## Exemples de requêtes

**Créer un artiste**

```http
POST /artists
Content-Type: application/json

{
  "Name": "Nouvel Artiste",
  "Annee": "2001",
  "Description": "Description de l'artiste"
}
```

Réponse `201 Created` :

```json
{
  "idArtist": 16,
  "Name": "Nouvel Artiste",
  "Annee": "2001",
  "Description": "Description de l'artiste"
}
```

**Créer un album**

```http
POST /albums
Content-Type: application/json

{
  "Titre": "Mon Album",
  "Artist_idArtist": 4
}
```

**Se connecter**

```http
POST /login
Content-Type: application/json

{
  "username": "SaintMichel",
  "password": "ITcampus"
}
```

Réponse :

```json
{ "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." }
```

**Appeler une route protégée**

```http
GET /protected
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

Réponse : `{ "message": "Hello, SaintMichel" }`

## Déploiement

L'API est hébergée sur **alwaysdata** (PHP 8.2), dans un sous-site `/repository`.
Sur le serveur, `public/index.php` contient en plus, juste après `AppFactory::create()` :

```php
$app->setBasePath('/repository');
```

En ligne, les URL ont donc le préfixe `/repository` (ex. `https://landi.alwaysdata.net/repository/artists`). En local, il n'y a pas de préfixe.

## Auteur

Briland Muja : BTS CIEL2, Lycée Saint-Michel, Annecy.
