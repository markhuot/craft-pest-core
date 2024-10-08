<?php

namespace markhuot\craftpest\factories;

/**
 * @method self title(string $title)
 *
 * @extends Element<\craft\elements\Category>
 */
class Category extends Element
{
    /** @var string */
    protected $groupHandle;

    /** {@inheritdoc} */
    protected $priorityAttributes = ['groupId'];

    public function group($handle)
    {
        $this->groupHandle = $handle;

        return $this;
    }

    public function newElement()
    {
        return new \craft\elements\Category;
    }

    public function definition(int $index = 0)
    {
        /** @var \craft\elements\Category $group */
        $group = \Craft::$app->categories->getGroupByHandle($this->groupHandle);

        return array_merge(parent::definition($index), [
            'groupId' => $group->id,
        ]);
    }
}
