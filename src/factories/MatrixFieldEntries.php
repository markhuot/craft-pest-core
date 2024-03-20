<?php

namespace markhuot\craftpest\factories;

use craft\fields\Matrix;

use function markhuot\craftpest\helpers\test\dd;

class MatrixFieldEntries extends Field
{
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
                ->map
                ->create()
                ->flatten()
                // ->each(function ($entryType, $index) use ($element) {
                //     $entryType->fieldId = $element->id;
                //     $entryType->sortOrder = $index;
                // })
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
        //dd($element->getEntryTypes()[0]->fieldLayout);
        collect($this->entryTypes)
            ->zip($element->getEntryTypes())
            ->each(function ($props) {
                /** @var \craft\models\EntryType $entryType */
                [$factory, $entryType] = $props;
                $factory->storeFields($entryType->fieldLayout, $entryType);

                $entryType->fieldLayoutId = $entryType->fieldLayout->id;
                \Craft::$app->getEntries()->saveEntryType($entryType);
            });

        // In Craft 3.7 the Matrix Field model stores a reference to the `_blockTypes` of the
        // matrix. Inside that reference the block type stores a reference to its `fieldLayoutId`.
        //
        // The reference to the Matrix Field is cached in to \Craft::$app->fields->_fields when the
        // field is created and it's cached without a valid `fieldLayoutId`.
        //
        // The following grabs the global \Craft::$app->fields->field reference to this matrix field
        // and updates the block types by pulling them fresh from the database. This ensures everything
        // is up to date and there are no null fieldLayoutId values.
        /** @var Matrix $cachedMatrixField */
        // $cachedMatrixField = \Craft::$app->fields->getFieldById($element->id);
        // $cachedMatrixField->setEntryTypes(\Craft::$app->matrix->getEntryTypesByFieldId($element->id));

        return $result;
    }
}
