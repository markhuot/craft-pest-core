<?php

namespace markhuot\craftpest\factories;

use craft\helpers\StringHelper;
use craft\models\MatrixBlockType;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 */
class BlockType extends Factory
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

    public function newElement()
    {
        return new MatrixBlockType;
    }

    public function store($blockType): never
    {
        throw new \Exception('Block types can not be saved on their own. They must be saved via their parent Matrix Field.');
    }
}
