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

    protected $hasVariants = false;

    protected $hasDimensions = false;

    protected $skuFormat = '';

    protected $titleFormat = '{product.title}';

    public function hasUrls(bool $hasUrls)
    {
        $this->hasUrls = $hasUrls;

        return $this;
    }

    public function hasVariants(bool $hasVariants)
    {
        $this->hasVariants = $hasVariants;

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

    public function titleFormat(string $titleFormat)
    {
        $this->titleFormat = $titleFormat;

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

        $definition['hasUrls'] = $this->hasUrls;
        $definition['hasVariants'] = $this->hasVariants;
        $definition['hasDimensions'] = $this->hasDimensions;
        $definition['skuFormat'] = $this->skuFormat;
        $definition['titleFormat'] = $this->titleFormat;

        // Set site settings for product URLs
        if ($this->hasUrls) {
            $definition['siteSettings'] = collect(Craft::$app->sites->getAllSites())
                ->mapWithKeys(function ($site) use ($definition) {
                    return [$site->id => [
                        'siteId' => $site->id,
                        'hasUrls' => true,
                        'uriFormat' => 'shop/'.($definition['handle'] ?? 'products').'/{slug}',
                        'template' => 'shop/_product',
                    ]];
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
        if ($element->getFieldLayout()) {
            $this->storeFields($element->getFieldLayout());
        }

        return $result;
    }
}
