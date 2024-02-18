<?php

namespace markhuot\craftpest\factories;

use craft\helpers\StringHelper;

use function markhuot\craftpest\helpers\craft\isBeforeCraftFive;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 * @method void context(string $context)
 */
class Field extends Factory
{
    protected $type;

    public function type(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function group(string $groupName)
    {
        $this->attributes['groupId'] = function () use ($groupName) {
            foreach (\Craft::$app->fields->getAllGroups() as $group) { //@phpstan-ignore-line
                if ($group->name === $groupName) {
                    return $group->id;
                }
            }

            return self::NULL;
        };

        return $this;
    }

    /**
     * Get the element to be generated
     *
     * @return \craft\base\Field
     */
    public function newElement()
    {
        $fieldClass = $this->type;

        return new $fieldClass;
    }

    /**
     * The faker definition
     *
     * @return array
     */
    public function definition(int $index = 0)
    {
        $name = $this->faker->words(2, true);

        $definition = [
            'name' => $name,
        ];

        if (isBeforeCraftFive()) {
            $firstFieldGroupId = \Craft::$app->fields->getAllGroups()[0]->id; // @phpstan-ignore-line
            $definition['groupId'] = $firstFieldGroupId;
        }

        return $definition;
    }

    public function inferences(array $definition = [])
    {
        if (empty($definition['handle']) && ! empty($definition['name'])) {
            $definition['handle'] = StringHelper::toCamelCase($definition['name']);
        }

        return $definition;
    }

    /**
     * Persist the entry to local
     */
    public function store($element): bool
    {
        return \Craft::$app->fields->saveField($element);
    }
}
