<?php

namespace Hacp0012\Quest;

use Closure;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Arr;
use Reflection;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\Attributs\QuestSpawClass;
use Hacp0012\Quest\core\QuestConsole;
use Hacp0012\Quest\core\Obstacle;
use Hacp0012\Quest\core\QuestReturnVoid;
use ReflectionIntersectionType;

/** Quest core handler.
 *
 * The Quest core class.
 */
class Quest
{
  /**
   * Create a new Quest instance.
   */
  public function __construct() {}

  const authorizedAttributs         = ['QuestSpawClass', 'QuestSpaw'];
  const supportedSpawTypes          = ['bool', 'int', 'float', 'string', 'null', 'array', 'mixed'];
  const supportedFilesPocketTypes   = [UploadedFile::class, 'mixed'];
  const allowedMethodModifiers      = ['static', 'public'];

  // QUEST ROUTE -->-------------------------------------------------------------- :
  // Quest::spaw('ref', middlware: []);

  /**
   * Quest Router `QuesetRouter` short hand.
   *
   * @param string $uri
   * ⚠️ At any end of `uri` a `{quest_ref}` route parameter are append. Dont append it twice.
   *
   * @param array<int, string> $routes An array of spawned class's or directories (paths) started at the Laravel base path `base_path()`.
   *
   * __Routes precedence__ :
   * 1. Local routes : defined in spawed $routes parameter.
   * 2. Global Base routes : defined in your routes/quest.php.
   * 3. Defaults Global routes : default quest routes.
   */
  static function spawn(string $uri = 'quest', array $routes = []): RoutingRoute
  {
    # TRACKER :
    { # For console track ref -->:
      $vars = isset($GLOBALS[QuestConsole::GLOBAL_TEMP_LIST]) ? $GLOBALS[QuestConsole::GLOBAL_TEMP_LIST] : [];

      $GLOBALS[QuestConsole::GLOBAL_TEMP_LIST] = array_merge($routes, $vars);
    }

    // Bind routes to Route action closure {#fff, 11}
    $anonymClass = new class($routes) {
      function __construct(protected array $routes) {}
    };

    $closure = function (string $quest_ref) {
      $questRouter = new QuestRouter(questRef: $quest_ref, routes: $this->{'routes'});

      return $questRouter->spawn();
    };

    $bindedClosure = Closure::bind($closure, $anonymClass, $anonymClass);

    # ROUTER :
    if (Str::endsWith($uri, '/')) $uri = Str::replaceEnd('/', '', $uri);

    $router = Route::any(uri: $uri . '/{quest_ref}', action: $bindedClosure);

    # END :
    return $router;
  }

  // METHODS  ROUTER-->----------------------------------------------------------- :
  private array $classTrace = ['line' => null, 'file' => null];
  private array $methodTrace = ['line' => null, 'file' => null];

