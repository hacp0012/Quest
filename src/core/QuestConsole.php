<?php

namespace Hacp0012\Quest\core;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\QuestRouter;
use Hacp0012\Quest\QuestSpawMethod;
use ReflectionClass;

class QuestConsole
{
  const GLOBAL_TEMP_LIST = 'QUEST_GLOBAL_TEMP_LIST_goObnj6crvx4YFQK6hbgoStEsIQrxrdM7CsA';

  /**
   * Create a new class instance.
   */
  public function __construct() {}

  function generateId(int $length = 36): string
  {
    $generatedId = Str::random($length);

    return $generatedId;
  }

  function generateUuid(): string
  {
    $generatedId = (string) Uuid::uuid4();

    return $generatedId;
  }

  function trackId(string $id): array
  {
    $routes = QuestRouter::routesList();

    $inBaseRouteRoutes = $this->getClassFromRoutes();

    $routes = array_merge($routes, $inBaseRouteRoutes);

    $_className = null;
    $_classNamespace = null;
    $_methodName = null;
    $_methodParams = null;
    $_filePath = null;
    $_methodIsPublic = true;
    $_attribut = null;

    // loop in classes.
    $noMatched = false;

    // if (count($routesRegister->routes) == 0) return ['status' => 'EMPTY_PATHS'];
    if (count($routes) == 0) $noMatched = true;

    foreach ($routes as $class) {
      try {
        $classReflexion = new ReflectionClass($class);
      } catch (\Exception $e) {
        throw new \Exception("The provided quest class '$class' not exist. " /* . $e->__toString() */);
      }

      // loop in methods.
      $methods = $classReflexion->getMethods();

      if (count($methods)) {
        foreach ($methods as $method) {

          // get attributs.
          $attributs = $method->getAttributes(QuestSpaw::class);

          // If not attribut found.
          if (count($attributs) == 0) continue;

          // get attribut instance.
          $attributInst = $attributs[0]->newInstance();

          // ID .
          if (strcmp($attributInst->ref, $id) == 0) {
            //
            $_classNamespace = $class;
            $_className = $classReflexion->getName();
            $_methodName = $method->getName();
            $_methodParams = $method->__toString();
            $_methodIsPublic = $method->isPublic();
            $_filePath = $classReflexion->getFileName();

            $atArgs = [];
            foreach ($method->getAttributes(QuestSpaw::class)[0]->getArguments() as $key => $value) {
              if ($value instanceof QuestSpawMethod) $atArgs[$key] = $value->name;
              elseif ($key == 'ref') continue;
              elseif ($key == 'alias') {
                $t = json_encode($value);
                $t = str_replace(':', ' -> ', $t);
                $t = str_replace('"', '', $t);
                $t = str_replace('{', '(', $t);
                $t = str_replace('}', ')', $t);

                $atArgs[$key] = $t;
              } else $atArgs[$key] = $value;
            }
            $_attribut = count($atArgs) > 0 ? json_encode($atArgs) : null;

            $noMatched = false;
            break;
          } else $noMatched = true;
        }
      }

      if ($noMatched == false) break;
    }

    if ($noMatched) return ['status' => 'UNMATCHED'];
    else return [
      'status' => 'MACTHED',
      'class_name' => $_className,
      'class_namespace' => $_classNamespace,
      'method_name' => $_methodName,
      'method_params' => $_methodParams,
      'method_is_public' => $_methodIsPublic,
      'file_path' => $_filePath,
      'attribut' => $_attribut,
    ];
  }

  private function getClassFromRoutes(): array
  {
    $GLOBALS[QuestConsole::GLOBAL_TEMP_LIST] = [];

    $routes = config('quest.base_routes', []);

    foreach ($routes as $route) include $route;

    $return = $GLOBALS[QuestConsole::GLOBAL_TEMP_LIST];

    unset($GLOBALS[QuestConsole::GLOBAL_TEMP_LIST]);

    return $return;
  }
}
