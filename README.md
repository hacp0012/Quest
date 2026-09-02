<p align="center">
  <img src="./doc/assets/quest.png" alt="Quest" width="160">
</p>

# Quest

**Expose a Laravel method as an HTTP endpoint — without declaring a route for it.**

You mark a class method with a PHP attribute and a reference key. Quest finds that method, injects the request parameters, and returns the result.

[**Online documentation**](https://hacp0012.github.io/Quest/) · [Documentation en français](./fr.md)

---

- [What Quest does](#what-quest-does)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How a request is resolved](#how-a-request-is-resolved)
- [Calling a method](#calling-a-method)
- [Parameters](#parameters)
- [HTTP methods, files, middleware](#http-methods-files-middleware)
- [Responses](#responses)
- [Registering classes](#registering-classes)
- [CLI](#cli)
- [API reference](#api-reference)
- [Best practices](#best-practices)
- [FAQ](#faq)

## What Quest does

In a typical Laravel app, every public action needs its own route:

```php
Route::get('/phone/codes', [PhoneHandler::class, 'getCodes']);
Route::post('/forest/tree', [Forest::class, 'tree']);
Route::post('/comments/update', [CommentService::class, 'updateText']);
```

Quest replaces that list with **one catch-all route** plus **attributes on the methods themselves**.

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

The client then calls the method by its reference:

```http
GET /quest/phone.codes
```

Quest:

1. Matches `phone.codes` to `PhoneHandler::getCodes()`.
2. Reads query/body/files and maps them onto the method arguments (with type casting).
3. Resolves Laravel container dependencies (`Request`, and any bound class).
4. Calls the method and returns the result (JSON by default).

You still use Laravel (middleware, validation, the container). You stop maintaining one `Route::…` line per small action.

## Installation

**Requirements:** PHP 8.0+ · Laravel 9+

```bash
composer require hacp0012/quest
```

Publish the config and the global class list:

```bash
php artisan vendor:publish --tag=quest
```

This creates:

| File | Role |
| --- | --- |
| `config/quest.php` | Default HTTP method, and which route files the CLI tracker should scan |
| `routes/quest.php` | Optional **global** list of classes (or directories) available on every Quest endpoint |

You can also run `php artisan quest:publish` to create `routes/quest.php` only.

## Quick start

### 1. Mark a method

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

`ref` is the public identifier of the method. Any string works. Do **not** put `/` in it (it would break the URL). Prefer a readable name plus a unique suffix, for example `forest.tree.NAhLlRZW3g3Fbh30dZ`.

Generate a unique suffix:

```bash
php artisan quest:generate-ref
# or a UUID:
php artisan quest:generate-ref --uuid
```

### 2. Register one Quest endpoint

In `routes/web.php` or `routes/api.php`:

```php
use Hacp0012\Quest\Quest;
use App\Services\Forest;

Quest::spawn(uri: 'quest', routes: [Forest::class])->name('quest');
```

`spawn()` returns an `Illuminate\Routing\Route`, so you can chain the usual helpers:

```php
Quest::spawn(uri: 'quest', routes: [Forest::class])
    ->name('quest')
    ->middleware(['auth:sanctum']);
```

That registers:

```
ANY  /quest/{quest_ref}
```

### 3. Call it

```http
GET /quest/forest.tree?color=green
```

```js
axios.get('/quest/forest.tree', { params: { color: 'green' } });
```

From Blade, if the route is named:

```php
route('quest', ['quest_ref' => 'forest.tree', 'color' => 'green']);
```

Default response (`jsonResponse: true`):

```json
{ "color": "green", "fruits": 18 }
```

## How a request is resolved

```
Client  →  ANY /quest/{quest_ref}
        →  Quest looks up the matching #[QuestSpaw(ref: …)]
        →  Checks HTTP method, visibility, middleware filter
        →  Builds the class (container + optional #[QuestSpawClass])
        →  Casts request data onto method parameters
        →  Invokes the method
        →  Returns JSON (or the raw return value)
```

Only **public** (including **static**) methods can be exposed.

## Calling a method

Two registration styles exist.

### `Quest::spawn` — one URL, many methods

Use this in almost every case.

```php
Quest::spawn(uri: 'quest', routes: [Forest::class, PhoneHandler::class]);
```

| HTTP call | Invokes |
| --- | --- |
| `/quest/forest.tree` | the method whose `ref` is `forest.tree` |
| `/quest/phone.codes` | the method whose `ref` is `phone.codes` |

You can pass a **directory** (relative to the Laravel project root) instead of listing classes. Quest scans `.php` files, keeps the **first class** of each file, and registers classes that contain a `#[QuestSpaw]`. The class must live in a namespace.

```php
Quest::spawn(uri: 'quest', routes: ['app/Services']);
```

### `Quest::spaw` — one URL, one method

Binds a single reference to a fixed URI. The client does **not** pass `{quest_ref}`.

```php
Quest::spaw('phone/codes', [PhoneHandler::class, 'phone.codes']);
// or: Quest::spaw('phone/codes', PhoneHandler::class . '@phone.codes');
```

```http
GET /phone/codes
```

Always calls `phone.codes`. Useful when you want a stable, readable URL for one action.

## Parameters

Quest reads `request()->input()` and uploaded files, then maps them **by name** onto the method signature.

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

Rules:

- The request key must match the parameter name (or its [alias](#parameter-aliases)).
- Required parameters with no default must be present.
- Types are cast from HTTP data (strings in JSON/query) to PHP types.
- Parameters typed with a class **bound in the Laravel container** are constructed by the container (`Request`, custom services, …). You do not send them from the client.

### Supported types

| From the client | PHP type |
| --- | --- |
| JSON / form / query | `bool`, `int`, `float`, `string`, `array`, `mixed`, `null` |
| Uploaded file (`filePocket`) | `Illuminate\Http\UploadedFile` or `mixed` |
| Not sent by the client | Any type bound in the service container |

Other object types are rejected: HTTP does not transport arbitrary PHP objects. Register the class in the container if Quest should build it.

`array` values may be a real array or a JSON string.

### Parameter aliases

Expose a different name to the client without renaming the PHP parameter:

```php
#[QuestSpaw(ref: 'forest.apples', alias: ['count' => 'max_weight'])]
public function displayApples(int $count, string $color): array
{
    return compact('count', 'color');
}
```

The client sends `max_weight`, not `count`.

In `filePocket`, always use the **original** parameter name, not the alias.

### Validation

Parameter types are a first filter, not a replacement for Laravel validation. Inject `Request` and validate as usual:

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

## HTTP methods, files, middleware

### HTTP method

Supported: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`.

The Laravel route is registered with `Route::any`. Quest then **requires** the method declared on the attribute. The default is `POST` (`config/quest.php` → `method`).

```php
use Hacp0012\Quest\SpawMethod;

#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array { /* ... */ }
```

`QuestSpawMethod` still works but is **deprecated**. Use `SpawMethod`.

For `GET` / `HEAD`, pass arguments as **query parameters**. For `POST` / `PUT` / `PATCH`, use a JSON or form body.

### File upload

Declare which parameter receives the file. One file per method in this version. Uploads are accepted on **POST** only.

```php
use Illuminate\Http\UploadedFile;

#[QuestSpaw(ref: 'profile.photo', filePocket: 'photo')]
public function storePhoto(string $user_id, UploadedFile $photo): string
{
    return $photo->store('photos');
}
```

The form field name is `photo` (the parameter name). Multipart request:

```js
const form = new FormData();
form.append('user_id', '42');
form.append('photo', file);

axios.post('/quest/profile.photo', form);
```

### Middleware on the attribute

This is a **filter**, not a way to attach middleware. The method runs only if the **current Laravel route** already has at least one of the listed middleware names.

```php
Quest::spawn('quest', [Forest::class])->middleware('auth');

#[QuestSpaw(ref: 'forest.tree', middleware: 'auth')]
public function tree(string $color): array { /* ... */ }
```

If the names do not overlap, Quest skips the method (it does not throw). Attach real middleware on the route returned by `spawn()` / `spaw()`.

## Responses

### Default: JSON

With `jsonResponse: true` (the default), the method return value is wrapped in `response()->json(…)`.

Set `jsonResponse: false` to return a view, a redirect, or any Laravel response as-is:

```php
use Illuminate\View\View;

#[QuestSpaw(ref: 'forest.page', method: SpawMethod::GET, jsonResponse: false)]
public function page(int $count): View
{
    return view('forest.apples', ['count' => $count]);
}
```

### Envelope without changing the return type — `QuestResponse`

Keep a useful PHP return type for reuse inside the app, while sending extra fields (`success`, `message`, …) to the HTTP client.

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

    return 18; // still an int for other PHP callers
}
```

HTTP body:

```json
{
  "success": true,
  "message": "Ok",
  "count": 18
}
```

Create the `QuestResponse` **at the beginning** of the method, with the **same** `ref` as the attribute.

## Registering classes

### Where classes are listed

| Source | Scope |
| --- | --- |
| `Quest::spawn(…, routes: […])` | That endpoint |
| `routes/quest.php` | Every Quest endpoint |
| Directories in either list | All matching classes under that path |

Search order for a `ref`: classes passed to `spawn` / `spaw` first, then `routes/quest.php`. The first matching method wins.

`routes/quest.php`:

```php
return [
    App\Services\Forest::class,
    'app/Quest', // directory from base_path()
];
```

### Constructing the class — `#[QuestSpawClass]`

Quest instantiates the class before calling the method. Constructor arguments that are bound in the container are resolved automatically. Extra primitive values can be passed with the class attribute (associative array only):

```php
use Hacp0012\Quest\Attributs\QuestSpawClass;
use Illuminate\Http\Request;

#[QuestSpawClass(constructWith: ['age' => 1])]
class PersonService
{
    public function __construct(Request $request, int $age)
    {
        // $request from the container, $age = 1
    }
}
```

### Manual router (avoid if you can)

`Quest::spawn` is the supported entry point. Instantiating `QuestRouter` or calling `Quest::router()` yourself can return values Laravel will not convert into a proper HTTP response.

## CLI

| Command | Purpose |
| --- | --- |
| `php artisan quest:generate-ref [length] [--uuid]` | Random reference (default length 36) or UUID |
| `php artisan quest:track-ref {ref}` | Locate the method behind a reference (signature, file, attribute) |
| `php artisan quest:find {keyword}` | Search by class, method, ref, or PHPDoc text |
| `php artisan quest:ref --list` | List all known references |
| `php artisan quest:publish` | Create `routes/quest.php` |
| `php artisan about` | Shows the Quest version among other Laravel info |

```bash
php artisan quest:ref --list
php artisan quest:ref --generate=16
php artisan quest:ref --g-uuid
php artisan quest:ref --track=forest.tree

php artisan quest:find forest --full --with-comments
```

The tracker reads classes from `routes/quest.php` and from `Quest::spawn` / `Quest::spaw` calls in the files listed in `config/quest.php` → `base_routes` (by default `routes/web.php` and `routes/api.php`).

PHPDoc on the method is shown by the tracker. Document the return shape; it is the fastest way to remember what a `ref` returns:

```php
/** @return array{color: string, fruits: int} */
#[QuestSpaw(ref: 'forest.tree', method: SpawMethod::GET)]
public function tree(string $color): array
{
    return ['color' => $color, 'fruits' => 18];
}
```

## API reference

- [Attributes (`QuestSpaw`, `QuestSpawClass`)](./doc/refs/attributs.md)
- [Quest class (`spawn`, `spaw`, `router`)](./doc/refs/quest.md)
- [QuestResponse](./doc/refs/response.md)
- [QuestRouter](./doc/refs/quester_router.md)
- [CLI commands](./doc/refs/commands.md)

Full guide: [doc/README.md](./doc/README.md) · [Guide français](./doc/fr.md)

## Best practices

- Use **readable refs**: `orders.list.k3n9…`, not only a random string.
- Keep refs **stable**; they are part of your public API.
- Put **PHPDoc** on every exposed method (`@param`, `@return`).
- Attach **authentication** on the `spawn()` route, not only on the attribute filter.
- Expose small, explicit methods. Quest is not a replacement for a full REST router when you need nested resources, rate-limit groups, or public OpenAPI contracts.
- Prefer `Quest::spawn` over `Quest::spaw` unless one dedicated URL is required.

## FAQ

**Can I mix Quest with normal Laravel routes?**  
Yes. Register `Quest::spawn` next to `Route::get` / `Route::post`. They coexist.

**What if two methods share the same `ref`?**  
The first match in the search order is used. Keep refs unique.

**Why did my method not run (empty response)?**  
Typical causes: wrong `ref`, HTTP method mismatch, or the attribute `middleware` filter did not match the route middleware. Use `php artisan quest:track-ref {ref}`.

**Does Quest replace FormRequest / policies?**  
No. Inject `Request`, call `$request->validate(…)`, and use Laravel policies as you already do.
