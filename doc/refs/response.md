# QuestResponse

QuestResponse allows you not to alter the business logic of your method as to the value that it should return. You can keep the return value intact and be able to use it in other needs. That the presence of Quest does not oblige you much.

Set response for json response data type.
⚠️ To use only when response is json data format.

## parameters

@param string $ref The quest ref. This should be the same as the one provided in QuestSpawn above the method. It is needed to identify which reference to assign the value of $model to and insert `$dataName` into it.

@param array $model Other data you want to return to the client. At the bottom of it will be added a field with the name that contains `$dataName`. and this field will contain the value returned by the method.

@param array<mixed,mixed> $model By default its value is `data`. It will be pasted to the `$model` and it will contain the value retained by the method.

```php
  public static function setForJson(string $ref, array $model = [], string $dataName = 'data'): void
```

Exemple :

```php
#[QuestSpawn(ref: 'same.ref.5L3yEswk5nRgr7zW8p')]
public function countFruits(): int
{
  QuestResponse::setForJson(ref: 'same.ref.5L3yEswk5nRgr7zW8p', model: ['success' => true], dataName: 'count');

  return 18;
}

# The http response:
{
  "success": true,
  "count": 18
}
```

Quest will handle the response.

This just allows you to be able to use your method in other needs. That the presence of Quest does not interfere with the logic of the class.
