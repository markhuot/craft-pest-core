<?php

namespace markhuot\craftpest\actions;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use markhuot\craftpest\interfaces\RenderCompiledClassesInterface;

class RenderCraft5CompiledClasses implements RenderCompiledClassesInterface
{
    public function handle(bool $forceRecreate = false): bool
    {
        $this->render($forceRecreate);

        return true;
    }

    protected function render(bool $forceRecreate): ?bool
    {
        $storedFieldVersion = \Craft::$app->getFields()->getFieldVersion();
        $compiledClassesPath = \Craft::$app->getPath()->getCompiledClassesPath();
        $fieldVersionExists = $storedFieldVersion !== null;
        if (! $fieldVersionExists) {
            $storedFieldVersion = StringHelper::randomString(12);
        }

        $compiledClassPath = $compiledClassesPath.DIRECTORY_SEPARATOR.'FactoryFields_'.$storedFieldVersion.'.php';

        if (file_exists($compiledClassPath) && ! $forceRecreate) {
            return false;
        }

        $this->cleanupOldMixins('FactoryFields_'.$storedFieldVersion.'.php');

        $template = file_get_contents(__DIR__.'/../../stubs/compiled_classes/FactoryFields.twig');

        $compiledClass = \Craft::$app->view->renderString($template, [
            'fields' => \Craft::$app->fields->getAllFields(),
        ]);

        file_put_contents($compiledClassPath, $compiledClass);

        return null;
    }

    protected function cleanupOldMixins(?string $except = null)
    {
        $compiledClassesPath = __DIR__.'/../storage/';

        FileHelper::clearDirectory($compiledClassesPath, [
            'filter' => function (string $path) use ($except): bool {
                $b = basename($path);

                return
                    str_starts_with($b, 'FactoryFields') &&
                    ($except === null || $b !== $except);
            },
        ]);
    }
}
