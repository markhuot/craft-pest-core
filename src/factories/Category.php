<?php

namespace markhuot\craftpest\factories;

/**
 * @method self title(string $title)
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
        return new \craft\elements\Category();
    }

    public function definition(int $index = 0)
    {
        /** @var \craft\elements\Category $group */
        $group = \Craft::$app->categories->getGroupByHandle($this->groupHandle);

        return array_merge(parent::definition($index), [
            'groupId' => $group->id,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param  \craft\elements\Category  $element
     */
    protected function setAttributes($attributes, $element)
    {
        // Set `groupId` early on the element so that it does break when trying to retrieve the field layout
        if (isset($attributes['groupId'])) {
            $element->groupId = $attributes['groupId'];
            unset($attributes['groupId']);
        }

        return parent::setAttributes($attributes, $element);
    }
}
