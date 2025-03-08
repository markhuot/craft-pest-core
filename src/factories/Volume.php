<?php

namespace markhuot\craftpest\factories;

use craft\helpers\StringHelper;
use markhuot\craftpest\test\RefreshesDatabase;
use yii\base\Event;

use function markhuot\craftpest\helpers\base\collection_wrap;
use function markhuot\craftpest\helpers\craft\createVolume;
use function markhuot\craftpest\helpers\craft\volumeDefinition;
use function markhuot\craftpest\helpers\craft\volumeDeleteRootDirectory;

/**
 * @extends Factory<\craft\models\Volume>
 */
class Volume extends Factory
{
    /**
     * Get the element to be generated
     *
     * I can't type this because Craft 3 returns a craft\base\VolumeInterface but
     * Craft 4 returns a \craft\models\Volume
     */
    public function newElement(): \craft\models\Volume|\craft\volumes\Local|null
    {
        return createVolume();
    }

    /**
     * The faker definition
     */
    public function definition(int $index = 0): array
    {
        $name = $this->faker->words(2, true);
        $handle = StringHelper::toCamelCase($name);

        return volumeDefinition([
            'name' => $name,
            'handle' => $handle,
        ]);
    }

    /**
     * Persist the entry to local
     *
     * I can't type this because Craft 3 expects a craft\base\VolumeInterface but
     * Craft 4 expects a \craft\models\Volume
     */
    public function store($element)
    {
        return \Craft::$app->getVolumes()->saveVolume($element);
    }

    /**
     * I can't type this because Craft 3 returns a craft\base\VolumeInterface but
     * Craft 4 returns a \craft\models\Volume
     */
    public function create(array $definition = [])
    {
        $volumes = parent::create($definition);

        Event::on(RefreshesDatabase::class, 'EVENT_ROLLBACK_TRANSACTION', function () use ($volumes): void {
            foreach (collection_wrap($volumes) as $volume) {
                volumeDeleteRootDirectory($volume);
            }
        });

        return $volumes;
    }
}
