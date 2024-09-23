<?php

namespace Hacp0012\Quest\Attributs;

use Attribute;
use Hacp0012\Quest\QuestSpawMethod;

/** The Spaw Attribut.
 *
 * Use only to spaw (aim) a method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class QuestSpaw
{
  /**
   * Create a new Spaw Attribut instance.
   * @param string $ref Quest identifier. _Can be any text you want to use as an identifier_.
   * - ⚠️ Avoid to put / (slash) in the ID String.
   * @param string|null $filePocket The name of parameter that will receive file.
   * - ⚠️ The method parameter name, not an alias name.
   * - ⚠️ For this version, filePocket reference will receive a single `Illuminate\Http\UploadedFile` file.
   * @param QuestSpawMethod|null $method Request HTTP method. default : POST. Supporteds is
   * [POST, GET, DELETE]. _But you can change this behavior in quest config file._
   * @param bool $jsonResponse The return value will be serealized as Json Response. Set it to `false` if you want to return un serealized data.
   * @param array|string|null $middleware The name or array of middlewares. Not that, the middlware is verified when the parent provide a middleware.
   * if no one match, the spawed method, will not be called.
   * @param array<string,string> $alias The spawed method alises parameters names.
   * - the `key` name is the name of the spawd method parameter and
   * - the `value` is the alias ot this parameter name.
   *
   * ⚠️ Alias affect the `$filePocket` name. In the filesPccket, use the original parameter name; not an alias.
   */
  public function __construct(
    public string $ref,
    public ?QuestSpawMethod $method       = null,
    public string|null $filePocket        = null,
    public bool $jsonResponse             = true,
    public array|string|null $middleware  = null,
    public array $alias                   = [],
  ) {
    if ($method == null) $this->method = config('quest.method', QuestSpawMethod::POST);
  }
}
