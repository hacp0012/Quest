# 🚩 Attributs

## 🧩 QuestSpawClass [Attribut]

Custruct the spawed class instance.

```php
QuestSpawClass(public array $constructWith = [])
```

@param array<string,mixed> $constructWith Is a list of argument to pass to the class constructor.

- ⚠️ Only an _AsscoArray_ are allowed not indexed array.
- ⚠️ Only primitve data are allowed in the constructWith array value.
- 🚧 But you can use the sugar of Laravel Service Container in the class constructor.

```php
# Laravel Service Container Sugar :

#[QuestSpawClass(['age' => 1])]
class person {
 function __construct(Request $request, int $age) {...}
}
```

## 🧩 QuestSpwn (Attribut)

The Spaw Attribut.

Create a new Spaw Attribut instance.

```php
QuestSpaw(
  string $ref,
  ?QuestSpawMethod $method       = null,
  string|null $filePocket        = null,
  bool $jsonResponse             = true,
  array|string|null $middleware  = null,
  array $alias                   = [],
)
```

@param string `$ref` Quest identifier. _Can be any text you want to use as an identifier_.

- ⚠️ Avoid to put / (slash) in the ID String.

@param string|null `$filePocket` The name of parameter that will receive file.

- ⚠️ The method parameter name, not an alias name.
- ⚠️ For this version, filePocket reference will receive a single `Illuminate\Http\UploadedFile` file.

@param `QuestSpawMethod|null $method` Http method. supporteds [GET, POST and DELETE]. Default is `QuestSpawMethod::POST`. But you can change this behavior in quest config file.

@param bool `$jsonResponse` The return value will be serealized as Json Response. Set it to `false` if you want to return un serealized data.

@param array|string|null `$middleware` The name or array of middlewares.
🏷️ Not that, the middlware is verified when the method provide a middleware.
If the method middleware a provided and have not matched with route (request) middlewares, the method will
not be called.

@param array<string,string> `$alias` The spawed method aliases parameters names.

- the `key` name is the name of the spawed method parameter and
- the `value` is the alias ot this parameter name.

⚠️ Alias affect the `$filePocket` name. In the filesPccket, use the original parameter name; not an alias.

```php
# Exemple:
#[QuestSpaw(ref: 'RrOWXRfKOjauvSpc7y', alias: ['count'=> 'max_weight', 'state' => 'quality'])]
function displayAnApples(int $count, string $color, string $state): View

// The first parameter `$count` become `max_weight`
```
