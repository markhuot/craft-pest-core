<?php

namespace markhuot\craftpest\factories;

class Block extends Element
{
    protected ?string $type = null;

    protected bool $enabled = true;

    public function type(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function enabled(bool $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function definition(int $index = 0): array
    {
        return [];
    }

    public function newElement(): array
    {
        return [];
    }

    protected function setAttributes($attributes, $element)
    {
        $element['type'] = $this->type;
        $element['enabled'] = $this->enabled;

        foreach ($attributes as $key => $value) {
            $element['fields'][$key] = $value;
        }

        return $element;
    }

    public function store($element): bool
    {
        // no-op, blocks can't be stored directly, they are returned
        // as arrays for their parent element/field to store.

        return true;
    }
}
