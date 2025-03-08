<?php

namespace markhuot\craftpest\factories;

use Craft;
use craft\helpers\StringHelper;
use craft\models\EntryType as EntryTypeModel;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 * @method self hasTitleField(bool $hasTitleField)
 */
class EntryType extends Factory
{
    use Fieldable;

    public function definition(int $index = 0): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
        ];
    }

    public function inferences(array $definition = []): array
    {
        if (empty($definition['handle']) && ! empty($definition['name'])) {
            $definition['handle'] = StringHelper::toCamelCase($definition['name']);
        }

        return $definition;
    }

    public function newElement(): \craft\models\EntryType
    {
        return new EntryTypeModel;
    }

    public function store($entryType)
    {
        return Craft::$app->getEntries()->saveEntryType($entryType);
    }
}
