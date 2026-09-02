<p align="center">
  <img src="./assets/quest.png" alt="Quest" width="160">
</p>

# Quest — documentation

Accéder à une méthode Laravel en HTTP sans déclarer une route par action.

Ceci est le guide français complet. Version courte : [README français](../fr.md). [English guide](./README.md).

---

- [Présentation](#présentation)
- [Installation](#installation)
- [Concepts](#concepts)
- [Tutoriel](#tutoriel)
- [Exposer une méthode](#exposer-une-méthode)
- [Enregistrer l’endpoint](#enregistrer-lendpoint)
- [Appels client](#appels-client)
- [Paramètres et types](#paramètres-et-types)
- [Fichiers](#fichiers)
- [Méthodes HTTP](#méthodes-http)
- [Middleware](#middleware)
- [Construction de la classe](#construction-de-la-classe)
- [Réponses](#réponses)
- [Liste globale et dossiers](#liste-globale-et-dossiers)
- [Outils CLI](#outils-cli)
- [Configuration](#configuration)
- [Limites](#limites)
- [FAQ](#faq)
- [Référence API](#référence-api)

## Présentation

**Quest** est un package Laravel. Il transforme des méthodes de classe choisies en endpoints HTTP grâce aux attributs PHP 8.

Vous n’écrivez pas :

```php
Route::get('/forest/tree', [Forest::class, 'tree']);
```

Vous écrivez ceci sur la méthode :

```php
#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array { /* ... */ }
```

…et vous enregistrez **une** URL Quest :

```php
Quest::spawn(uri: 'quest', routes: [Forest::class]);
```

Un client appelle alors :

```http
GET /quest/forest.tree?color=green
```

Quest mappe `color` sur `$color`, appelle `Forest::tree()`, et renvoie du JSON.

C’est tout le produit : **attribut + clé de référence + une route fourre-tout**.

Utile lorsque l’application accumule beaucoup de petites actions nommées (lookups, bascules, appels de type RPC depuis une SPA ou un client mobile) et que les fichiers `routes/*.php` deviennent un catalogue que personne ne veut maintenir à la main.

Ce n’est **pas** un framework REST complet. Continuez d’utiliser les routes Laravel pour des URLs de ressources, des chemins imbriqués, ou une surface OpenAPI publique.

## Installation

Nécessite **PHP 8.0+** et **Laravel 9+**.

```bash
composer require hacp0012/quest
```

```bash
php artisan vendor:publish --tag=quest
```

| Fichier publié | Rôle |
| --- | --- |
| `config/quest.php` | Verbe HTTP par défaut pour `#[QuestSpaw]`, et fichiers de routes scannés par le traqueur CLI |
| `routes/quest.php` | Liste globale optionnelle de classes ou de dossiers |

`php artisan quest:publish` crée uniquement `routes/quest.php`.

Le service provider `Hacp0012\Quest\providers\QuestProvider` est auto-découvert par Laravel.

## Concepts

| Terme | Signification |
| --- | --- |
| **Référence (`ref`)** | Chaîne publique qui identifie une méthode. C’est le dernier segment de `/quest/{quest_ref}`. |
| **`#[QuestSpaw]`** | Attribut PHP sur une **méthode**. La rend appelable en HTTP. |
| **`#[QuestSpawClass]`** | Attribut PHP sur une **classe**. Passe des valeurs extra au constructeur quand Quest instancie la classe. |
| **`Quest::spawn`** | Enregistre `ANY /{uri}/{quest_ref}` et retrouve la méthode par `ref`. |
| **`Quest::spaw`** | Enregistre `ANY /{uri}` lié à **une** classe + `ref`. Pas de `{quest_ref}` dans l’URL. |
| **`filePocket`** | Nom du paramètre de méthode qui reçoit un fichier uploadé. |
| **`SpawMethod`** | Enum des verbes HTTP autorisés. À préférer à `QuestSpawMethod` (déprécié). |

Namespace des attributs : `Hacp0012\Quest\Attributs` (cette orthographe).

## Tutoriel

De la classe vide jusqu’à l’appel HTTP.

**1. Créer une classe**

```php
namespace App\Services;

use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\SpawMethod;

class PhoneHandler
{
    #[QuestSpaw(ref: 'phone.codes', method: SpawMethod::GET)]
    public function getCodes(): array
    {
        return ['+243', '+33', '+1'];
    }

    #[QuestSpaw(ref: 'phone.lookup')]
    public function lookup(string $number): array
    {
        return ['number' => $number, 'valid' => true];
    }
}
```

`phone.codes` est un GET sans body. `phone.lookup` utilise le verbe par défaut (`POST` sauf changement de config).

**2. Enregistrer Quest dans un fichier de routes**

```php
use Hacp0012\Quest\Quest;
use App\Services\PhoneHandler;

Quest::spawn(uri: 'quest', routes: [PhoneHandler::class])
    ->name('quest')
    ->middleware('auth:sanctum');
```

**3. Appeler depuis le client**

```http
GET /quest/phone.codes
```

```http
POST /quest/phone.lookup
Content-Type: application/json

{ "number": "+243800000000" }
```

Cela suffit pour livrer un endpoint. Le reste du guide couvre les options autour de ce noyau.

## Exposer une méthode

```php
#[QuestSpaw(
    ref: 'forest.tree',
    method: SpawMethod::GET,   // optionnel, POST par défaut
    filePocket: null,          // optionnel, paramètre qui reçoit un fichier
    jsonResponse: true,        // optionnel, envelopper le retour en JSON
    middleware: null,          // optionnel, filtre (voir Middleware)
    alias: [],                 // optionnel, nom PHP => nom côté client
)]
public function tree(string $color): array { /* ... */ }
```

Règles :

- La méthode doit être **public** (static est autorisé).
- `ref` peut être n’importe quel texte, mais ne doit pas contenir `/`.
- Un seul `#[QuestSpaw]` par méthode est utilisé (le premier).
- `ref` en double : la première correspondance gagne. Gardez-les uniques.

Des refs lisibles se déboguent mieux que des chaînes aléatoires pures :

```
forest.tree.NAhLlRZW3g3Fbh30dZ
orders.list.k3n9Qx
```

Générez la partie aléatoire avec `php artisan quest:generate-ref`.

## Enregistrer l’endpoint

### Plusieurs méthodes, un préfixe — `spawn`

```php
Quest::spawn(string $uri = 'quest', array|string $routes = []): Illuminate\Routing\Route
```

- `$uri` devient `/{uri}/{quest_ref}`. N’ajoutez pas `{quest_ref}` vous-même.
- `$routes` est un nom de classe, un chemin de dossier depuis `base_path()`, ou un tableau de ceux-ci.

```php
Quest::spawn('quest', Forest::class);
Quest::spawn('quest', [Forest::class, PhoneHandler::class, 'app/Services']);
```

La valeur de retour est une `Route` Laravel. Chaînez `->name()`, `->middleware()`, `->withoutMiddleware()`, etc.

### Une méthode, un chemin — `spaw`

```php
Quest::spaw('phone/codes', [PhoneHandler::class, 'phone.codes']);
Quest::spaw('phone/codes', 'App\Services\PhoneHandler@phone.codes');
Quest::spaw('phone/codes', 'App\Services\PhoneHandler:phone.codes');
```

Le client appelle `/phone/codes` sans segment de chemin supplémentaire.

### `QuestRouter` / `Quest::router` manuels

Ils existent pour un câblage personnalisé. Ils sont faciles à mal utiliser (types de retour que Laravel ne convertit pas en HTTP). Préférez `spawn` / `spaw`. Détails : [QuestRouter](./refs/quester_router.md), [Quest](./refs/quest.md).

## Appels client

La route Laravel accepte n’importe quel verbe (`Route::any`). Quest vérifie ensuite l’attribut.

**GET** — arguments en query string :

```js
axios.get('/quest/forest.tree', { params: { color: 'green' } });
```

**POST** — body JSON (clés = noms des paramètres) :

```js
axios.post('/quest/phone.lookup', { number: '+243800000000' });
```

**Route Laravel nommée** (après `->name('quest')`) :

```php
route('quest', ['quest_ref' => 'forest.tree', 'color' => 'green']);
```

`quest_ref` est le paramètre de route que Quest ajoute. Les clés supplémentaires deviennent des paramètres de query.

## Paramètres et types

Quest construit la liste d’arguments dans **l’ordre de déclaration**, en remplissant chaque paramètre depuis :

1. L’input de la requête dont la clé égale le nom du paramètre (ou son alias).
2. Un fichier uploadé, si ce paramètre est le `filePocket`.
3. Le container Laravel, si le type est lié (`App::bound($type)`).
4. La valeur par défaut du paramètre, si la valeur manque et qu’un défaut existe.

Sinon Quest lève une exception (`Hacp0012\Quest\core\Obstacle`) avec un message qui pointe vers la méthode.

### Types acceptés depuis le client

`bool`, `int`, `float`, `string`, `array`, `mixed`, `null`.

`array` peut être un tableau PHP (objet/tableau JSON déjà décodé par Laravel) ou une chaîne JSON.

Les unions sont autorisées (`int|float`, `string|null`, …). Les types du container peuvent apparaître dans une union.

### Types que Quest ne prendra pas depuis HTTP

Objets arbitraires (`DateTime`, modèles Eloquent, DTO non liés dans le container, …). Liez la classe dans un service provider si Quest doit la construire. C’est ainsi que fonctionne `Illuminate\Http\Request`.

### Alias

```php
#[QuestSpaw(ref: 'forest.apples', alias: ['count' => 'max_weight', 'state' => 'quality'])]
public function displayApples(int $count, string $color, string $state): array
```

Le client envoie `max_weight` et `quality`. `$color` reste `color`.

`filePocket` désigne toujours le nom de paramètre **PHP**.

### Validation

```php
public function updateText(Request $request, string $com_id): string
{
    $validated = $request->validate([
        'title' => 'required|string|max:120',
        'text'  => 'required|string',
    ]);

    return $com_id;
}
```

Les types de paramètres ne remplacent pas FormRequest / `$request->validate()`.

## Fichiers

```php
use Illuminate\Http\UploadedFile;

#[QuestSpaw(ref: 'profile.photo', filePocket: 'photo')]
public function storePhoto(string $user_id, UploadedFile $photo): string
{
    return $photo->store('photos');
}
```

- Un fichier par méthode.
- Le type du paramètre doit être `UploadedFile` ou `mixed`.
- La méthode de requête doit être **POST**.
- Le nom du champ formulaire est le nom du paramètre (`photo`), sauf si vous avez aliasé ce paramètre — alors le client utilise l’alias, tandis que `filePocket` utilise toujours `photo`.

```js
const form = new FormData();
form.append('user_id', '42');
form.append('photo', file);
axios.post('/quest/profile.photo', form);
```

## Méthodes HTTP

`SpawMethod` : `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`.

Si omis, Quest utilise `config('quest.method')`, qui vaut `POST` par défaut.

Un décalage entre le verbe entrant et l’attribut lève un `Obstacle`.

## Middleware

Deux mécanismes distincts :

**1. Middleware Laravel sur la route** — c’est la vraie couche de sécurité :

```php
Quest::spawn('quest', [Forest::class])->middleware(['auth:sanctum', 'throttle:60,1']);
```

**2. `middleware` sur l’attribut — un filtre.** Quest n’exécute la méthode que si **au moins un** des noms est déjà présent sur la route courante. Si aucun ne correspond, la méthode est ignorée (réponse vide, pas d’exception).

```php
#[QuestSpaw(ref: 'forest.secret', middleware: 'auth:sanctum')]
public function secret(): array { /* ... */ }
```

Ne vous appuyez pas sur (2) seul pour protéger un endpoint.

## Construction de la classe

Quest crée une nouvelle instance à chaque appel (même si la méthode est static, l’instance est tout de même construite).

Les arguments de constructeur liés au container sont résolus avec `App::make()`. Primitives extra :

```php
use Hacp0012\Quest\Attributs\QuestSpawClass;

#[QuestSpawClass(constructWith: ['locale' => 'fr'])]
class ReportService
{
    public function __construct(Request $request, string $locale) {}
}
```

`constructWith` doit être un tableau **associatif**. Les valeurs sont des primitives, pas des objets.

## Réponses

### JSON (défaut)

`jsonResponse: true` → `return response()->json($result)`.

### Retour Laravel brut

`jsonResponse: false` → renvoyer une `View`, `RedirectResponse`, `StreamedResponse`, etc. sans transformation.

### Enveloppe : `QuestResponse`

Ajoute `success`, `message`, et des clés personnalisées autour de la valeur de retour **sans changer le type de retour PHP**.

```php
use Hacp0012\Quest\QuestResponse;

#[QuestSpaw(ref: 'forest.count')]
public function countFruits(): int
{
    QuestResponse::setForJson(
        ref: 'forest.count',
        model: ['success' => true],
        dataName: 'count',
    )->setMessage('Ok');

    return 18;
}
```

```json
{ "success": true, "message": "Ok", "count": 18 }
```

Initialisez-le **en haut** de la méthode. Le `ref` doit être identique à celui de l’attribut. Voir [QuestResponse](./refs/response.md).

## Liste globale et dossiers

`routes/quest.php` est fusionné **après** l’argument `routes` de `spawn` / `spaw`. Ordre de recherche :

1. Classes (et dossiers) passés à `spawn` / `spaw`
2. Entrées de `routes/quest.php`
3. La liste vide par défaut dans Quest (`QuestRoutes::$routes`)

Un dossier est un chemin depuis la racine du projet (`base_path()`), par exemple `'app/Services'`. Quest :

- Parcourt les sous-dossiers
- Conserve les fichiers `.php` qui contiennent `#[QuestSpaw`
- Lit `namespace` et la **première** `class` du fichier

Pas de namespace → le fichier est ignoré.

## Outils CLI

| Commande | Rôle |
| --- | --- |
| `quest:generate-ref [36] [--uuid]` | Chaîne aléatoire ou UUID pour un nouveau `ref` |
| `quest:track-ref {ref}` | Classe, signature, fichier, arguments de l’attribut |
| `quest:find {keyword} [--full] [--with-comments]` | Chercher classe, méthode, ref, ou PHPDoc |
| `quest:ref --list [--no-table] [--index=1,2]` | Tableau de toutes les refs |
| `quest:ref --generate=n` / `--g-uuid` / `--track=` | Raccourcis vers les commandes ci-dessus |
| `quest:publish` | Écrire `routes/quest.php` |
| `php artisan about` | Inclut la version de Quest |

Le traqueur découvre les classes depuis `routes/quest.php` et en **incluant** les fichiers PHP de `config('quest.base_routes')` (défaut : `routes/web.php`, `routes/api.php`) pour que les appels `Quest::spawn(…)` alimentent une liste interne.

Si vous enregistrez Quest depuis un autre fichier, ajoutez ce fichier à `base_routes` sinon le traqueur manquera ces classes.

Le PHPDoc est affiché par `track-ref` et `find`. Écrivez `@return` pour qu’un `ref` soit explicite dans la console.

Détails et options : [Commandes CLI](./refs/commands.md).

## Configuration

`config/quest.php` :

```php
use Hacp0012\Quest\QuestSpawMethod;

return [
    'method' => QuestSpawMethod::POST,

    'base_routes' => [
        base_path('/routes/api.php'),
        base_path('/routes/web.php'),
    ],
];
```

| Clé | Rôle |
| --- | --- |
| `method` | `SpawMethod` / `QuestSpawMethod` par défaut si l’attribut omet `method` |
| `base_routes` | Fichiers inclus par le traqueur CLI pour découvrir `Quest::spawn` / `Quest::spaw` |

## Limites

- Un seul fichier uploadé par méthode (`filePocket`).
- Les types envoyés par le client sont uniquement des scalaires et tableaux compatibles JSON.
- Le `middleware` de l’attribut est une vérification de nom, pas un middleware Laravel.
- Seule la première classe d’un fichier `.php` scanné est enregistrée.
- Les valeurs `ref` sont une API publique ; en renommer une casse les clients.
- Quest ne génère pas d’OpenAPI / collections Postman. Le traqueur CLI est l’outil de découverte.

## FAQ

**Quest peut-il cohabiter avec `Route::get` / `Route::post` ?**  
Oui.

**Réponse HTTP vide, sans exception ?**  
Mauvais `ref`, ignoré par le filtre middleware, ou aucune classe correspondante dans les listes. Lancez `quest:track-ref`.

**`GET` avec un body JSON ?**  
Les navigateurs et beaucoup de clients ignorent le body des GET. Utilisez la query string.

**Service container ?**  
Oui. Les types liés sur la méthode ou le constructeur sont construits avec `App::make()`.

**Méthodes private / protected ?**  
Refusées. Uniquement public (et static).

## Référence API

- [Attributs](./refs/attributs.md)
- [Quest](./refs/quest.md)
- [QuestResponse](./refs/response.md)
- [QuestRouter](./refs/quester_router.md)
- [Commandes CLI](./refs/commands.md)
