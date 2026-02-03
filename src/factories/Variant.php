<?php

namespace markhuot\craftpest\factories;

/**
 * Variant Factory
 *
 * You can easily build product variants using the Variant factory for Craft Commerce.
 * Typically, variants are created automatically when creating products, but this factory
 * allows you to create variants with specific attributes.
 *
 * @method self sku(string $sku)
 * @method self price(float $price)
 * @method self isDefault(bool $isDefault)
 * @method self stock(int $stock)
 * @method self unlimitedStock(bool $unlimitedStock)
 * @method self minQty(int $minQty)
 * @method self maxQty(int $maxQty)
 * @method self width(float $width)
 * @method self height(float $height)
 * @method self length(float $length)
 * @method self weight(float $weight)
 *
 * @extends Element<\craft\commerce\elements\Variant>
 */
class Variant extends Element
{
    /** @var \craft\commerce\elements\Product|int|null */
    protected $productIdentifier = null;

    protected $priorityAttributes = ['productId'];

    /**
     * Set the product for this variant. You may pass:
     *
     * 1. a product object
     * 2. a product id
     */
    public function product($identifier)
    {
        $this->productIdentifier = $identifier;

        return $this;
    }

    /**
     * Get the element to be generated
     *
     * @internal
     */
    public function newElement()
    {
        return new \craft\commerce\elements\Variant;
    }

    /**
     * The faker definition
     *
     * @return array
     */
    public function definition(int $index = 0)
    {
        return [
            'title' => 'Variant '.$this->faker->word(),
            'sku' => 'SKU-'.strtoupper($this->faker->bothify('???-###')),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'isDefault' => false,
            // unlimitedStock was removed in Commerce 5.0+
        ];
    }

    /**
     * @internal
     */
    public function inferences(array $definition = [])
    {
        if ($this->productIdentifier) {
            if (is_numeric($this->productIdentifier)) {
                $definition['productId'] = $this->productIdentifier;
            } elseif (is_object($this->productIdentifier) && isset($this->productIdentifier->id)) {
                $definition['productId'] = $this->productIdentifier->id;
            }
        }

        return $definition;
    }
}
