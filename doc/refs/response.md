# QuestResponse

Class: `Hacp0012\Quest\QuestResponse`.

Adds an HTTP **envelope** (`success`, `message`, extra keys) around a method’s return value **without changing that return type in PHP**.

Use it when the same method is both:

- called over HTTP (client wants `{ success, message, data }`), and
- called from other PHP code (you still want a raw `int`, `array`, …).

Quest applies the envelope **after** the method returns, only if a `QuestResponse` was registered for the same `ref`.

Use it only when `jsonResponse` is `true` (the default).

---

## Quick example

```php
use Hacp0012\Quest\Attributs\QuestSpaw;
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

HTTP JSON:

```json
{
  "success": true,
  "message": "Ok",
  "count": 18
}
```

Other PHP code that calls `countFruits()` still receives `18` (`int`). The envelope is an HTTP concern handled by Quest.

Create the response object **at the top** of the method, with the **same** `ref` as `#[QuestSpaw]`.

Without `QuestResponse`, Quest returns the method value as-is (still JSON-encoded if `jsonResponse` is true): `18`.

---

## `setForJson` (recommended)

```php
public static function setForJson(
    string $ref,
    array $model = [],
    string $dataName = 'data',
): QuestResponse
```

| Argument | Role |
| --- | --- |
| `$ref` | Must equal the method’s `#[QuestSpaw(ref: …)]`. |
| `$model` | Extra keys in the JSON object. The method return value is inserted afterwards. |
| `$dataName` | Key under which the method return value is stored. Default `'data'`. |

Returns the instance so you can chain `success()`, `setMessage()`, `setData()`.

---

## Constructor

```php
public function __construct(?string $ref = null, string $dataName = 'data')
```

Equivalent pattern:

```php
$responser = new QuestResponse(ref: 'forest.count', dataName: 'count');
$responser->success(true);
$responser->setMessage('Ok');
$responser->setData(name: 'car', value: 'Benz');

return 18;
```

If `$ref` is `null`, nothing is registered; Quest will not wrap the return value.

---

## Instance methods

| Method | Role |
| --- | --- |
| `success(?bool $is = null): bool` | Getter/setter. Default `true`. |
| `message(?string $message = null): string` | Getter/setter. Default `null`. |
| `setMessage(?string $message = null): string` | Alias of `message()`. |
| `addToModel(?string $name, mixed $value, ?array $replaceModelWith = null): void` | Set one key, or replace the whole model with `$replaceModelWith`. |
| `setData(?string $name, mixed $value, ?array $replaceModelWith = null): void` | Alias of `addToModel()`. |
| `hasSetted(string $ref): bool` | Whether this `ref` currently owns the envelope. |
| `setAdnGetIt(string $ref, mixed $response): mixed` | Used by Quest: if an envelope exists for `$ref`, merge `$response` under `dataName` and return the array; otherwise return `$response` unchanged. |

`success` and `message` are stored in the model as keys `"success"` and `"message"` when the instance is constructed with a `ref`. Additional keys from `setData` / `addToModel` sit beside them.

---

## Shape of the JSON

Default model after `new QuestResponse($ref)`:

```json
{
  "success": true,
  "message": null,
  "data": <method return value>
}
```

`setForJson` copies `$model` then writes `success` (default `true`) and `message` (default `null`) on top. Extra keys in `$model` are kept.

With `setForJson(ref: '…', model: ['ok' => 1], dataName: 'count')` and `return 18`:

```json
{
  "ok": 1,
  "success": true,
  "message": null,
  "count": 18
}
```

Call `success()` / `setMessage()` **after** `setForJson` to control those two fields:

```php
$response = QuestResponse::setForJson(ref: 'forest.count', dataName: 'count');
$response->success(true);
$response->setMessage('Ok');
return 18;
```

```json
{
  "success": true,
  "message": "Ok",
  "count": 18
}
```

---

## Related

- [Attributes](./attributs.md) — `jsonResponse`
- [Quest](./quest.md)
- [Guide](../README.md)
