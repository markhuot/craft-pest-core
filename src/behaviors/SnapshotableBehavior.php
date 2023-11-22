<?php

namespace markhuot\craftpest\behaviors;

use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use Illuminate\Support\Collection;
use yii\base\Behavior;

/**
 * @property ElementInterface $owner
 */
class SnapshotableBehavior extends Behavior
{
    public function toSnapshot()
    {
        return collect($this->owner->toArray())
            ->except([
                'id', 'postDate', 'sectionId', 'uid', 'siteSettingsId',
                'fieldLayoutId', 'contentId', 'dateCreated', 'dateUpdated',
                'canonicalId', 'typeId', 'siteId',
            ])

            // filter out any non-eager loaded queries because we can't snapshot on them, their
            // values change too often between runs
            ->filter(fn ($value, $handle) => ! ($this->owner->{$handle} instanceof ElementQuery))

            // Remap any element collections (eager loaded relations) to their nested snapshots
            ->map(function ($value, $handle) {
                if ($this->owner->{$handle} instanceof ElementCollection) {
                    $value = $this->owner->{$handle};
                    return $value->map->toSnapshot(); // @phpstan-ignore-line can't get PHPStan to reason about the ->map higher order callable
                }

                return $value;
            })
            ->all();
    }
}
