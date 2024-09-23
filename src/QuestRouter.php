<?php

namespace Hacp0012\Quest;

use Hacp0012\Quest\core\QuestReturnVoid;
use Hacp0012\Quest\core\QuestRoutes;

class QuestRouter extends QuestRoutes
{
  /**
   * Create a new class instance.
   * @param string $questRef Reference ID.
   *
   * @param array<int, string> $routes An array of spawned class's. But class's listed
   * here are not visible by the Ref-Tracker in console. The Class referenced here are private to this route.
   * If `$routes` is not empty, only the global routes `$routes` a accessible. The base routes quest are not quested.
   *
   * __Routes precedence__ :
   * 1. Local routes : defined in spawed $routes parameter.
   * 2. Global Base routes : defined in your routes/quest.php.
   * 3. Defaults Global routes : default quest routes.
   */
  public function __construct(protected string $questRef, array $routes = [])
  {
    parent::__construct();

    QuestRouter::createRouteFile();

    $this->routes = QuestRouter::routesList();

    $this->routes = array_merge($routes, $this->routes);
  }

  /**
   * Begin the quest by making their way.
   * Spawn a way.
   */
  public function spawn()
  {
    $quest = new Quest;

    $questResult = $quest->router(questId: $this->questRef, classes: $this->routes);

    if (($questResult instanceof QuestReturnVoid) == false) return $questResult;
  }

  /** Create quest routes file in routes base dir. */
  static function createRouteFile(): void
  {
    $routeQuestFile = base_path('routes/quest.php');

    if (is_file($routeQuestFile) == false) file_put_contents(
      $routeQuestFile,
      file_get_contents('./publishables/quest_routes.php') ??
        "<?php\n\n" .
        "return [\n" .
        " // Spawed classes names here ...\n" .
        "];\n\n",
    );
  }

  /** Get quest routes list. */
  static function routesList(): array
  {
    $routes = new QuestRoutes;

    $routesList = $routes->routes;

    $routeQuestFile = base_path('routes/quest.php');

    if (is_file($routeQuestFile)) {
      $questRoutes = fn(): array => include $routeQuestFile;

      $routesList = array_merge($questRoutes(), $routes->routes);
    }

    return $routesList;
  }
}
