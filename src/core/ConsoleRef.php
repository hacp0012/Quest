<?php

use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\core\QuestConsole;
use Hacp0012\Quest\QuestRouter;

class ConsoleRef
{
  private function routes(): array
  {
    $routes = QuestRouter::routesList();

    $inBaseRouteRoutes = QuestConsole::getClassFromRoutes();

    $routes = array_merge($routes, $inBaseRouteRoutes);

    $routes = QuestRouter::exploreIfIsFolder($routes);

    return $routes;
  }

  /** Get Ref list */
  function getList()
  {
    $routes = $this->routes();

    $list = $this->datas($routes);

    return $list;
  }

  private function datas(array $data): array
  {
    // [[namespace, class, comment, method, ref, line, filename]]
    $list = [];

    foreach ($data as $route) {
      $data = [];

      $class = new ReflectionClass($route);
      $data['class_name'] = $class->getShortName();
      $data['class_namespace'] = $class->getNamespaceName();
      $data['file_name'] = $class->getFileName();

      $methods = $class->getMethods();

      foreach ($methods as $method) {
        $_attrubuts = $method->getAttributes(QuestSpaw::class);
        if (count($_attrubuts) > 0) {
          $arguments = $_attrubuts[0]->getArguments();
          if (isset($arguments['ref'])) {
            $data['ref'] = $arguments['ref'];
            $data['method'] = $method->getName();
            $data['line'] = $method->getStartLine();
            $data['comment'] = $method->getDocComment();

            $list[] = $data;
          }
        }
      }
    }

    return $list;
  }
}
