<?php

namespace markhuot\craftpest\console;

use craft\console\Controller;
use markhuot\craftpest\actions\RenderCompiledClasses;
use yii\console\ExitCode;

class IdeController extends Controller
{
    public bool $force = false;

    public function options($actionID): array
    {
        return [
            'force',
        ];
    }

    /**
     * Run the Pest tests
     */
    public function actionGenerateMixins()
    {
        //$result = (new RenderCompiledClasses)->handle($this->force);
        $result = false;

        // @phpstan-ignore-next-line
        if ($result) {
            echo "Mixins successfully generated!\n";
        } else {
            echo "Mixins already exist, skipping.\n";
        }

        return ExitCode::OK;
    }
}
