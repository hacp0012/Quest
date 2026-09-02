# Attributes

PHP 8 attributes that mark a method as an HTTP endpoint, and optionally how to construct its class.

Namespace: `Hacp0012\Quest\Attributs`.

---

## `#[QuestSpaw]`

Target: **method**.

Turns that method into a Quest endpoint. Quest looks it up by `ref` when a request hits `Quest::spawn` / `Quest::spaw`.

```php
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\SpawMethod;

#[QuestSpaw(
    ref: 'forest.tree',
    method: SpawMethod::GET,
    filePocket: null,
    jsonResponse: true,
    middleware: null,
    alias: [],
)]
public function tree(string $color): array
{
    return ['color' => $color];
}
```

### Constructor

```php
public function __construct(
    public string $ref,
    public null|SpawMethod|QuestSpawMethod $method = null,
    public string|null $filePocket = null,
    public bool $jsonResponse = true,
    public array|string|null $middleware = null,
    public array $alias = [],
)
```

If `$method` is `null`, Quest uses `config('quest.method', SpawMethod::POST)`.

### Parameters

| Parameter | Type | Default | Role |
| --- | --- | --- | --- |
| `ref` | `string` | required | Public identifier. Used as `{quest_ref}` in the URL. Any text except `/`. |
| `method` | `SpawMethod` \| `QuestSpawMethod` \| `null` | `null` → config | HTTP verb the client must use. |
| `filePocket` | `string` \| `null` | `null` | PHP parameter name that receives `UploadedFile`. One file. POST only. Use the original name, not an alias. |
| `jsonResponse` | `bool` | `true` | `true`: `response()->json($return)`. `false`: return the value as Laravel received it (view, redirect, …). |
| `middleware` | `array` \| `string` \| `null` | `null` | **Filter**, not Laravel middleware. The method runs only if the current route already has at least one of these names. |
| `alias` | `array<string, string>` | `[]` | Map of **PHP parameter name → client field name**. |

### HTTP methods (`SpawMethod`)

```php
enum SpawMethod {
    case POST;
    case GET;
    case DELETE;
    case PUT;
    case HEAD;
    case PATCH;
}
```

`Hacp0012\Quest\QuestSpawMethod` is the same enum, **deprecated**. Use `SpawMethod`.

### Alias example

```php
#[QuestSpaw(
    ref: 'forest.apples',
    alias: ['count' => 'max_weight', 'state' => 'quality'],
)]
public function displayApples(int $count, string $color, string $state): array
```

The client sends `max_weight` and `quality`. `$color` is still `color`.

### File example

```php
use Illuminate\Http\UploadedFile;

#[QuestSpaw(ref: 'profile.photo', filePocket: 'photo')]
public function storePhoto(string $user_id, UploadedFile $photo): string
{
    return $photo->store('photos');
}
```

`$photo` must be typed `UploadedFile` or `mixed`. The form field is `photo`.

### Visibility

The method must be `public`. `static` is allowed. `private` / `protected` throw `Obstacle`.

---

## `#[QuestSpawClass]`

Target: **class**.

Tells Quest which extra primitive values to pass when it instantiates the class. Container-bound constructor types (`Request`, …) are still resolved automatically.

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

### Constructor

```php
public function __construct(public array $constructWith = [])
```

| Parameter | Type | Role |
| --- | --- | --- |
| `constructWith` | `array<string, mixed>` | Associative only (`['name' => value, …]`). Indexed arrays are rejected. Values should be primitives. |

If the class has no constructor, or every constructor argument is container-bound, you can omit the attribute.

Without a constructor, Quest uses `newInstanceWithoutConstructor()`.

---

## Related

- [Quest](./quest.md) — `spawn` / `spaw`
- [QuestResponse](./response.md) — HTTP envelope around a PHP return value
- [Guide](../README.md)
