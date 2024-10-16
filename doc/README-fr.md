# Quest

![Generated ref code](./doc/assets/quest.png)

Accédez directement aux ressources sans définir des routes

- [Introduction](#introdiction)
- [Instalation](#installation)
- [Usage](#usage)
  - [Le service container](#service_container)
- [Fonctionement](#fonctionement)
- [Api reference](#api_ref)
  - [Quest Spaw (attribut)](#quest_spaw)
  - [Quest Spaw Class (attribut)](#quest_spaw_class)
  - [Quest Router](#quest_route)
  - [Console](#ref_console)
- [FAQ](#faq)

## <span id='introdiction'>🪬Introdiction</span>

Quest, le **maître Guru** qui simplifie votre quête, il vous donne un itinéraire court à suivre pour atteindre votre objectif (ressource).

Je sais, vous n'avez pas besoin de me mentir 🤥, il vous est souvient arrivé, quand vous faites votre brainstorming pour implémenter une fonctionnalité ou récupérer des ressources et de vous demander: Mais... **comment est-ce que je vais organiser mes Routes ?**

La question des Routes, je ne vous cache pas, moi, ca me fout la flemme. Car je doit sois définir un route pour chaques appel et du coup je me retrouve avec des dizaines des Routes défini.

Je sais, nil n'est parfait, ni **Quest** aussi, mais... il va beaucoup vous simplifier la tâche et fait tombe bas tout ces surcharges mentale, utile mais ennuyant.

## <span id="installation">✨ Installation</span>

### Pré-requis

- PHP 8.0+
- Laravel minimum 9.x
- Avoir déjà fais usage de la Facade Route. Ex: `Route::get('route/to/x/{param}', fn(string $param) => X)`

### Installer Quest depuis composer

```bash
composer require hacp0012/quest
```

### Publier les fichiers de configs

Quest à besoin des quelques fichiers pour bien fonctionner.

```bash
php artisan vendor:publish --tag=quest
```

**Le fichier route quest.php**

est un fichier de base qui peut vous être utile pour y enregistrer vos class. Car les classes enregistrés dans cette liste sont publiques du second niveau, car ils ont une priorité qui viens après la liste passé dans votre route `Quest:spaw(routes: [])`

> Ces références sont accessibles depuis toute les requêtes.

Ce fichier est généré automatiquement mais vous pouvez la générer manuellement.

**Le fichier config quest.php**

Contient quelques réglages que vous pouvez appliquer si vous avez fait des motifs dans le bootstrap/provider.php de votre projete pour un ciblage personnalisé des vos fichiers route (/routes/web.php ou /routes/api.php).

Car Traqueur des références doit connaître vos cible pour traquer vos méthodes référencé (poinçonné).

> Pour publier les fichiers de configuration tapez la commande <kbd>php artisan vendor:publish<kbd>

Ceci va crée le fichier `configs/quest.php` (qui contient quelques peux des configuration) et le fichier que routage globale de quest dans `routes/quest.php`

_De façon manuel, vous pouvez publier les fichiers des configs de cette façon <kbd>php artisan quest:publish</kbd> dans le répertoire configs/ et routes/ de façon manuelle._

## 🏳️ Comment est-ce qu'il m'est utile ?

Quest vous permet d'accédez à des ressources ou d'envoyer vos ressources directement sans vous soucié des Routes. Il vous suffit juste de poser des Flags de référence ou des Marques de référence sur vos méthodes de classes et d'appeler 🤙 ce méthode directement, avec comme paramètres, les mêmes que celles de la method.

Ne vous inquiétez pas, il vous suffit juste de respecter les même types de paramètres que vous aviez défini sur votre méthode. Par exemple

```php
#[QuestSpaw(ref: 'my quest flag ID', filePocket: 'guidPicture')]
function yogaStage(int $moon, int $sunRise, UploadedFile $guidPicture = null): int
{
  # $guidPicture --> Illuminate\Http\UploadedFile

  return $moon + $sunRise;
}

# Donc l'appel sera simplement :

// Code client :
dio.post("/quest/my quest flag ID", data: {'moon': 2, 'sunRise': 7});
```

Remarque que Quest se charge de passer des paramètres à vôtre méthode. (Et vous pouvez même lui passer un fichier) comme paramètres, juste de donner le nom du parcmètre à votre fichier. (mais il faut le signaler dans filePocket)

## <span id="fonctionement">🚧 Comment fonctionne Quest</span>

Quest est basé sur les attributs PHP. Il parcours tout vos références et cré un registre des méthodes que vous avez marqué.
Une méthode est marqué par une clé de référence qui sert à quest comme point de repére pour appeler ta méthode.

Pour crée une référence :

```php
#[QuestSpaw(ref: 'cle.de.reference')]
functiton gong(): array
```

## <span id="usage">🧩 Usage<span>

Commençons par définir nôtre route avec Quest :

```php
# Dans votre fichier route
use Hacp0012\Quest\Quest;

Route::get(uri: '/', action: fn() => view('home')); // Exemple ...

Quest::spawn(uri: 'quest', routes: [Forest::class])->name('my.quest');
```

> **`Hacp0012\Quest`** est le namespace principale. Contient la classe `Quest()` et la classe `QuestRouter()` et l'enum `QuestSpawMethod`.

> Puis il y a le namespace **`Hacp0012\Quest\Attributs`**, qui contient les attributs Quest. Tele que `QuestSpaw()` et `QuestSpawClass()`

Vous pouvez ajouter des middlewares et autres car la fonction static `spawn` de Quest renvoi un objer de type `Illuminate\Routing\Route` donc il supporte tout les autres méthodes de la facade Route.

> Noté bien que la class `Forest` a était ajouté dans la liste des routes de la méthode `spaw(..., routes: [Forest::class])`

Définissons maintenent notre class Forest qui va contenir nos méthodes référencé par spaw. _poinconné_.

```php
// Dans votre class
class Forest
{
  #[QuestSpaw(ref:'NAhLlRZW3g3Fbh30dZ')]
  function tree(string $color): int
  {
    return $this->fruits();
  }

  function fruits(): int
  {
    return 18;
  }

  #[QuestSpaw(ref: 'RrOWXRfKOjauvSpc7y', method: QuestSpawMethod::GET, jsonResponse: false)]
  function displayAnApples(int $count): View
  {
    //...
  }
}
```

Et c'est toute, vous pouvez maintenant commencer à appeler vos méthodes poinçonné (référencé) par leur clé de référence `ref: 'NAhLlRZW3g3Fbh30dZ'`.

Noté bien que vous pouvez utiliser n'importé quel phrase comme référence. même si quest vous permet de générer des clé unique. Vous pouvez utiliser comme par ex: _forest.app.tree.NAhLlRZW3g3Fbh30dZ_. [Ou consulter le référence des commandes CLI pour plus des détails](#ref_console)

Comme dans cette exemple ci-dessus :

```dart
// Code client :
dio.get("/quest/NAhLlRZW3g3Fbh30dZ", data: {'color': 'green'});
```

```php
// Ou depuis votre fichier view blad:

route('my.quest', ['quest_ref' => 'RrOWXRfKOjauvSpc7y', 'count' => 9]);
# Il est simple quand vous avez donné un nom à vôtre route. `->name('quest')`.

```

_`quest_ref` est la clé du paramètre du route généré par Quest. le genre de paramètres que l'on passe dans l'url : <https://moonsite.com/my/quest/{quest_id}>_

🔖 Il y a une autre façon de faire appel à quest. C'est de passer QuestRouter et crée un objet router, de cette façon :

```php
Route::post('quest/{ref}', function(string $ref) {
  $quest = new QuestRouter(questRef: $ref, routes: [QuestTest::class]);

  return $quest->spawn();
});
```

Ou

```php
Route::post('quest/{ref}', function(string $ref) {
  $quest = new Quest;

  $data = $quest->router(questId: $ref, classes: [QuestTest::class]);

  return $data;
});
```

⚠️ Même si celui-ci n'est pas la méthode la plus clean, Je vous déconseillé de l'utiliser car il peut vous pondre des type de retour bizarre que même le `Service container` de Laravel ne saura pas interprété.

### <span id="service_container">Service container</span>

Laravel fourni un système d'injection de dépendance automatique qu'il nomme Service Container. Il est capable de construire un objet que vous avez déclarez en paramètre.

Prénom ceci comme rappel :

```php
Route::get('/', function(Request $request, int $number) {
  // Le service container construits automatiquement $request pour vous.
});
```

Et bien quest ne pouvez pas vous gâchez cette bonheur. Quest résout aussi vos object déclaré dans le paramètres.
En tout cas sentez-vous allais de faire ce que vous voulez.

🪄 _Try and you will know._ 🧙‍♂️

## <span id="ref_console">👽 Commandes CLI</span>

> `php artisan quest:generate-ref [36] [--uuid]`

Générer une clé de référence. Mais cela ne vous empêche pas de prendre n'importe quel text pour référence. Ceci est juste un aide, pour vous permettre de faire quelque chose d'unique.

_Si vous ajoutez l'option `--uuid`, il va générer un clé UUID et ignorer la longueur que vous avez précisé. Les UUID comptant 36 caractères (de toutes façon ils sont unique)_

Par défaut la commande génère 36 caractères aléatoire

<kbd>php artisan quest:generate-ref</kbd>

![Generated ref code](./doc/assets/generated_ref.png)

> `php artisan quest:track-ref [ref-id]`

Traquer la référence d'une méthode pointé (spawed)

Parmis les bonnes choses, il y a le ref tracker. Cet traqueur est génial, il vous permet de vous retrouver plus facilement et trouver l'implémentation de votre méthode.

<kbd>php artisan quest:track-ref RrOWXRfKOjauvSpc7y</kbd>

![Tracked reference result](./doc/assets/ref.png)

Car soyons sérieux, le système des clés de référence peut être un peu plus constipants quand on a pas une architecture bien solide ou quand on est débutant. C'est pourquoi je vous conseille de ne pas vous fié non seulement aux clés généré par la commande `quest:generate-ref`, ayez l'habitudes de rajouter quelques mots dites **human readable**. Ex. 'my.forest.trees.meXRQbm0WQP6ZpAN5U'

Pour vérifier la version de quest :

> `php artisan about`

_c'est une commande interne de Laravel_

## <span id="api_ref">🔆 Api reference</span>

- [Quest Attributs](./doc/refs/attributs.md)
- [Quest class](./doc/refs/quest.md)
- [Quest Router](./doc/refs/quester_router.md)
- [CLI Commands](./doc/refs/commands.md)

## Les bonne pratiques

### Le type de retour en commentaire

Prenons cet exemple :

```php
/** @return stdClass {state:UPDATED|FAILED} */
#[QuestSpaw(ref: 'com.update.text.628L7cLg1RGTvaxkgg')]
function updateText(string $com_id, string $title, string $text, string $status): stdClass
{
  $return = new stdClass;

  $state = false;

  // ...

  $return->state = $state ? 'UPDATED' : 'FAILED';

  return $return;
}
```

Veuillez spécifié le type de retour et les détails le concernant, par ce que le traquer renvoie les commentaires PHP-Doc de la méthode. Ca vous aidera pour une idée direct de ce qui est retourné par l'appel.

![Screen shot](./doc/assets/2024-09-09-174755.png)

## Choses à rajouter

- Routes temporaire.

## <span id="#faq">FAQ</span>

### Comment je peux faire mes validations `request` ?

Tout d'abord le paramètres de la méthode sont aussi un autre type de validation mais de bas niveau.
Vous pouvez récupérer tout vos `request parameters` via l'objet `Request` de cette façon :

```php
function myMethod(Request $request, array $myQueryParams)
{
  $validateds = $request->validate([...], [...]);

  $validateds = request()->validate(...);

  # ...
}
```

> De base, quest supporte certains types de base (native) `['bool', 'int', 'float', 'string', 'null', 'array', 'mixed', UploadedFile::class]` et cel que vous aviez lié dans Service Container via Provider. Les autres type ne sont pas prise en charge. La raison en est que, sur le protocole HTTP(S) on ne transfère pas souvent des objets. C'est souvent du textes et souvent formaté en JSON. Donc les types de base (native) sont souvent les mêmes type que l'annotation JSON supporte.
