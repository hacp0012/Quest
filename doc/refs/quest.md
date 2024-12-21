# Quest

The quest core class.

## Constructor

No parameter.

## spawn

```php
static spawn(string $uri = 'quest', string|array $routes = []): Illuminate\Routing\Route
```

Quest Router `QuesetRouter` short hand.

* string $uri
* ⚠️ At any end of `uri` a `{quest_ref}` route parameter are append. Dont append it twice.

@param string|array<int, string> `$routes` the (class name or directory) or an array of spawned class's, it can be directories (paths) started at the Laravel base path `base_path()`.

__Routes precedence__ :

1. Local routes : defined in spawed $routes parameter.
2. Global Base routes : defined in your routes/quest.php.
3. Defaults Global routes : default quest routes.

```php
// Ex: in your route file
Route::get('/', [...]);

Quest::spawn(uri: '/my/quest', routes: [QuestDemo::class]);

# ⚠️ To use only in route file. 
```

## spaw

```php
static function spaw(string $uri, string|array $spaw): RoutingRoute
```

Spaw a specific reference (call it directly). No quest reference key is required on request call.

```php
# Exemple: 
Quest::spaw('my/quest', 'App\class@ref-id');

Quest::spaw('my/quest', [className::class, 'ref-id']);
```

## router

Internal Main quest router

```php
router(string $questId, array $classes): mixed
```

```php
// Exemple :
Route::get('/', function() {
  $quest = new Quest;

  return $quest->router(questId: 'HhXEo0019', classes: [QuestDemo::class]);
});
```

🚧⚠️ _no good to use_
