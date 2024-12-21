<?php

namespace Hacp0012\Quest;

/**
 * Handle quest response.
 * And don't alter method return value (type).
 */
class QuestResponse
{
  const GLOBAL_REF_NAME = 'GLOBAL_REF_NAME_5L3yEswk5nRgr7zW8p';

  /**
   * Set response for json response data type.
   * ⚠️ To use only when response is json data format.
   *
   * @param string $ref The quest ref. This should be the same as the one provided in QuestSpawn above the method. It is needed to identify which reference to assign the value of $model to and insert `$dataName` into it.
   * @param array $model Other data you want to return to the client. At the bottom of it will be added a field with the name that contains `$dataName`. and this field will contain the value returned by the method.
   * @param array<mixed,mixed> $model By default its value is `data`. It will be pasted to the `$model` and it will contain the value retained by the method.
   *
   */
  public static function setForJson(string $ref, array $model = [], string $dataName = 'data'): void
  {
    $global = [
      'ref' => $ref,
      'data_name' => $dataName,
      'params' => $model,
    ];

    $GLOBALS[QuestResponse::GLOBAL_REF_NAME] = $global;
  }

  /** Check if `ref` is setted in the globale variable $GLOBALS. */
  public function hasSetted(string $ref): bool
  {
    if (isset($GLOBALS[QuestResponse::GLOBAL_REF_NAME]) && strcmp($GLOBALS[QuestResponse::GLOBAL_REF_NAME]['ref'], $ref) == 0) {
      return true;
    }

    return false;
  }

  /** Set and get data stored to a `ref` key settd by `setForJson`.
   * If no ref has setted, return value will be the `response` data.
   */
  public function setAdnGetIt(string $ref, mixed $response): mixed
  {
    if ($this->hasSetted(ref: $ref)) {
      $dataName = $GLOBALS[QuestResponse::GLOBAL_REF_NAME]['data_name'];
      $params = $GLOBALS[QuestResponse::GLOBAL_REF_NAME]['params'];

      $params[$dataName] = $response;

      return $params;
    }

    return $response;
  }
}
