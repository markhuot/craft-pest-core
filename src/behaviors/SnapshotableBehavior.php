<?php

namespace markhuot\craftpest\behaviors;

use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\fields\Entries;
use craft\fields\Matrix;
use yii\base\Behavior;

class SnapshotableBehavior extends Behavior
{
    public function toSnapshot()
    {
        return collect($this->owner->toArray())
            ->except([
                'id', 'postDate', 'sectionId', 'uid', 'siteSettingsId',
                'fieldLayoutId', 'contentId', 'dateCreated', 'dateUpdated',
                'canonicalId', 'typeId',
            ])

            // filter out any non-eager loaded queries because we can't snapshot on them, their
            // values change too often between runs
            ->filter(fn ($value, $handle) => ! ($this->owner->{$handle} instanceof ElementQuery))

            // Remap any element collections (eager loaded relations) to their nested snapshots
            ->map(function ($value, $handle) {
                if ($this->owner->{$handle} instanceof ElementCollection) {
                    return $this->owner->{$handle}->map->toSnapshot();
                }

                return $value;
            })
            ->all();
    }
}
