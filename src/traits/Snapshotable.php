<?php

namespace markhuot\craftpest\traits;

use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\models\MatrixBlockType;

trait Snapshotable
{
    public function assertMatchesSnapshot(...$args)
    {
        expect($this->toSnapshot(...$args))->toMatchSnapshot();

        return $this;
    }

    public function toSnapshotArray(array $extraAttributes = [], ?array $attributes = null)
    {
        $attributes ??= match ($this::class) {
            Entry::class => ['title', 'slug', 'isDraft', 'isRevision', 'isNewForSite', 'isUnpublishedDraft', 'enabled', 'archived', 'uri', 'trashed', 'ref', 'status', 'url'],
            MatrixBlock::class => ['enabled', 'type'],
            Asset::class => ['filename', 'kind', 'alt', 'size', 'width', 'height', 'focalPoint'],
        };

        $customFields = collect($this->getFieldLayout()->getCustomFields())
            ->mapWithKeys(fn ($field) => [$field->handle => $field])

            // remove any ElementQueries from the element so we don't try to snapshot
            // a serialized query. It will never match because it may have a dynamic `->where()`
            // or an `->ownerId` that changes with each generated element.
            ->filter(fn ($field, $handle): bool => ! ($this->{$handle} instanceof ElementQuery));

        return collect($attributes)
            ->merge($extraAttributes)
            ->mapWithKeys(fn ($attribute) => [
                $attribute => $this->{$attribute} ?? null,
            ])
            ->merge($customFields)

            // snapshot any eager loaded element queries so nested elements are downcasted
            // to a reproducible array
            ->map(function ($value, $handle) {
                if ($this->{$handle} instanceof ElementCollection) {
                    $value = $this->{$handle};

                    return $value->map->toSnapshotArray(); // @phpstan-ignore-line can't get PHPStan to reason about the ->map higher order callable
                }
                if ($this->{$handle} instanceof MatrixBlockType) {
                    return collect($this->{$handle}->toArray())->only(['name', 'handle']);
                }

                return $this->{$handle};
            })
            ->toArray();
    }

    /**
     * @param  array  $extraAttributes  Any additional fields that should be included in the snapshot
     * @param  array  $attributes  The default list of attributes that should be included in a snapshot
     */
    public function toSnapshot(array $extraAttributes = [], array $attributes = ['title', 'slug', 'isDraft', 'isRevision', 'isNewForSite', 'isUnpublishedDraft', 'enabled', 'archived', 'uri', 'trashed', 'ref', 'status', 'url'])
    {
        $result = $this->toSnapshotArray($extraAttributes, $attributes);

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
