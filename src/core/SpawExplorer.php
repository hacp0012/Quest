<?php

namespace Hacp0012\Quest\core;

use Illuminate\Support\Facades\Log;

class SpawExplorer
{
  /** Names of trackeds Attributs. */
  private array $spawNames = ['QuestSpaw'];

  private function removeParentFoldersIndicator(array $array): array
  {
    if (in_array('.', $array)) {
      array_splice($array, array_search('.', $array), 1);
    }

    if (in_array('..', $array)) {
      array_splice($array, array_search('..', $array), 1);
    }

    return $array;
  }

  private function explore(string $base, array $dirs): array
  {
    $collection = [];

    $dirs = $this->removeParentFoldersIndicator($dirs);

    foreach ($dirs as $dir) {
      if (is_file($base . '\\' . $dir)) {
        if (pathinfo($base . '\\' . $dir)['extension'] == 'php') $collection[] = $base . '\\' . $dir;
      }
      elseif (is_dir($base . '\\' . $dir)) {
        $files = $this->explore($base . '\\' . $dir, scandir($base . '\\' . $dir));
        $collection = array_merge($collection, $files);
      }
    }

    return $collection;
  }

  private function fileControl(array $fileList): array
  {
    $matcheds = [];
    $data = [];

    foreach($fileList as $file) {
      foreach($this->spawNames as $spawName) {
        if (preg_match("(#\[[\r\t\n ]*$spawName)", file_get_contents($file), $data)) {
          // dd($data);
          $matcheds[] = $file;
          break;
        }
      }
    }

    return $matcheds;
  }

  private function getNamespace(array $fileList)
  {
    $matcheds = [];
    $data = [];

    foreach ($fileList as $file) {
      if (preg_match("(namespace [ \t]*[\w\\\]*)", file_get_contents($file), $data)) {
        $tmp = preg_replace("(namespace[ \t]+)", '', $data[0]);
        $tmp = str_replace(' ', '', $tmp);

        if ($class = $this->getClass($file)) {
          $tmp .= "\\$class";

          $matcheds[] = $tmp;
        }
      }
    }

    return $matcheds;
  }

  private function getClass(string $file): ?string
  {
    $matcheds = null;
    $data = [];

    // foreach ($fileList as $file) {
      if (preg_match("(class[ \t\r\n]+[\w]+)", file_get_contents($file), $data)) {
        $tmp = preg_replace("(class[ \t]+)", '', $data[0]);
        $tmp = str_replace(' ', '', $tmp);

        $matcheds = $tmp;
      }
    // }

    return $matcheds;
  }

  static function pirify(array $list): array
  {
    $newList = [];

    foreach ($list as $item) {
      if (in_array($item, $list) && in_array($item, $newList)) continue;
      else $newList[] = $item;
    }

    return $newList;
  }

  /** Get spaweds class that containe spawed methods. */
  public function getSpaweds (string $base, array $dirs)
  {
    $matcheds = $this->fileControl($this->explore($base, $dirs));

    // $matcheds = $this->getClass($matcheds);
    $matcheds = $this->getNamespace($matcheds);

    return $matcheds;
  }
}
