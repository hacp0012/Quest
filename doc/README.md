<p align="center">
  <img src="./assets/quest.png" alt="Quest" width="160">
</p>

# Quest — documentation

Access a Laravel method over HTTP without declaring one route per action.

This guide is the full English documentation. For a shorter overview, see the [root README](../README.md). [Version française](./fr.md).

---

- [What this package is](#what-this-package-is)
- [Installation](#installation)
- [Concepts](#concepts)
- [Tutorial](#tutorial)
- [Exposing a method](#exposing-a-method)
- [Registering the endpoint](#registering-the-endpoint)
- [Client calls](#client-calls)
- [Parameters and types](#parameters-and-types)
- [Files](#files)
- [HTTP methods](#http-methods)
- [Middleware](#middleware)
- [Class construction](#class-construction)
- [Responses](#responses)
- [Global class list and directories](#global-class-list-and-directories)
- [CLI tools](#cli-tools)
- [Configuration](#configuration)
- [Limitations](#limitations)
- [FAQ](#faq)
- [API reference](#api-reference)

## What this package is

**Quest** is a Laravel package. It turns selected class methods into HTTP endpoints using PHP 8 attributes.

You do not write:

```php
Route::get('/forest/tree', [Forest::class, 'tree']);
```

You write this on the method:

```php
#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array { /* ... */ }
```

…and you register **one** Quest URL:

```php
Quest::spawn(uri: 'quest', routes: [Forest::class]);
```

A client then calls:

```http
GET /quest/forest.tree?color=green
```

Quest maps `color` onto `$color`, calls `Forest::tree()`, and returns JSON.

That is the whole product: **attribute + reference key + one catch-all route**.

It is useful when an app accumulates many small, named actions (lookups, toggles, RPC-style calls from a SPA or a mobile client) and the `routes/*.php` files become a catalogue you never want to maintain by hand.

It is **not** a full REST framework. You still use Laravel routes when you need resource URLs, nested paths, or a public OpenAPI surface.

## Installation

Requires **PHP 8.0+** and **Laravel 9+**.

```bash
composer require hacp0012/quest
```

```bash
php artisan vendor:publish --tag=quest
```

| Published file | Purpose |
| --- | --- |
| `config/quest.php` | Default HTTP verb for `#[QuestSpaw]`, and route files scanned by the CLI tracker |
| `routes/quest.php` | Optional global list of classes or directories |

`php artisan quest:publish` creates `routes/quest.php` only.

The service provider `Hacp0012\Quest\providers\QuestProvider` is auto-discovered by Laravel.

## Concepts

| Term | Meaning |
| --- | --- |
| **Reference (`ref`)** | Public string that identifies a method. It is the last segment of `/quest/{quest_ref}`. |
| **`#[QuestSpaw]`** | PHP attribute on a **method**. Marks it as callable over HTTP. |
| **`#[QuestSpawClass]`** | PHP attribute on a **class**. Passes extra constructor values when Quest instantiates the class. |
| **`Quest::spawn`** | Registers `ANY /{uri}/{quest_ref}` and looks up the method by `ref`. |
| **`Quest::spaw`** | Registers `ANY /{uri}` bound to **one** class + `ref`. No `{quest_ref}` in the URL. |
| **`filePocket`** | Name of the method parameter that receives an uploaded file. |
| **`SpawMethod`** | Enum of allowed HTTP verbs. Prefer this over the deprecated `QuestSpawMethod`. |

Namespace of the attributes: `Hacp0012\Quest\Attributs` (that spelling).

## Tutorial

A complete path from empty class to HTTP call.

**1. Create a class**

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

`phone.codes` is a GET with no body. `phone.lookup` uses the default verb (`POST` unless you changed the config).

**2. Register Quest in a route file**

```php
use Hacp0012\Quest\Quest;
use App\Services\PhoneHandler;

Quest::spawn(uri: 'quest', routes: [PhoneHandler::class])
    ->name('quest')
    ->middleware('auth:sanctum');
```

**3. Call from the client**

```http
GET /quest/phone.codes
```

```http
POST /quest/phone.lookup
Content-Type: application/json

{ "number": "+243800000000" }
```

That is enough to ship an endpoint. The rest of this guide covers the options around that core.

## Exposing a method

```php
#[QuestSpaw(
    ref: 'forest.tree',
    method: SpawMethod::GET,   // optional, default POST
    filePocket: null,          // optional, parameter that receives a file
    jsonResponse: true,        // optional, wrap return value as JSON
    middleware: null,          // optional, filter (see Middleware)
    alias: [],                 // optional, PHP parameter name => name the client sends
)]
public function tree(string $color): array { /* ... */ }
```

Rules:

- The method must be **public** (static is allowed).
- `ref` can be any text except it should not contain `/`.
- One `#[QuestSpaw]` per method is used (the first).
- Duplicate `ref` values: the first match wins. Keep them unique.

Readable refs are easier to debug than raw random strings:

```
forest.tree.NAhLlRZW3g3Fbh30dZ
orders.list.k3n9Qx
```

Generate the random part with `php artisan quest:generate-ref`.

## Registering the endpoint

### Many methods, one prefix — `spawn`

```php
Quest::spawn(string $uri = 'quest', array|string $routes = []): Illuminate\Routing\Route
```

- `$uri` becomes `/{uri}/{quest_ref}`. Do not put `{quest_ref}` yourself.
- `$routes` is a class name, a directory path from `base_path()`, or an array of those.

```php
Quest::spawn('quest', Forest::class);
Quest::spawn('quest', [Forest::class, PhoneHandler::class, 'app/Services']);
```

The return value is a Laravel `Route`. Chain `->name()`, `->middleware()`, `->withoutMiddleware()`, etc.

### One method, one path — `spaw`

```php
Quest::spaw('phone/codes', [PhoneHandler::class, 'phone.codes']);
Quest::spaw('phone/codes', 'App\Services\PhoneHandler@phone.codes');
Quest::spaw('phone/codes', 'App\Services\PhoneHandler:phone.codes');
```

The client calls `/phone/codes` with no extra path segment.

### Manual `QuestRouter` / `Quest::router`

These exist for custom wiring. They are easy to misuse (return types Laravel cannot turn into HTTP). Prefer `spawn` / `spaw`. Details: [QuestRouter](./refs/quester_router.md), [Quest](./refs/quest.md).

## Client calls

The Laravel route accepts any verb (`Route::any`). Quest then checks the attribute.

**GET** — arguments as query string:

```js
axios.get('/quest/forest.tree', { params: { color: 'green' } });
```

**POST** — JSON body (keys = parameter names):

```js
axios.post('/quest/phone.lookup', { number: '+243800000000' });
```

**Named Laravel route** (after `->name('quest')`):

```php
route('quest', ['quest_ref' => 'forest.tree', 'color' => 'green']);
```

`quest_ref` is the route parameter Quest appends. Extra keys become query parameters.

## Parameters and types

Quest builds the argument list in **declaration order**, filling each parameter from:

1. Request input whose key equals the parameter name (or its alias).
2. An uploaded file, if this parameter is the `filePocket`.
3. The Laravel container, if the type is bound (`App::bound($type)`).
4. The parameter default, if the value is missing and a default exists.

Otherwise Quest throws (`Hacp0012\Quest\core\Obstacle`) with a message pointing at the method.

### Types accepted from the client

`bool`, `int`, `float`, `string`, `array`, `mixed`, `null`.

`array` may be a PHP array (JSON object/array already decoded by Laravel) or a JSON string.

Union types are allowed (`int|float`, `string|null`, …). Container types can appear in unions.

### Types Quest will not take from HTTP

Arbitrary objects (`DateTime`, Eloquent models, DTOs not bound in the container, …). Bind the class in a service provider if Quest should construct it. That is how `Illuminate\Http\Request` works.

### Aliases

```php
#[QuestSpaw(ref: 'forest.apples', alias: ['count' => 'max_weight', 'state' => 'quality'])]
public function displayApples(int $count, string $color, string $state): array
```

The client sends `max_weight` and `quality`. `$color` stays `color`.

`filePocket` always refers to the **PHP** parameter name.

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

Parameter types are not a substitute for FormRequest / `$request->validate()`.

## Files

```php
use Illuminate\Http\UploadedFile;

#[QuestSpaw(ref: 'profile.photo', filePocket: 'photo')]
public function storePhoto(string $user_id, UploadedFile $photo): string
{
    return $photo->store('photos');
}
```

- One file per method.
- Parameter type must be `UploadedFile` or `mixed`.
- Request method must be **POST**.
- The form field name is the parameter name (`photo`), unless you aliased that parameter — then the client uses the alias, while `filePocket` still uses `photo`.

```js
const form = new FormData();
form.append('user_id', '42');
form.append('photo', file);
axios.post('/quest/profile.photo', form);
```

## HTTP methods

`SpawMethod`: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`.

If omitted, Quest uses `config('quest.method')`, which defaults to `POST`.

A mismatch between the incoming verb and the attribute throws an `Obstacle`.

## Middleware

Two different mechanisms:

**1. Laravel middleware on the route** — this is the real security layer:

```php
Quest::spawn('quest', [Forest::class])->middleware(['auth:sanctum', 'throttle:60,1']);
```

**2. Attribute `middleware` — a filter.** Quest runs the method only if **at least one** of the names is already present on the current route. If none match, the method is skipped (empty response, no exception).

```php
#[QuestSpaw(ref: 'forest.secret', middleware: 'auth:sanctum')]
public function secret(): array { /* ... */ }
```

Do not rely on (2) alone to protect an endpoint.

## Class construction

Quest creates a new instance for each call (unless the method is static; the instance is still built).

Container-bound constructor arguments are resolved with `App::make()`. Extra primitives:

```php
use Hacp0012\Quest\Attributs\QuestSpawClass;

#[QuestSpawClass(constructWith: ['locale' => 'fr'])]
class ReportService
{
    public function __construct(Request $request, string $locale) {}
}
```

`constructWith` must be an **associative** array. Values are primitives, not objects.

## Responses

### JSON (default)

`jsonResponse: true` → `return response()->json($result)`.

### Raw Laravel return

`jsonResponse: false` → return a `View`, `RedirectResponse`, `StreamedResponse`, etc. unchanged.

### Envelope: `QuestResponse`

Adds `success`, `message`, and custom keys around the method’s return value **without changing the PHP return type**.

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

Initialize it at the **top** of the method. The `ref` must equal the attribute `ref`. See [QuestResponse](./refs/response.md).

## Global class list and directories

`routes/quest.php` is merged **after** the `routes` argument of `spawn` / `spaw`. Lookup order:

1. Classes (and directories) passed to `spawn` / `spaw`
2. Entries in `routes/quest.php`
3. The empty default list inside Quest (`QuestRoutes::$routes`)

A directory is a path from the project root (`base_path()`), for example `'app/Services'`. Quest:

- Recurses into subfolders
- Keeps `.php` files that contain `#[QuestSpaw`
- Reads `namespace` and the **first** `class` in the file

No namespace → the file is ignored.

## CLI tools

| Command | What it does |
| --- | --- |
| `quest:generate-ref [36] [--uuid]` | Random string or UUID for a new `ref` |
| `quest:track-ref {ref}` | Class, method signature, file, attribute arguments |
| `quest:find {keyword} [--full] [--with-comments]` | Search class, method, ref, or PHPDoc |
| `quest:ref --list [--no-table] [--index=1,2]` | Table of all refs |
| `quest:ref --generate=n` / `--g-uuid` / `--track=` | Shortcuts to the commands above |
| `quest:publish` | Write `routes/quest.php` |
| `php artisan about` | Includes the Quest version |

The tracker discovers classes from `routes/quest.php` and by **including** the PHP files in `config('quest.base_routes')` (default: `routes/web.php`, `routes/api.php`) so that `Quest::spawn(…)` calls populate an internal list.

If you register Quest from another file, add that file to `base_routes` or the tracker will miss those classes.

PHPDoc is displayed by `track-ref` and `find`. Write `@return` so a `ref` is self-explanatory in the console.

Details and flags: [CLI commands](./refs/commands.md).

## Configuration

`config/quest.php`:

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

| Key | Role |
| --- | --- |
| `method` | Default `SpawMethod` / `QuestSpawMethod` when the attribute omits `method` |
| `base_routes` | Files included by the CLI tracker to discover `Quest::spawn` / `Quest::spaw` |

## Limitations

- One uploaded file per method (`filePocket`).
- Client-sent types are JSON-friendly scalars and arrays only.
- Attribute `middleware` is a name check, not Laravel middleware.
- The first class in a scanned `.php` file is the only one registered from that file.
- `ref` values are a public API; renaming one breaks clients.
- Quest does not generate OpenAPI / Postman collections. The CLI tracker is the discovery tool.

## FAQ

**Can Quest live next to `Route::get` / `Route::post`?**  
Yes.

**Empty HTTP response, no exception?**  
Wrong `ref`, skipped by the middleware filter, or no matching class in the lists. Run `quest:track-ref`.

**`GET` with a JSON body?**  
Browsers and many clients ignore GET bodies. Use query parameters.

**Service container?**  
Yes. Bound types on the method or the constructor are built with `App::make()`.

**Private / protected methods?**  
Rejected. Only public (and static) methods.

## API reference

- [Attributes](./refs/attributs.md)
- [Quest](./refs/quest.md)
- [QuestResponse](./refs/response.md)
- [QuestRouter](./refs/quester_router.md)
- [CLI commands](./refs/commands.md)
