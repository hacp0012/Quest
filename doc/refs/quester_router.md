# QuestRouter

Class: `Hacp0012\Quest\QuestRouter`.

Low-level helper used by `Quest::spawn` and `Quest::spaw`. It loads the class lists, expands directories, and delegates to `Quest::router()`.

**In application code, call `Quest::spawn` / `Quest::spaw`.** Instantiating `QuestRouter` in a closure is supported but easy to get wrong: a missed `ref` returns nothing (`QuestReturnVoid` is swallowed), which looks like a blank HTTP response.

---

## Constructor

```php
public function __construct(protected string $questRef, array $routes = [])
```

| Argument | Role |
| --- | --- |
| `$questRef` | The `ref` to dispatch. With `spawn`, this is the `{quest_ref}` route parameter. With `spaw`, it is the ref you bound at registration. |
| `$routes` | Extra class names / directories for **this** request. Merged **in front of** the global list. |

On construct, QuestRouter:

1. Ensures `routes/quest.php` exists (`createRouteFile()`).
2. Loads `routesList()` (file + package defaults).
3. Sets `$this->routes = array_merge($routes, $globalList)` so local classes are searched first.

Both local and global classes remain reachable. The first matching `ref` wins.

---

## `spawn`

```php
public function spawn(): mixed
```

Runs `Quest::router($this->questRef, $this->routes)`.

If the result is `QuestReturnVoid` (no method matched, or middleware filter skipped it), the method returns `null` (implicit empty return). Otherwise it returns the method result / JSON response.

```php
// Not recommended — prefer Quest::spawn()
Route::post('quest/{ref}', function (string $ref) {
    $router = new QuestRouter(questRef: $ref, routes: [QuestTest::class]);
    return $router->spawn();
});
```

---

## Static helpers

### `createRouteFile()`

Writes `routes/quest.php` if it is missing. Same effect as `php artisan quest:publish`.

### `routesList(): array`

Returns the merge of:

1. `return […]` from `routes/quest.php` (if the file exists)
2. `QuestRoutes::$routes` (package default, empty)

### `exploreIfIsFolder(array $routes): array`

For each entry:

- If it is a class (`new ReflectionClass` succeeds) → keep it.
- Else if `base_path($entry)` is a directory → scan for classes that contain `#[QuestSpaw`.
- Else → throw `Obstacle` (`"$entry" is not correct sub directory of Laravel project base path.`).

Duplicates are removed.

---

## Related

- [Quest](./quest.md) — `spawn` / `spaw` (preferred API)
- [Attributes](./attributs.md)
- [Guide](../README.md)
