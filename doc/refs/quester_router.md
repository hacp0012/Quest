# QuestRouter [class]

## constructor

Create a new class instance.

```php
QuestRouter(protected string $questRef, array $routes = [])
```

@param string $questRef Reference ID.

@param array<int, string> $routes An array of spawned class's. But class's listed
here are not visible by the Ref-Tracker in console. The Class referenced here are private to this route.
If `$routes` is not empty, only the global routes `$routes` a accessible. The base routes quest are not quested.

__Routes precedence__ :

1. Local routes : defined in spawed $routes parameter.
2. Global Base routes : defined in your routes/quest.php.
3. Defaults Global routes : default quest routes.

## spawn

Begin the quest by making their way. Spawn a way.

```php
spawn(): mixed
```

No parameter.

@return mixed

```php
// Ex: 
Route::get('/home', function() {
  $router = new QuestRouter(questRef: 'HhXEo0019', routes: [DemoClass::class]);

  return $router->spaw(); // 🥷🚩 Launch the quest and return result as response.
})
```
