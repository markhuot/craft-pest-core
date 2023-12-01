<?php

namespace markhuot\craftpest\exceptions;

use yii\base\Model;

class ModelStoreException extends \Exception
{
    public function __construct(Model $model)
    {
        $message = implode(' ', $model->getErrorSummary(false));
        parent::__construct($message);
    }
}
