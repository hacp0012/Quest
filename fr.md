<p align="center">
  <img src="./doc/assets/quest.png" alt="Quest" width="160">
</p>

# Quest

**Exposez une méthode Laravel comme endpoint HTTP — sans déclarer une route pour chaque action.**

Vous marquez une méthode de classe avec un attribut PHP et une clé de référence. Quest trouve cette méthode, injecte les paramètres de la requête, et renvoie le résultat.

[**Documentation en ligne**](https://hacp0012.github.io/Quest/) · [English README](./README.md) · [Guide français complet](./doc/fr.md)

---

- [Ce que fait Quest](#ce-que-fait-quest)
- [Installation](#installation)
- [Démarrage rapide](#démarrage-rapide)
- [Comment une requête est résolue](#comment-une-requête-est-résolue)
- [Appeler une méthode](#appeler-une-méthode)
- [Paramètres](#paramètres)
- [Méthodes HTTP, fichiers, middleware](#méthodes-http-fichiers-middleware)
- [Réponses](#réponses)
- [Enregistrer les classes](#enregistrer-les-classes)
- [CLI](#cli)
- [Référence API](#référence-api)
- [Bonnes pratiques](#bonnes-pratiques)
- [FAQ](#faq)

## Ce que fait Quest

Dans une application Laravel classique, chaque action publique a sa propre route :

```php
Route::get('/phone/codes', [PhoneHandler::class, 'getCodes']);
Route::post('/forest/tree', [Forest::class, 'tree']);
Route::post('/comments/update', [CommentService::class, 'updateText']);
```

Quest remplace cette liste par **une seule route fourre-tout** et des **attributs sur les méthodes**.

```php
class PhoneHandler
{
    #[QuestSpaw(ref: 'phone.codes')]
    public function getCodes(): array
    {
        return ['+243', '+33', '+1'];
    }
}
```

```php
// routes/api.php
Quest::spawn(uri: 'quest', routes: [PhoneHandler::class]);
```

Le client appelle ensuite la méthode par sa référence :

```http
GET /quest/phone.codes
```

Quest :

1. Associe `phone.codes` à `PhoneHandler::getCodes()`.
2. Lit query / body / fichiers et les mappe sur les arguments de la méthode (avec conversion de types).
3. Résout les dépendances du container Laravel (`Request`, et toute classe liée).
4. Appelle la méthode et renvoie le résultat (JSON par défaut).

Vous continuez d’utiliser Laravel (middleware, validation, container). Vous arrêtez de maintenir une ligne `Route::…` par petite action.

## Installation

**Prérequis :** PHP 8.0+ · Laravel 9+

```bash
composer require hacp0012/quest
```

Publiez la configuration et la liste globale des classes :

```bash
php artisan vendor:publish --tag=quest
```

Cela crée :

| Fichier | Rôle |
| --- | --- |
| `config/quest.php` | Méthode HTTP par défaut, et fichiers de routes que le traqueur CLI doit scanner |
| `routes/quest.php` | Liste **globale** optionnelle de classes (ou de dossiers) disponibles sur tous les endpoints Quest |

Vous pouvez aussi lancer `php artisan quest:publish` pour créer uniquement `routes/quest.php`.

## Démarrage rapide

### 1. Marquer une méthode

```php
namespace App\Services;

use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\SpawMethod;

class Forest
{
    #[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
    public function tree(string $color): array
    {
        return ['color' => $color, 'fruits' => 18];
    }
}
```

`ref` est l’identifiant public de la méthode. N’importe quelle chaîne convient. **N’y mettez pas de `/`** (cela casserait l’URL). Préférez un nom lisible plus un suffixe unique, par exemple `forest.tree.NAhLlRZW3g3Fbh30dZ`.

Générer un suffixe unique :

```bash
php artisan quest:generate-ref
# ou un UUID :
php artisan quest:generate-ref --uuid
```

### 2. Enregistrer un endpoint Quest

Dans `routes/web.php` ou `routes/api.php` :

```php
use Hacp0012\Quest\Quest;
use App\Services\Forest;

Quest::spawn(uri: 'quest', routes: [Forest::class])->name('quest');
```

`spawn()` renvoie un `Illuminate\Routing\Route`, donc vous pouvez chaîner les helpers habituels :

```php
Quest::spawn(uri: 'quest', routes: [Forest::class])
    ->name('quest')
    ->middleware(['auth:sanctum']);
```

Cela enregistre :

```
ANY  /quest/{quest_ref}
```

### 3. L’appeler

```http
GET /quest/forest.tree?color=green
```

```js
axios.get('/quest/forest.tree', { params: { color: 'green' } });
```

Depuis Blade, si la route est nommée :

```php
route('quest', ['quest_ref' => 'forest.tree', 'color' => 'green']);
```

Réponse par défaut (`jsonResponse: true`) :

```json
{ "color": "green", "fruits": 18 }
```

## Comment une requête est résolue

```
Client  →  ANY /quest/{quest_ref}
        →  Quest cherche le #[QuestSpaw(ref: …)] correspondant
        →  Vérifie la méthode HTTP, la visibilité, le filtre middleware
        →  Construit la classe (container + #[QuestSpawClass] optionnel)
        →  Convertit les données de la requête vers les paramètres
        →  Invoque la méthode
        →  Renvoie du JSON (ou la valeur brute)
```

Seules les méthodes **public** (y compris **static**) peuvent être exposées.

## Appeler une méthode

Deux styles d’enregistrement existent.

### `Quest::spawn` — une URL, plusieurs méthodes

C’est le cas d’usage principal.

```php
Quest::spawn(uri: 'quest', routes: [Forest::class, PhoneHandler::class]);
```

| Appel HTTP | Invoque |
| --- | --- |
| `/quest/forest.tree` | la méthode dont le `ref` est `forest.tree` |
| `/quest/phone.codes` | la méthode dont le `ref` est `phone.codes` |

Vous pouvez passer un **dossier** (relatif à la racine du projet Laravel) au lieu de lister les classes. Quest parcourt les fichiers `.php`, conserve la **première classe** de chaque fichier, et enregistre celles qui contiennent un `#[QuestSpaw]`. La classe doit avoir un namespace.

```php
Quest::spawn(uri: 'quest', routes: ['app/Services']);
```

### `Quest::spaw` — une URL, une méthode

Lie une seule référence à une URI fixe. Le client **ne passe pas** `{quest_ref}`.

```php
Quest::spaw('phone/codes', [PhoneHandler::class, 'phone.codes']);
// ou : Quest::spaw('phone/codes', PhoneHandler::class . '@phone.codes');
```

```http
GET /phone/codes
```

Appelle toujours `phone.codes`. Utile pour une URL stable et lisible dédiée à une action.

## Paramètres

Quest lit `request()->input()` et les fichiers uploadés, puis les mappe **par nom** sur la signature de la méthode.

```php
#[QuestSpaw(ref: 'comment.update')]
public function updateText(string $com_id, string $title, string $text): string
{
    // ...
    return $com_id;
}
```

```http
POST /quest/comment.update
Content-Type: application/json

{ "com_id": "42", "title": "Hello", "text": "…" }
```

Règles :

- La clé de la requête doit correspondre au nom du paramètre (ou à son [alias](#alias-de-paramètres)).
- Un paramètre obligatoire sans valeur par défaut doit être fourni.
- Les types sont convertis depuis les données HTTP (chaînes JSON / query) vers les types PHP.
- Un paramètre typé par une classe **liée dans le container Laravel** est construit par le container (`Request`, services, …). Le client ne l’envoie pas.

### Types pris en charge

| Provenance | Type PHP |
| --- | --- |
| JSON / form / query | `bool`, `int`, `float`, `string`, `array`, `mixed`, `null` |
| Fichier (`filePocket`) | `Illuminate\Http\UploadedFile` ou `mixed` |
| Non envoyé par le client | Tout type lié dans le service container |

Les autres types objet sont refusés : HTTP ne transporte pas d’objets PHP arbitraires. Liez la classe dans le container si Quest doit la construire.

Une valeur `array` peut être un vrai tableau ou une chaîne JSON.

### Alias de paramètres

Exposez un autre nom au client sans renommer le paramètre PHP :

```php
#[QuestSpaw(ref: 'forest.apples', alias: ['count' => 'max_weight'])]
public function displayApples(int $count, string $color): array
{
    return compact('count', 'color');
}
```

Le client envoie `max_weight`, pas `count`.

Dans `filePocket`, utilisez toujours le nom de paramètre **d’origine**, pas l’alias.

### Validation

Les types de paramètres sont un premier filtre, pas un remplacement de la validation Laravel. Injectez `Request` et validez comme d’habitude :

```php
use Illuminate\Http\Request;

#[QuestSpaw(ref: 'comment.update')]
public function updateText(Request $request, string $com_id): string
{
    $request->validate([
        'title' => 'required|string|max:120',
        'text'  => 'required|string',
    ]);

    return $com_id;
}
```

## Méthodes HTTP, fichiers, middleware

### Méthode HTTP

Prises en charge : `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`.

La route Laravel est enregistrée avec `Route::any`. Quest **exige** ensuite la méthode déclarée sur l’attribut. La valeur par défaut est `POST` (`config/quest.php` → `method`).

```php
use Hacp0012\Quest\SpawMethod;

#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array { /* ... */ }
```

`QuestSpawMethod` fonctionne encore mais est **déprécié**. Utilisez `SpawMethod`.

Pour `GET` / `HEAD`, passez les arguments en **paramètres de query**. Pour `POST` / `PUT` / `PATCH`, utilisez un body JSON ou form.

### Envoi de fichier

Indiquez quel paramètre reçoit le fichier. Un seul fichier par méthode dans cette version. Les uploads sont acceptés en **POST** uniquement.

```php
use Illuminate\Http\UploadedFile;

#[QuestSpaw(ref: 'profile.photo', filePocket: 'photo')]
public function storePhoto(string $user_id, UploadedFile $photo): string
{
    return $photo->store('photos');
}
```

Le nom du champ formulaire est `photo` (le nom du paramètre). Requête multipart :

```js
const form = new FormData();
form.append('user_id', '42');
form.append('photo', file);

axios.post('/quest/profile.photo', form);
```

### Middleware sur l’attribut

C’est un **filtre**, pas une façon d’attacher un middleware. La méthode ne s’exécute que si la **route Laravel courante** possède déjà au moins un des noms listés.

```php
Quest::spawn('quest', [Forest::class])->middleware('auth');

#[QuestSpaw(ref: 'forest.tree', middleware: 'auth')]
public function tree(string $color): array { /* ... */ }
```

Si les noms ne se recoupent pas, Quest ignore la méthode (sans exception). Attachez les vrais middlewares sur la route renvoyée par `spawn()` / `spaw()`.

## Réponses

### Par défaut : JSON

Avec `jsonResponse: true` (défaut), la valeur de retour est enveloppée dans `response()->json(…)`.

Mettez `jsonResponse: false` pour renvoyer une vue, une redirection, ou toute réponse Laravel telle quelle :

```php
use Illuminate\View\View;

#[QuestSpaw(ref: 'forest.page', method: SpawMethod::GET, jsonResponse: false)]
public function page(int $count): View
{
    return view('forest.apples', ['count' => $count]);
}
```

### Enveloppe sans changer le type de retour — `QuestResponse`

Gardez un type PHP utile pour réutiliser la méthode dans l’application, tout en envoyant des champs supplémentaires (`success`, `message`, …) au client HTTP.

```php
use Hacp0012\Quest\QuestResponse;

#[QuestSpaw(ref: 'forest.count')]
public function countFruits(): int
{
    $response = QuestResponse::setForJson(
        ref: 'forest.count',
        model: ['success' => true],
        dataName: 'count',
    );
    $response->setMessage('Ok');

    return 18; // reste un int pour les autres appels PHP
}
```

Corps HTTP :

```json
{
  "success": true,
  "message": "Ok",
  "count": 18
}
```

Créez le `QuestResponse` **au début** de la méthode, avec le **même** `ref` que l’attribut.

## Enregistrer les classes

### Où lister les classes

| Source | Portée |
| --- | --- |
| `Quest::spawn(…, routes: […])` | Cet endpoint |
| `routes/quest.php` | Tous les endpoints Quest |
| Dossiers dans l’une ou l’autre liste | Toutes les classes correspondantes sous ce chemin |

Ordre de recherche d’un `ref` : d’abord les classes passées à `spawn` / `spaw`, puis `routes/quest.php`. La première méthode correspondante gagne.

`routes/quest.php` :

```php
return [
    App\Services\Forest::class,
    'app/Quest', // dossier depuis base_path()
];
```

### Construire la classe — `#[QuestSpawClass]`

Quest instancie la classe avant d’appeler la méthode. Les arguments du constructeur liés dans le container sont résolus automatiquement. Des valeurs primitives supplémentaires se passent via l’attribut de classe (tableau associatif uniquement) :

```php
use Hacp0012\Quest\Attributs\QuestSpawClass;
use Illuminate\Http\Request;

#[QuestSpawClass(constructWith: ['age' => 1])]
class PersonService
{
    public function __construct(Request $request, int $age)
    {
        // $request depuis le container, $age = 1
    }
}
```

### Routeur manuel (à éviter)

`Quest::spawn` est le point d’entrée recommandé. Instancier `QuestRouter` ou appeler `Quest::router()` vous-même peut renvoyer des valeurs que Laravel ne convertira pas en réponse HTTP correcte.

## CLI

| Commande | Rôle |
| --- | --- |
| `php artisan quest:generate-ref [length] [--uuid]` | Référence aléatoire (longueur 36 par défaut) ou UUID |
| `php artisan quest:track-ref {ref}` | Localiser la méthode derrière une référence (signature, fichier, attribut) |
| `php artisan quest:find {keyword}` | Chercher par classe, méthode, ref, ou texte PHPDoc |
| `php artisan quest:ref --list` | Lister toutes les références connues |
| `php artisan quest:publish` | Créer `routes/quest.php` |
| `php artisan about` | Affiche la version de Quest parmi les infos Laravel |

```bash
php artisan quest:ref --list
php artisan quest:ref --generate=16
php artisan quest:ref --g-uuid
php artisan quest:ref --track=forest.tree

php artisan quest:find forest --full --with-comments
```

Le traqueur lit les classes depuis `routes/quest.php` et depuis les appels `Quest::spawn` / `Quest::spaw` dans les fichiers listés dans `config/quest.php` → `base_routes` (par défaut `routes/web.php` et `routes/api.php`).

Le PHPDoc de la méthode est affiché par le traqueur. Documentez la forme du retour ; c’est le moyen le plus rapide de se souvenir de ce qu’un `ref` renvoie :

```php
/** @return array{color: string, fruits: int} */
#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array
{
    return ['color' => $color, 'fruits' => 18];
}
```

## Référence API

- [Attributs (`QuestSpaw`, `QuestSpawClass`)](./doc/refs/attributs.md)
- [Classe Quest (`spawn`, `spaw`, `router`)](./doc/refs/quest.md)
- [QuestResponse](./doc/refs/response.md)
- [QuestRouter](./doc/refs/quester_router.md)
- [Commandes CLI](./doc/refs/commands.md)

Guide complet : [doc/fr.md](./doc/fr.md) · [English guide](./doc/README.md)

## Bonnes pratiques

- Utilisez des **refs lisibles** : `orders.list.k3n9…`, pas seulement une chaîne aléatoire.
- Gardez les refs **stables** ; elles font partie de votre API publique.
- Mettez du **PHPDoc** sur chaque méthode exposée (`@param`, `@return`).
- Attachez **l’authentification** sur la route `spawn()`, pas seulement sur le filtre d’attribut.
- Exposez des méthodes petites et explicites. Quest ne remplace pas un routeur REST complet quand vous avez besoin de ressources imbriquées, de groupes de rate-limit, ou d’un contrat OpenAPI public.
- Préférez `Quest::spawn` à `Quest::spaw`, sauf si une URL dédiée est nécessaire.

## FAQ

**Puis-je mélanger Quest et les routes Laravel normales ?**  
Oui. Enregistrez `Quest::spawn` à côté de `Route::get` / `Route::post`. Ils coexistent.

**Que se passe-t-il si deux méthodes partagent le même `ref` ?**  
La première correspondance dans l’ordre de recherche est utilisée. Gardez des refs uniques.

**Pourquoi ma méthode ne s’est pas exécutée (réponse vide) ?**  
Causes fréquentes : mauvais `ref`, méthode HTTP incorrecte, ou le filtre `middleware` de l’attribut ne correspond pas aux middlewares de la route. Utilisez `php artisan quest:track-ref {ref}`.

**Est-ce que Quest remplace FormRequest / les policies ?**  
Non. Injectez `Request`, appelez `$request->validate(…)`, et utilisez les policies Laravel comme d’habitude.
