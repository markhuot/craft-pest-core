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
        $customFields = collect($this->owner->getFieldLayout()->getCustomFields())
            ->mapWithKeys(function ($field) {
                return [$field->handle => $field];
            })
            ->filter(fn ($field, $handle) => ! ($this->owner->{$handle} instanceof ElementQuery))
            ->map(function ($value, $handle) {
                if ($this->owner->{$handle} instanceof ElementCollection) {
                    $value = $this->owner->{$handle};
                    return $value->map->toSnapshot(); // @phpstan-ignore-line can't get PHPStan to reason about the ->map higher order callable
                }

                return $value;
            });

        return $customFields->set([
            'title' => $this->owner->title,
            'enabled' => $this->owner->enabled,
            'archived' => $this->owner->archived,
            'uri' => $this->owner->uri,
            'trashed' => $this->owner->trashed,
            'ref' => $this->owner->ref,
            'status' => $this->owner->status,
            'url' => $this->owner->url,
        ])->all();
    }
}
