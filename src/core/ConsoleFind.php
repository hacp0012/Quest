<?php

use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\core\QuestConsole;
use Hacp0012\Quest\QuestRouter;

class ConsoleFind
{
  private function routes(): array
  {
    $routes = QuestRouter::routesList();

    $inBaseRouteRoutes = QuestConsole::getClassFromRoutes();

    $routes = array_merge($routes, $inBaseRouteRoutes);

    $routes = QuestRouter::exploreIfIsFolder($routes);

    return $routes;
  }

  /** Get search results. */
  function search(string $keyword)
  {
    $routes = $this->routes();

    // Class name
    // Comments
    // Method
    // Ref

    $list = $this->datas($routes);

    // [[rate, item]]
    $results = [];

    $splidedKeyword = explode(' ', $keyword);

    foreach ($list as $item) {
      $rate = 0;


      foreach ($item as $key => $element) {
        // Prevent to search in some fields.
        if ($key === 'class_namespace' /* || $key === 'class_name' */) continue;

        foreach ($splidedKeyword as $value) {
          $rate += str_contains($element, $value) ? 1 : 0;
        }
      }

      if ($rate) $results[] = ['rate' => $rate, 'item' => $item];
    }

    usort($results, function ($a, $b) {
      if ($a['rate'] == $b['rate']) return 0;

      return ($a['rate'] > $b['rate']) ? -1 : 1;
    });


    $onlyItems = [];

    foreach($results as $item) $onlyItems[] = $item['item'];

    return $onlyItems;

    // dd($keyword, $showRef, $showComments, $data);
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
