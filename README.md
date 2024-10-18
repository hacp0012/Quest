![Logo](./doc/assets/quest.png)

# Quest

Access resources directly without defining routes, thanks to PHP attributes.

[**Please visit online documentation here**](https://hacp0012.github.io/Quest/)

## 🪬Introdiction

Quest, the **Master Guru** which simplifies your quest, it gives you a short route to follow to reach your goal (resource).

I know, you don't need to lie to me 🤥, you have remembered when you are brainstorming to implement a functionality or recover resources and ask you: but ... **how do I will organize my Routes?**

The question of the routes, I do not hide you, me, it fucks the laziness. Because I must be defined a road for any call and suddenly I find myself with many of the defined Routes.

I know, it's not perfect, and neither is **Quest**, but... it will make your job a lot easier and eliminates all that mental overload, useful but boring.

## ✨ Installation

### Prerequisites

- PHP 8.0+
- Laravel >= 9.x
- Have already made use of the Facade Route. Ex: `Route::get('route/to/x/{param}', fn(string $param) => X)`

### Install Quest from composer

```bash
composer require hacp0012/quest
```

### Publish the config files

Quest needs a few files to work properly.

```bash
php artisan vendor:publish --tag=quest
```

## 🏳️ How is it useful to me?

Quest allows you to access resources or send your resources directly without worrying about Routes. You just need to set Reference Flags or Reference Marks using PHP attributes on your class methods and call 🤙 these methods directly, with the same parameters as those of the method.

_Don't worry, you just need to respect the same types of parameters that you had defined on your method._

Let's take for example, in a case where you are designing an application and reach a certain level where your application will need to retrieve an up-to-date list of telephone codes. You just have to create a method in a class, reference it and call it; without worrying about creating a route for it.

```php
class PhoneHandler
{
  #[QuestSpaw(ref: 'r84d2S1tM')]
  function getCodes(): array
  {
    //...
  }
}

```

```js
// And call it as this :
axios.get('https://myhost.com/r84d2S1tM');
```

An other exemple :

```php
#[QuestSpaw(ref: 'my quest flag ID', filePocket: 'guidPicture')]
function yogaStage(int $moon, int $sunRise, UploadedFile $guidPicture = null): int
{
  # $guidPicture --> Illuminate\Http\UploadedFile

  return $moon + $sunRise;
}
```

```dart
// So the call will simply be like this:

// Client code :
dio.post("/quest/my quest flag ID", data: {'moon': 2, 'sunRise': 7});
```

Note that Quest takes care of passing parameters to your method. (And you can even pass it a file) as parameters, just give the parameter name to your file. (but you have to report it in filePocket)

## 🚧 How Quest works

Quest is based on PHP attributes. It goes through all your references and creates a registry of the methods you have marked.
A method is marked by a reference key that serves as a reference point for quest to call your method.

To create a reference:

```php
#[QuestSpaw(ref: 'reference.key')]
functiton gong(): array
```

[**Full documentation here**](https://hacp0012.github.io/Quest/)
