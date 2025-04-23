<?php

namespace Hacp0012\Quest;

/**
 * Handle quest response.
 * And don't alter method return value (type).
 *
 * - Initialize this instance at the top in your function declaration.
 * to be sure tha you use this ref.
 */
class QuestResponse
{
    const GLOBAL_REF_NAME = 'GLOBAL_REF_NAME_5L3yEswk5nRgr7zW8p';

    public function __construct(private string|null $ref = null, private string $dataName = 'data')
    {
        $this->loadIt();
    }

    private array $params = [];
    private bool $_success = true;

    public function loadIt(): void
    {
        if ($this->ref === null) return;

        $this->params['success'] = $this->_success;

        # To avoid other colleds methods to set her ref.
        if (isset($GLOBALS[QuestResponse::GLOBAL_REF_NAME]) == false) {
            $GLOBALS[QuestResponse::GLOBAL_REF_NAME] = [
                'ref'           => $this->ref,
                'data_name'     => $this->dataName,
                'params'        => $this->params,
            ];
        } elseif (isset($GLOBALS[QuestResponse::GLOBAL_REF_NAME]) && $GLOBALS[QuestResponse::GLOBAL_REF_NAME]['ref'] == $this->ref) {
            $GLOBALS[QuestResponse::GLOBAL_REF_NAME] = [
                'ref'           => $this->ref,
                'data_name'     => $this->dataName,
                'params'        => $this->params,
            ];
        }

    }

    /** Set success state. Default is TRUE. */
    public function success(bool|null $is = null): bool
    {
        if ($is !== null) {
            $this->_success = $is;

            $this->loadIt();
        }

        return $this->_success;
    }

    /** Add value to Model | Or set new model value. */
    public function addToModel(string|null $name = null, mixed $value = null, array|null $replaceModelWith = null): void
    {
        if ($name == null && $replaceModelWith == null) return;

        if ($replaceModelWith) $this->params = $replaceModelWith;
        else $this->params[$name] = $value;

        $this->loadIt();
    }

    /**
     * Set response for json response data type.
     * ⚠️ To use only when response is json data format.
     *
     * @param string $ref The quest ref. This should be the same as the one provided in QuestSpawn above the method. It is needed to identify which reference to assign the value of $model to and insert `$dataName` into it.
     * @param array $model Other data you want to return to the client. At the bottom of it will be added a field with the name that contains `$dataName`. and this field will contain the value returned by the method.
     * @param array<mixed,mixed> $model By default its value is `data`. It will be pasted to the `$model` and it will contain the value retained by the method.
     *
     */
    public static function setForJson(string $ref, array $model = [], string $dataName = 'data'): QuestResponse
    {
        $responser = new QuestResponse(ref: $ref, dataName: $dataName);

        $responser->params      = $model;

        $responser->loadIt();

        return $responser;
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
