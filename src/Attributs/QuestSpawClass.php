<?php

namespace Hacp0012\Quest\Attributs;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class QuestSpawClass
{
  /**
   * Custruct the spawed class instance.
   *
   * @param array<string,mixed> $constructWith Is a list of argument to pass to
   * the class constructor.
   *
   * - ⚠️ Only an _AsscoArray_ are allowed not indexed array.
   * - ⚠️ Only primitve data are allowed in the constructWith array value.
   * - 🚧 But you can use the sugar of Laravel Service Container in the class constructor.
   *
   * ```php
   * # Laravel Service Container Sugar :
   *
   * #[QuestSpawClass(['age' => 1])]
   * class person {
   *  function __construct(Request $request, int $age) {...}
   * }
   * ```
   */
  public function __construct(public array $constructWith = []) {}
}
