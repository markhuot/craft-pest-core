<?php

namespace markhuot\craftpest\factories;

use craft\fields\Matrix;

use function markhuot\craftpest\helpers\test\dd;

class MatrixFieldEntries extends Field
{
    /**
     * @var EntryType[]
     */
    protected $entryTypes = [];

    public function entryTypes(...$entryTypes)
    {
        if (is_array($entryTypes[0])) {
            $this->entryTypes = array_merge($this->entryTypes, $entryTypes[0]);
        } else {
            $this->entryTypes = array_merge($this->entryTypes, $entryTypes);
        }

        return $this;
    }

    public function addEntryType($entryType)
    {
        $this->entryTypes[] = $entryType;

        return $this;
    }

    /**
     * Get the element to be generated
     */
    public function newElement()
    {
        return new \craft\fields\Matrix;
    }

    /**
     * @param  Matrix  $element
     */
    public function store($element): bool
    {
        // Push the block types in to the field
        $element->setEntryTypes(
            collect($this->entryTypes)
                ->map(fn (EntryType $entryType) => $entryType->create())
                ->flatten(1)
                ->toArray()
        );

        // Store the field, which also saves the block types
        $result = parent::store($element);

        // If we have an error, stop here because it will be impossible to save
        // block types on an unsaved/errored matrix field
        if ($result === false) {
            return $result;
        }

        // Add the fields in to the block types
        collect($this->entryTypes)
            ->zip($element->getEntryTypes())
            ->each(function ($props) {
                /** @var \craft\models\EntryType $entryType */
                [$factory, $entryType] = $props;
                $factory->storeFields($entryType->fieldLayout, $entryType);

                $entryType->fieldLayoutId = $entryType->fieldLayout->id;
                \Craft::$app->getEntries()->saveEntryType($entryType);
            });

        return $result;
    }
}
