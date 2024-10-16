# Quest

The quest core class.

## Constructor

No parameter.

## spawn

```php
static spawn(string $uri = 'quest', array $routes = []): Illuminate\Routing\Route
```

Quest Router `QuesetRouter` short hand.

* string $uri
* ⚠️ At any end of `uri` a `{quest_ref}` route parameter are append. Dont append it twice.

array<int, string> $routes An array of spawned class's or directories (paths) started at the Laravel base path `base_path()`.

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
