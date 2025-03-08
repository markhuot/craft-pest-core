<?php

namespace markhuot\craftpest\modules\test;

use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public function init(): void
    {
        $this->controllerNamespace = 'markhuot\craftpest\modules\test\controllers';

        if (\Craft::$app->request->isConsoleRequest) {
            $this->controllerNamespace = 'markhuot\craftpest\modules\test\console';
        }
    }
}