  /** Internal Main quest router */
  public function router(string $questId, array $classes): mixed
  {
    /** Explore a folder if a folder is provided. */
    $classes = QuestRouter::exploreIfIsFolder($classes);

    // loop in classes.
    foreach ($classes as $class) {
      try {
        $classReflexion = new ReflectionClass($class);
      } catch (\Exception $e) {
        throw new Obstacle(
          "The provided quest class '$class' not exist. " /* . $e->__toString() */,
          file: $this->classTrace['file'],
          line: $this->classTrace['line'],
        );
      }

      // Make class trace.
      $this->makeClassTrace($classReflexion);

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

          // check if id's match.
          if ($this->controlQuestId(questId: $questId, attribut: $attributInst) == false) continue;

          // Make method trace.
          $this->makeMethodTrace($method);

          // Contol middleware.
          if ($attributInst->middleware !== null) {
            if ($this->controlMiddleware($attributInst->middleware) == false) return new QuestReturnVoid;
          }

          // control : Request method | Method avalable.
          $this->controllQuestSpawMethods(spawedQuestAttribut: $attributs[0]);

          // control modifier [only public a authorized].
          $this->controlModifier(method: $method);

          // controller.
          $controlState = $this->controller(method: $method, attribut: $attributs[0]);

          // ? CALL THE METHOD ? {#00f, 1}
          if ($controlState) return $this->call(class: $classReflexion, method: $method);
        }
      }
    }

    return new QuestReturnVoid;
  }

  /** @param \ReflectionClass<object> $class */
  private function makeClassTrace($class): void
  {
    $line = $class->getStartLine();
    $filename = $class->getFileName();

    $this->classTrace['file'] = $filename;
    $this->classTrace['line'] = $line;
  }

  /** @param \ReflectionMethod $method */
  private function makeMethodTrace($method): void
  {
    $line = $method->getStartLine();
    $filename = $method->getFileName();

    $this->methodTrace['file'] = $filename;
    $this->methodTrace['line'] = $line;
  }

  /**
   * @param array|string|null $attribut
   * @param array|string|null $middlewares
   */
  private function controlMiddleware($attributMiddleware): bool
  {
    $middlewares = Route::current()->middleware();

    if (is_array($middlewares)) {
      if (is_array($attributMiddleware)) {
        foreach ($attributMiddleware as $atMid) {
          if (in_array($atMid, $middlewares)) return true;
        }
      } elseif (is_string($attributMiddleware)) {
        if (in_array($attributMiddleware, $middlewares)) return true;
      } // null.
    } elseif (is_string($middlewares)) {
      if ($attributMiddleware == $middlewares) return true;
    } // null.

    return false;
  }

  /**
   * @param \ReflectionAttribute::newInstance $attribut
   */
  private function controlQuestId(string $questId, $attribut): bool
  {
    // Log::debug("--> $questId | " . $attribut->ref);
    if (strcmp($attribut->ref, $questId) == 0) {
      // * ID'S ARE MATCHED * //
      return true;
    } else {
      // // throw new \Exception("");
      return false;
    }
  }

  /** Get Request data */
  function intentionRequest(): ?array
  {
    $request = request();

    // Params :
    $params = (array) $request->input();

    // File :
    if ($request->files->count()) $params = array_merge($params, $request->files->all());

    return $params;
  }

  /** Control modifier [only public & static are authorized].
   *
   * @param \ReflectionMethod $method
   */
  private function controlModifier($method)
  {
    $modifier = implode(' ', Reflection::getModifierNames($method->getModifiers()));
    $methodName = $method->getName();

    if (str_contains(trim($modifier), 'public') == false && str_contains(trim($modifier), 'static') == false)
      throw new Obstacle(
        "Only static and public method are authorized for quest spaw at [$methodName]. ",
        file: $this->methodTrace['file'],
        line: $this->methodTrace['line'],
      );
  }

  /**
   * @param \ReflectionMethod $method
   * @param \ReflectionAttribute $attribut
   */
  private function controller($method, $attribut): bool
  {
    // get attribut properties.
    /** @var \ReflectionAttribute|null */
    $foundedAttribut = null;
    $attributName = Arr::last(explode('\\', $attribut->getName()));

    if ($this->thisAttributExist($attributName)) {
      $foundedAttribut = $attribut;
    }

    if ($foundedAttribut !== null) {
      $this->spawTypeCheker($method, $attribut);

      $filePocketName = $attribut->getArguments();

      $this->compareParameters(
        $this->intentionRequest(),
        $method->getParameters(),
        isset($filePocketName['filePocket']) ? $filePocketName['filePocket'] : null,
        $attribut,
      );

      return true;
    }

    return false;
  }

  private function thisAttributExist(string $name): bool
  {
    return in_array($name, Quest::authorizedAttributs);
    // return Arr::exists(Quest::authorizedAttributs, $name);
  }

  /** @param \ReflectionAttribute $spawedQuestAttribut */
  private function controllQuestSpawMethods($spawedQuestAttribut)
  {
    // Control Http quest method.
    $questMethod = request()->method();
    $questState = match ($questMethod) {
      QuestSpawMethod::GET->name    => true,
      QuestSpawMethod::POST->name   => true,
      QuestSpawMethod::DELETE->name => true,
      QuestSpawMethod::PUT->name    => true,
      QuestSpawMethod::HEAD->name   => true,
      QuestSpawMethod::PATCH->name  => true,

      default => false,
    };

    if ($questState == false) throw new Obstacle(
      "The quest method '$questMethod' are not admit to this quest. Avallable quest method are GET, POST and DELETE. ",
      file: $this->methodTrace['file'],
      line: $this->methodTrace['line'],
    );

    // REQUEST MOTHOD ------------ :
    $spawedQuestAttributInstance = $spawedQuestAttribut->newInstance();

    $providedMethod = $spawedQuestAttributInstance->method;

    if ($providedMethod->name != $questMethod) throw new Obstacle(
      "The spaw method are not match. The expected method are " . $providedMethod->name . " and your provide " . $questMethod .
        ". Default is " . QuestSpawMethod::POST->name,
      file: $this->methodTrace['file'],
      line: $this->methodTrace['line'],
    );
  }

  /** @param \ReflectionMethod $method
   * @param \ReflectionAttribute $attribut
   */
  private function spawTypeCheker($method, $attribut)
  {
    // get file pocket name.
    $spawedQuestAttributInstance = $attribut->newInstance();
    $filePocketName = $spawedQuestAttributInstance->filePocket;

    // method parameters.
    $params = $method->getParameters();
    foreach ($params as $param) {
      /** @var array<int, \ReflectionNamedType|null> */
      $types = [$param->getType()]; # can be null;

      if ($param->getType() instanceof ReflectionUnionType) $types = $param->getType()->getTypes();

      if ($param->getName() == $filePocketName) {
        $isIncorrectType = true;
        foreach ($types as $type) if (in_array($type?->{'getName'}() ?? 'mixed', Quest::supportedFilesPocketTypes)) $isIncorrectType = false;

        if ($isIncorrectType) {
          $paramName = $param->getName();
          throw new Obstacle(
            "The quest file pocket '$paramName' support only [" . implode(', ', Quest::supportedFilesPocketTypes) . "] as types.",
            file: $this->methodTrace['file'],
            line: $this->methodTrace['line'],
          );
        }
      } else {
        foreach ($types as $type) {
          if ($type?->{'getName'}() && in_array($type?->{'getName'}(), Quest::supportedSpawTypes) === false && App::bound($type?->{'getName'}())) {
            // Do nothing.
          } else {
            $isIncorrectType = true;
            if (in_array($type?->{'getName'}() ?? 'mixed', Quest::supportedSpawTypes)) $isIncorrectType = false;

            if ($isIncorrectType) throw new Obstacle(
              "The spaw quest parameter has an unsupported Type '" . $type->{'getName'}() . "'. Expected [" . implode(', ', Quest::supportedSpawTypes) . "] as types. " .
                "Or a class that is bound in Service Container.",
              file: $this->methodTrace['file'],
              line: $this->methodTrace['line'],
            );
          }
        }
      }
    }
  }

  /** @param \ReflectionMethod $method
   * @param \ReflectionAttribute $attribut
   */
  private function intentionTypeChecker(array $intention, $method, $attribut): array
  {
    // get file pocket name.
    $attributParams = $attribut->getArguments();
    $filePocketName = isset($attributParams['filePocket']) ? $attributParams['filePocket'] : null;

    // method parameters.
    $casteds = [];
    $params = $method->getParameters();
    foreach ($params as $param) {
      /** @var array<int, \ReflectionNamedType|null> */
      $types = [$param->getType()]; # can be null;

      if ($param->getType() instanceof ReflectionUnionType) $types = $param->getType()->getTypes();

      $paramName = $this->getAliasName($param->getName(), $attribut);

      $isAutoConstructable = false;
      $autoConstructName = null;
      foreach ($types as $mType) {
        if ($mType instanceof ReflectionNamedType) {
          $typeName = $mType->getName();
          // if ($mType->allowsNull()) $arrayTypes[] = 'null';
          if (App::bound($typeName)) {
            $isAutoConstructable = true;
            $autoConstructName = $typeName;
            break;
          }
        } elseif ($mType instanceof ReflectionUnionType) {
          foreach ($mType->getTypes() as $unionType) {
            $typeName = $unionType->getName();
            // if ($mType->allowsNull()) $arrayTypes[] = 'null';
            if (App::bound($typeName)) {
              $isAutoConstructable = true;
              $autoConstructName = $typeName;
              break;
            }
          }
        } elseif ($mType instanceof ReflectionIntersectionType) {
          foreach ($mType->getTypes() as $intercectionType) {
            $typeName = $intercectionType->{'getName'}();
            // if ($mType->allowsNull()) $arrayTypes[] = 'null';
            if (App::bound($typeName)) {
              $isAutoConstructable = true;
              $autoConstructName = $typeName;
              break;
            }
          }
        }
      }

      if ($filePocketName) $filePocketName = $this->getAliasName($filePocketName, $attribut);

      if ($paramName == $filePocketName) {
        $casteds[] = $this->intentionTypeCast($paramName, isset($intention[$filePocketName]) ? $intention[$filePocketName] : null, $types, $filePocketName);
      } elseif ($isAutoConstructable) {
        $casteds[] = App::make($autoConstructName);
      } else {
        $casteds[] = $this->intentionTypeCast($paramName, isset($intention[$paramName]) ? $intention[$paramName] : null, $types);
      }
    }

    return $casteds;
  }

  /** @param \ReflectionAttribute $attribut instance*/
  private function getAliasName(string $paramName, $attribut): string
  {
    $attributInst = $attribut->newInstance();

    $aliasList = $attributInst->alias;

    $alias = null;
    foreach ($aliasList as $_paramName => $aliasName) if ($_paramName == $paramName) {
      $alias = $aliasName;
      break;
    }

    if ($alias) return $alias;

    return $paramName;
  }

  /** @param mixed $methodPram
   * @param array<int, \ReflectionNamedType|null> $types
   */
  private function intentionTypeCast(string $paramName, mixed $value, $types, ?string $filePocket = null): mixed
  {
    $casted = null;

    if ($filePocket) {
      $request = request();
      if ($request->method() != QuestSpawMethod::POST->name) throw new Obstacle(
        "The files pocket only support the quest method " . QuestSpawMethod::POST->name . ". You use " . $request->method(),
        file: $this->methodTrace['file'],
        line: $this->methodTrace['line'],
      );

      if ($request->hasFile($filePocket))  $casted = $request->file($filePocket);

      else throw new Obstacle(
        "No file found for this pocket name '$filePocket'.",
        file: $this->methodTrace['file'],
        line: $this->methodTrace['line'],
      );
    } else {
      foreach ($types as $type) {
        $typeName = $type?->getName() ?? 'mixed';

        if (($type?->allowsNull() ?? true) && $value === null) $casted = null;
        else {
          $isOfThisType = match ($typeName) {
            'int' => is_numeric($value) ? (is_string($value) && str_contains($value, '.') ? false : true) : true,
            'float' => is_numeric($value),
            'string' => is_string($value),
            'bool' => true,
            'null' => true,
            'mixed' => true,
            'array' => (is_string($value) ? (Json::decode($value) != null) : false) || is_array($value),
            default => false,
          };

          if ($isOfThisType) {
            $casted = match ($typeName) {
              'int' => intval($value),
              'float' => floatval($value),
              'bool' => boolval($value),
              'string' => $value,
              'null' => null,
              'mixed' => $value,
              'array' => is_array($value) ? $value : Json::decode($value) ?? [],
            };
          }
        }

        if ((($type?->allowsNull() ?? true) === false) && $casted === null) {
          // dd(Json::decode([]));
          throw new Obstacle(
            "The spaw parameter '$paramName' dont allow Null value. Expected type '$typeName', provided value : " . Json::encode($value),
            file: $this->methodTrace['file'],
            line: $this->methodTrace['line'],
          );
        }
      }
    }

    return $casted;
  }

  /**
   * @param \ReflectionParameter[] $params
   * @param \ReflectionAttribute|null $attribut
   */
  private function compareParameters(?array $intention, $params, string $filePocket = null, $attribut = null): void
  {
    assert($attribut !== null, "The attribut parameter must not be null.");

    // loop in params.
    foreach ($params as $param) {
      $type = $param->getType();

      $arrayTypes = [];

      if ($type instanceof ReflectionNamedType) {
        $arrayTypes[] = $type->getName();
        if ($type->allowsNull()) $arrayTypes[] = 'null';
      } elseif ($type instanceof ReflectionUnionType) {
        foreach ($type->getTypes() as $unionType) {
          $arrayTypes[] = $unionType->getName();
          if ($type->allowsNull()) $arrayTypes[] = 'null';
        }
      } elseif ($type instanceof ReflectionIntersectionType) {
        foreach ($type->getTypes() as $intercectionType) {
          $arrayTypes[] = $intercectionType->{'getName'}();
          if ($type->allowsNull()) $arrayTypes[] = 'null';
        }
      } elseif ($type === null) $arrayTypes[] = 'mixed';

      // Check in intion.
      if ($intention === null) {
        throw new Obstacle(
          "Your quest has no parameter where " . count($params) . " parameter's are excepted. " .
            "Run the command : php artisan quest:track-ref 'ref key here...' to see method parameters details.",
          file: $this->methodTrace['file'],
          line: $this->methodTrace['line'],
        );
      }

      $paramName = $this->getAliasName($param->getName(), $attribut);

      $intentionPropertyValue = isset($intention[$paramName]) ? $intention[$paramName] : null;

      if ($intentionPropertyValue === null) {
        if ($filePocket) $filePocket = $this->getAliasName($filePocket, $attribut);

        $isAutoConstructable = false;
        $isSupported = false; {
          foreach ($arrayTypes as $aType) {
            if (in_array($aType, Quest::supportedSpawTypes)) {
              $isSupported = true;
              break;
            }

            if (in_array($aType, Quest::supportedSpawTypes) == false && App::bound($aType)) {
              $isAutoConstructable = true;
              break;
            }
          }
        }

        if ($isSupported) {
          if (($filePocket !== null && $filePocket === $paramName)) {
            // Do nothing.
          } else {
            try {
              $param->getDefaultValue(); // Do nothing
            } catch (\Exception $e) {
              throw new Obstacle(
                "The aimed parameter '$paramName' is required.",
                file: $this->methodTrace['file'],
                line: $this->methodTrace['line'],
              );
            }
          }
        } elseif ($isAutoConstructable == false) {
          throw new Obstacle(
            "The type or some of them '" . implode('|', $arrayTypes) .
              "' on the parameter '$paramName', are not supported or are not bound in The Service Container.",
            file: $this->methodTrace['file'],
            line: $this->methodTrace['line'],
          );
        }
      }
    }
  }

  /** @param \ReflectionParameter[] $params
   * @param \ReflectionAttribute $attribut
   */
  private function classTypeChecker(array $constructionParams, $params): array
  {
    // method parameters.
    $casteds = [];
    foreach ($params as $param) {
      /** @var array<int, \ReflectionNamedType|null> */
      $types = [$param->getType()]; # can be null;

      if ($param->getType() instanceof ReflectionUnionType) $types = $param->getType()->getTypes();

      $paramName = $param->getName();

      $isAutoConstructable = false;
      $autoConstructName = null;
      foreach ($types as $mType) {
        if ($mType instanceof ReflectionNamedType) {
          $typeName = $mType->getName();
          // if ($mType->allowsNull()) $arrayTypes[] = 'null';
          if (App::bound($typeName)) {
            $isAutoConstructable = true;
            $autoConstructName = $typeName;
            break;
          }
        } elseif ($mType instanceof ReflectionUnionType) {
          foreach ($mType->getTypes() as $unionType) {
            $typeName = $unionType->getName();
            // if ($mType->allowsNull()) $arrayTypes[] = 'null';
            if (App::bound($typeName)) {
              $isAutoConstructable = true;
              $autoConstructName = $typeName;
              break;
            }
          }
        } elseif ($mType instanceof ReflectionIntersectionType) {
          foreach ($mType->getTypes() as $intercectionType) {
            $typeName = $intercectionType->{'getName'}();
            // if ($mType->allowsNull()) $arrayTypes[] = 'null';
            if (App::bound($typeName)) {
              $isAutoConstructable = true;
              $autoConstructName = $typeName;
              break;
            }
          }
        }
      }

      if ($isAutoConstructable) $casteds[] = App::make($autoConstructName);
      else $casteds[] = $constructionParams[$paramName];
    }

    return $casteds;
  }

  /** Construct the spawed class and return her instance.
   *
   * @param \ReflectionClass<object> $class
   * @return T class instance. */
  function makeClassInstance($class)
  {
    $classAttributs = $class->getAttributes(QuestSpawClass::class);
    $constructionParam = [];

    if (isset($classAttributs[0])) {
      $classAttributs = $classAttributs[0]->newInstance();
      $constructionParam = $classAttributs->constructWith ?? [];
    }

    $classInstance = null;

    $classConstructor = $class->getConstructor();
    $classParams = [];

    if ($classConstructor) $classConstructor->getParameters();

    if (count($classParams)) {
      if (count($constructionParam) && Arr::isAssoc($constructionParam) == false)
        throw new Obstacle(
          "Only associative array<string, value> are allowed on `constructWith` parameter on class attribut '" .
            $class->getName() . "'.",
          file: $this->classTrace['file'],
          line: $this->classTrace['line'],
        );

      try {
        $filledParams = $this->classTypeChecker($constructionParam, $classParams);

        if (count($filledParams) !== count($classParams ?? [])) {
          throw new Obstacle(
            "The quest guid can't construct the class '" . $class->getName() .
              "', beacause parameters provided in QuestSpawClass are too less or to mutch.",
            file: $this->classTrace['file'],
            line: $this->classTrace['line'],
          );
        }

        $classInstance = $class->newInstance(...$filledParams);
      } catch (\Exception $e) {
        throw new Obstacle(
          message: "Parameter error from QuestSpawClass on '" . $class->getName() .
            ". The raison is : " . $e->getMessage(),
          file: $this->classTrace['file'],
          line: $this->classTrace['line'],
        );
      }
    } else $classInstance = $class->newInstanceWithoutConstructor();

    return $classInstance;
  }

  /**
   * @param \ReflectionClass<object> $class
   * @param \ReflectionMethod $method
   * @param array $intention
   */
  public function call($class, $method): mixed
  {
    // Class construction.
    $classInstance = $this->makeClassInstance($class);

    // METHODS --- :
    $newMethodArgList = [];

    $newMethodArgList = $this->intentionTypeChecker($this->intentionRequest(), $method, $method->getAttributes(QuestSpaw::class)[0]);

    // try {
    // ! If wrapped in a Try blok, remember to rethrow the catched exception !
    $result = $method->invokeArgs($classInstance, $newMethodArgList);
    // } catch (\Exception $e) {
    // $methodName = $method->getName();

    // throw $e;

    // throw new \Exception(
    //   "Your quest intention arguments are wrong or are not match the QuestSpaw parameters types or numbers. Spaw method: [$methodName] \n\n" .
    //   $e->__toString(),
    // );
    // }

    // Controll response type.
    $methodAttribut = $method->getAttributes(QuestSpaw::class);

    $instanceOfSpaw = $methodAttribut[0]->newInstance();

    $responseAsjson = $instanceOfSpaw->jsonResponse;

    if ($responseAsjson) {
      return response()->json($result);
    } else
      return $result;
  }
}
