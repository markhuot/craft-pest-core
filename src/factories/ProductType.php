<?php

namespace markhuot\craftpest\factories;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\helpers\StringHelper;

/**
 * ProductType Factory
 *
 * You can easily build product types using the ProductType factory for Craft Commerce.
 *
 * @method self name(string $name)
 * @method self handle(string $handle)
 * @method \craft\commerce\models\ProductType create(array $definition = [])
 */
class ProductType extends Factory
{
    use Fieldable;

    protected $hasUrls = true;

    protected $maxVariants = null;

    protected $hasDimensions = false;

    protected $skuFormat = '';

    protected $variantTitleFormat = '{product.title}';

    public function hasUrls(bool $hasUrls)
    {
        $this->hasUrls = $hasUrls;

        return $this;
    }

    public function hasVariants(bool $hasVariants)
    {
        $this->maxVariants = $hasVariants ? null : 1;

        return $this;
    }

    public function hasDimensions(bool $hasDimensions)
    {
        $this->hasDimensions = $hasDimensions;

        return $this;
    }

    public function skuFormat(string $skuFormat)
    {
        $this->skuFormat = $skuFormat;

        return $this;
    }

    public function variantTitleFormat(string $variantTitleFormat)
    {
        $this->variantTitleFormat = $variantTitleFormat;

        return $this;
    }

    /**
     * Get the element to be generated
     *
     * @return \craft\commerce\models\ProductType
     */
    public function newElement()
    {
        return new \craft\commerce\models\ProductType;
    }

    /**
     * The faker definition
     *
     * @return array
     */
    public function definition(int $index = 0)
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
        ];
    }

    public function inferences(array $definition = [])
    {
        if (! empty($definition['name']) && empty($definition['handle'])) {
            $definition['handle'] = StringHelper::toCamelCase($definition['name']);
        }

        // Set valid ProductType model properties
        $definition['hasDimensions'] = $this->hasDimensions;
        $definition['maxVariants'] = $this->maxVariants;
        $definition['skuFormat'] = $this->skuFormat;
        $definition['variantTitleFormat'] = $this->variantTitleFormat;

        // Set site settings for product URLs
        if ($this->hasUrls) {
            $definition['siteSettings'] = collect(Craft::$app->sites->getAllSites())
                ->mapWithKeys(function ($site) use ($definition) {
                    $siteSettings = new \craft\commerce\models\ProductTypeSite([
                        'siteId' => $site->id,
                        'hasUrls' => true,
                        'uriFormat' => 'shop/'.($definition['handle'] ?? 'products').'/{slug}',
                        'template' => 'shop/_product',
                    ]);

                    return [$site->id => $siteSettings];
                })->toArray();
        }

        return $definition;
    }

    /**
     * Persist the product type to storage
     *
     * @param  \craft\commerce\models\ProductType  $element
     */
    public function store($element)
    {
        $result = Commerce::getInstance()->getProductTypes()->saveProductType($element);
        throw_unless(empty($element->errors), 'Problem saving product type: '.implode(', ', $element->getFirstErrors()));

        // Store fields for the product field layout if any were defined
        // @phpstan-ignore-next-line
        if ($element->getFieldLayout()) {
            $this->storeFields($element->getFieldLayout());
        }

        return $result;
    }
}
