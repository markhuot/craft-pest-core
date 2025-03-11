<?php

namespace markhuot\craftpest\factories;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use craft\helpers\StringHelper;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 * @method void context(string $context)
 */
class Field extends Factory
{
    protected $type;

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function group(string $groupName): static
    {
        $this->attributes['groupId'] = function () use ($groupName) {
            foreach (\Craft::$app->fields->getAllGroups() as $group) { // @phpstan-ignore-line
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
     */
    public function definition(int $index = 0): array
    {
        $name = $this->faker->words(2, true);

        $definition = [
            'name' => $name,
        ];

        if (InstalledVersions::satisfies(new VersionParser, 'craftcms/cms', '~4.0')) {
            $firstFieldGroupId = \Craft::$app->fields->getAllGroups()[0]->id; // @phpstan-ignore-line
            $definition['groupId'] = $firstFieldGroupId;
        }

        return $definition;
    }

    public function inferences(array $definition = []): array
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
