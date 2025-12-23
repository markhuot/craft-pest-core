<?php

namespace markhuot\craftpest\actions;

use Composer\Semver\Semver;
use Craft;
use craft\fields\Matrix;
use craft\models\EntryType;
use craft\models\MatrixBlockType;

class GetMatrixBlockTypes
{
    /**
     * Get all block/entry types for a matrix field.
     *
     * @return array<MatrixBlockType|EntryType>
     */
    public function handle(Matrix $field): array
    {
        if (Semver::satisfies(Craft::$app->version, '~5.0')) {
            /** @phpstan-ignore-next-line */
            return $field->getEntryTypes();
        }

        /** @phpstan-ignore-next-line */
        return $field->getBlockTypes();
    }
}
