<?php

namespace markhuot\craftpest\factories;

use craft\fields\Matrix;
use craft\models\MatrixBlockType;

trait AddsMatrixBlocks
{
    public function handlesMagicAddsMatrixBlocksCall($key, $args): bool
    {
        return preg_match('/^addBlockTo(.*)$/', (string) $key, $fieldMatches) ||
            preg_match('/^add(.+)To(.*)$/', (string) $key, $blockTypeMatches);
    }

    public function callMagicAddsMatrixBlocksCall(string $key, $args)
    {
        preg_match('/^addBlockTo(.*)$/', $key, $fieldMatches);
        if ($fieldMatches !== []) {
            $fieldName = lcfirst($fieldMatches[1]);

            return $this->addBlockTo($fieldName, ...$args);
        }

        preg_match('/^add(.+)To(.*)$/', $key, $blockTypeMatches);
        if ($blockTypeMatches !== []) {
            $blockType = lcfirst($blockTypeMatches[1]);
            $fieldName = lcfirst($blockTypeMatches[2]);

            return $this->addBlockTo($fieldName, $blockType, ...$args);
        }

        throw new \Exception('Could not determine a matrix field based on ['.$key.']');
    }

    /**
     * Adds a block to the given matrix field.
     */
    public function addBlockTo(Matrix|string $fieldOrHandle, ...$args)
    {
        if (is_string($fieldOrHandle)) {
            /** @var Matrix $field */
            $field = \Craft::$app->fields->getFieldByHandle($fieldOrHandle);
        } elseif ($fieldOrHandle instanceof \craft\fields\Matrix) {
            $field = $fieldOrHandle;
        }

        if (empty($field)) {
            throw new \Exception('Could not determine a field to add to from key ['.$fieldOrHandle.']');
        }

        if (! empty($args[0]) && is_string($args[0])) {
            $blockType = collect($field->getBlockTypes())->where('handle', '=', $args[0])->first();
            array_shift($args);
        } elseif (! empty($args[0]) && is_a($args[0], MatrixBlockType::class)) {
            $blockType = $args[0];
            array_shift($args);
        } else {
            $blockType = $field->getBlockTypes()[0];
        }

        $fieldData = ! empty($args[0]) && is_array($args[0]) ? $args[0] : $args;

        $this->set($field->handle, Block::factory()
            ->type($blockType)
            ->set($fieldData)
        );

        return $this;
    }
}
