# Quest

Core class: `Hacp0012\Quest\Quest`.

This is the class you use in route files. It registers a Laravel route and, on each request, finds the `#[QuestSpaw]` method that matches the reference.

```php
use Hacp0012\Quest\Quest;

Quest::spawn(uri: 'quest', routes: [Forest::class])->name('quest');
```

---

## `spawn`

Register **one URL prefix** for **many** methods.

```php
static function spawn(string $uri = 'quest', array|string $routes = []): Illuminate\Routing\Route
```

Laravel route created:

```
ANY  /{uri}/{quest_ref}
```

A trailing slash on `$uri` is stripped. Do not add `{quest_ref}` yourself.

| Argument | Type | Role |
| --- | --- | --- |
| `$uri` | `string` | Path prefix. Default `'quest'` → `/quest/{quest_ref}`. |
| `$routes` | `string` \| `array` | Class name(s) and/or directory path(s) from `base_path()`. A single string is wrapped in an array. |

Returns `Illuminate\Routing\Route`, so you can chain Laravel helpers:

```php
Quest::spawn('quest', [
    Forest::class,
    PhoneHandler::class,
    'app/Services',
])
    ->name('quest')
    ->middleware(['auth:sanctum']);
```

**Lookup order** for a `ref`:

1. Classes / directories in `$routes`
2. Entries in `routes/quest.php`
3. Package default list (empty)

The first matching public method is invoked.

Directories: Quest recurses from `base_path($path)`, keeps `.php` files that contain `#[QuestSpaw`, and registers `namespace` + the **first** `class` in each file. No namespace → skipped.

---

## `spaw`

Bind **one** method to a **fixed** URI. The client does not send `{quest_ref}`.

```php
static function spaw(string $uri, string|array $spaw): Illuminate\Routing\Route
```

Laravel route created:

```
ANY  /{uri}
```

| Argument | Type | Role |
| --- | --- | --- |
| `$uri` | `string` | Exact path (no `{quest_ref}` appended). |
| `$spaw` | `string` \| `array` | Either `['ClassName', 'ref']` or `'ClassName@ref'` / `'ClassName:ref'`. |

```php
Quest::spaw('phone/codes', [PhoneHandler::class, 'phone.codes']);
Quest::spaw('phone/codes', PhoneHandler::class . '@phone.codes');
Quest::spaw('phone/codes', 'App\Services\PhoneHandler:phone.codes');
```

A string without `@` or `:` throws `Obstacle`.

The first array element must be a real class (Quest runs `ReflectionClass` on it).

Use `spaw` when you want a stable, readable URL for a single action. For everything else, use `spawn`.

---

## `router`

Internal dispatcher. Prefer `spawn` / `spaw`.

```php
public function router(string $questId, array $classes): mixed
```

| Argument | Role |
| --- | --- |
| `$questId` | The `ref` to match. |
| `$classes` | Class names and/or directories (directories are expanded first). |

Walks every method of every class, and on the first `#[QuestSpaw]` whose `ref` equals `$questId`:

1. Optionally filters on attribute `middleware`
2. Checks the HTTP verb against `SpawMethod`
3. Requires `public` (or `static`) visibility
4. Validates and casts parameters
5. Instantiates the class and invokes the method

Return value:

- The method result (JSON response if `jsonResponse` is true)
- `QuestReturnVoid` if nothing matched or the middleware filter skipped the method

Calling this from a custom closure is possible but discouraged: `QuestReturnVoid` and mixed return types are easy to leak as a blank HTTP response.

```php
// Not recommended
Route::post('quest/{ref}', function (string $ref) {
    $quest = new Quest;
    return $quest->router(questId: $ref, classes: [QuestTest::class]);
});
```

---

## Other members

| Member | Role |
| --- | --- |
| `public string $ref` | Set to the current `questId` during `router()`. |
| `intentionRequest(): ?array` | `request()->input()` merged with uploaded files. |
| `makeClassInstance($class)` | Builds the class using `#[QuestSpawClass]` + the container. |
| `call($class, $method)` | Instantiates, injects arguments, applies `QuestResponse`, optionally JSON-encodes. |

Constants:

| Constant | Value |
| --- | --- |
| `authorizedAttributs` | `QuestSpawClass`, `QuestSpaw` |
| `supportedSpawTypes` | `bool`, `int`, `float`, `string`, `null`, `array`, `mixed` |
| `supportedFilesPocketTypes` | `UploadedFile::class`, `mixed` |
| `allowedMethodModifiers` | `static`, `public` |

Errors during dispatch throw `Hacp0012\Quest\core\Obstacle` (an `Exception` whose file/line point at the user method when possible).

---

## Related

- [Attributes](./attributs.md)
- [QuestRouter](./quester_router.md)
- [Guide](../README.md)
