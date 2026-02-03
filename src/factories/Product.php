<?php

namespace markhuot\craftpest\factories;

/**
 * # Product Factory
 *
 * Product factories allow you to easily create products for testing Craft Commerce functionality.
 * Products are the items you sell in your Commerce store, and each product can have one or more variants.
 *
 * ## Requirements
 *
 * The Product factory requires Craft Commerce 4.0+ to be installed:
 *
 * ```bash
 * composer require craftcms/commerce:^4.0
 * # or for Craft 5
 * composer require craftcms/commerce:^5.0
 * ```
 *
 * ## Basic Usage
 *
 * Create a simple product with default settings:
 *
 * ```php
 * use markhuot\craftpest\factories\Product;
 *
 * it('creates a product', function () {
 *     $product = Product::factory()
 *         ->title('My Product')
 *         ->create();
 *
 *     expect($product->title)->toBe('My Product');
 * });
 * ```
 *
 * ## Working with Product Types
 *
 * Products belong to a product type (similar to how entries belong to sections).
 * You can specify the product type by handle, ID, or object:
 *
 * ```php
 * use markhuot\craftpest\factories\Product;
 * use markhuot\craftpest\factories\ProductType;
 *
 * it('creates products with a specific product type', function () {
 *     // Create a product type first
 *     $productType = ProductType::factory()
 *         ->name('Clothing')
 *         ->handle('clothing')
 *         ->create();
 *
 *     // Create products using that type
 *     $product = Product::factory()
 *         ->productType('clothing')  // by handle
 *         ->title('T-Shirt')
 *         ->create();
 * });
 * ```
 *
 * If you don't specify a product type, one will be created automatically.
 *
 * ## Working with Variants
 *
 * Every product must have at least one variant. A variant represents a purchasable
 * version of the product with a specific SKU and price.
 *
 * ### Simple Products (Single Variant)
 *
 * If you don't specify any variants, a default variant will be created automatically:
 *
 * ```php
 * $product = Product::factory()
 *     ->title('Simple Product')
 *     ->create();
 *
 * // Automatically has one default variant
 * expect($product->getVariants())->toHaveCount(1);
 * ```
 *
 * ### Products with Multiple Variants
 *
 * Create products with multiple variants (like different sizes):
 *
 * ```php
 * $product = Product::factory()
 *     ->title('T-Shirt')
 *     ->variant(['sku' => 'SHIRT-S', 'price' => 19.99])
 *     ->variant(['sku' => 'SHIRT-M', 'price' => 19.99])
 *     ->variant(['sku' => 'SHIRT-L', 'price' => 24.99])
 *     ->create();
 *
 * expect($product->getVariants())->toHaveCount(3);
 * ```
 *
 * ## Common Product Attributes
 *
 * ### Promotable and Free Shipping
 *
 * Control whether a product can be included in promotions or has free shipping:
 *
 * ```php
 * $product = Product::factory()
 *     ->promotable(true)      // Can be used in sales
 *     ->freeShipping(true)    // Qualifies for free shipping
 *     ->create();
 * ```
 *
 * ### Post Date and Expiry Date
 *
 * Set when a product becomes available or expires:
 *
 * ```php
 * $product = Product::factory()
 *     ->postDate('2025-01-01 00:00:00')
 *     ->expiryDate('2025-12-31 23:59:59')
 *     ->create();
 * ```
 *
 * ## Helper Function
 *
 * You can use the `product()` helper function for a shorter syntax:
 *
 * ```php
 * use function markhuot\craftpest\helpers\model\product;
 *
 * $product = product('clothing')
 *     ->title('Hoodie')
 *     ->create();
 * ```
 *
 * ## Creating Multiple Products
 *
 * Use the `count()` method to create multiple products at once:
 *
 * ```php
 * $products = Product::factory()
 *     ->count(10)
 *     ->create();
 *
 * expect($products)->toHaveCount(10);
 * ```
 *
 * ## Using Sequences
 *
 * Create multiple products with varying attributes:
 *
 * ```php
 * $products = Product::factory()
 *     ->sequence(fn ($index) => [
 *         'title' => "Product {$index}",
 *         'promotable' => $index % 2 === 0,
 *     ])
 *     ->count(5)
 *     ->create();
 * ```
 *
 * ## See Also
 *
 * - ProductType Factory - for creating product types
 * - Variant Factory - for creating variants independently
 *
 * @method title(string $title)
 * @method slug(string $slug)
 * @method enabled(bool $enabled)
 *
 * @extends Element<\craft\commerce\elements\Product>
 */
class Product extends Element
{
    /** @var string|\craft\commerce\models\ProductType|int|null */
    protected $productTypeIdentifier = null;

    /** @var array */
    protected $variantData = [];

    /** @var string|int|null */
    protected $taxCategoryIdentifier = null;

    protected $priorityAttributes = ['typeId'];

    /**
     * Set the product type for the product to be created. You may pass a product type
     * in three ways,
     *
     * 1. a product type object (typically after creating one via the `ProductType` factory)
     * 2. a product type id
     * 3. a product type handle
     *
     * If you do not pass a product type, one will be created automatically.
     */
    public function productType($identifier)
    {
        $this->productTypeIdentifier = $identifier;

        return $this;
    }

    /**
     * Add variant data for the product. If no variants are specified, a default variant
     * will be created automatically.
     *
     * ```php
     * Product::factory()
     *   ->productType('clothing')
     *   ->variant(['sku' => 'SHIRT-001', 'price' => 29.99])
     *   ->variant(['sku' => 'SHIRT-002', 'price' => 34.99])
     *   ->create();
     * ```
     */
    public function variant(array $variantData)
    {
        $this->variantData[] = $variantData;

        return $this;
    }

    /**
     * Set the tax category for the product. You may pass a tax category
     * in two ways,
     *
     * 1. a tax category id
     * 2. a tax category handle
     *
     * Note: In Commerce 5.0+, this property has moved to PurchasableStore
     *
     * @deprecated This method is a no-op in Commerce 5.0+
     */
    public function taxCategory($identifier)
    {
        // This property no longer exists on Product in Commerce 5.0+
        // Keeping method for backwards compatibility but it does nothing
        return $this;
    }

    /**
     * Set whether the product is promotable (can be used in sales/discounts)
     * Note: In Commerce 5.0+, this property has moved to PurchasableStore
     *
     * @deprecated This method is a no-op in Commerce 5.0+
     */
    public function promotable(bool $value = true)
    {
        // This property no longer exists on Product in Commerce 5.0+
        // Keeping method for backwards compatibility but it does nothing
        return $this;
    }

    /**
     * Set whether the product has free shipping
     * Note: In Commerce 5.0+, this property has moved to PurchasableStore
     *
     * @deprecated This method is a no-op in Commerce 5.0+
     */
    public function freeShipping(bool $value = true)
    {
        // This property no longer exists on Product in Commerce 5.0+
        // Keeping method for backwards compatibility but it does nothing
        return $this;
    }

    /**
     * Set the post date by passing a `DateTime`, a string representing the date like
     * "2022-04-25 04:00:00", or a unix timestamp as an integer.
     */
    public function postDate(\DateTime|string|int $value)
    {
        $this->setDateField('postDate', $value);

        return $this;
    }

    /**
     * Set the expiration date by passing a `DateTime`, a string representing the date like
     * "2022-04-25 04:00:00", or a unix timestamp as an integer.
     */
    public function expiryDate(\DateTime|string|int $value)
    {
        $this->setDateField('expiryDate', $value);

        return $this;
    }

    /**
     * Date fields in Craft require a `DateTime` object. You can use `->setDateField` to pass
     * in other representations such as a timestamp or a string.
     */
    public function setDateField($key, $value)
    {
        if (is_numeric($value)) {
            $value = new \DateTime('@'.$value);
        } elseif (is_string($value)) {
            $value = new \DateTime($value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Infer the product type based on the class name or identifier
     *
     * @internal
     */
    public function inferTypeId(): ?int
    {
        $commerce = \Craft::$app->getPlugins()->getPlugin('commerce');
        if (! $commerce) {
            throw new \Exception('Craft Commerce plugin is not installed');
        }

        if (is_a($this->productTypeIdentifier, \craft\commerce\models\ProductType::class)) {
            $productType = $this->productTypeIdentifier;
        } elseif (is_numeric($this->productTypeIdentifier)) {
            $productType = \craft\commerce\Plugin::getInstance()->getProductTypes()->getProductTypeById($this->productTypeIdentifier);
        } elseif (is_string($this->productTypeIdentifier)) {
            $productType = \craft\commerce\Plugin::getInstance()->getProductTypes()->getProductTypeByHandle($this->productTypeIdentifier);
        } else {
            $reflector = new \ReflectionClass($this);
            $className = $reflector->getShortName();
            $productTypeHandle = lcfirst($className);
            $productType = \craft\commerce\Plugin::getInstance()->getProductTypes()->getProductTypeByHandle($productTypeHandle);
        }

        return $productType?->id;
    }

    /**
     * Get the element to be generated
     *
     * @internal
     */
    public function newElement()
    {
        return new \craft\commerce\elements\Product;
    }

    /**
     * @internal
     */
    public function inferences(array $definition = [])
    {
        $typeId = $this->inferTypeId();

        // If a product type was passed in but didn't resolve, throw an error
        throw_if(! empty($this->productTypeIdentifier) && empty($typeId), "Could not resolve product type identifier `{$this->productTypeIdentifier}`");

        // If nothing was passed in, and we couldn't infer a product type, create a new product type
        if (empty($this->productTypeIdentifier) && empty($typeId)) {
            $productType = ProductType::factory()->create();
            $typeId = $productType->id;
        }

        return array_merge($definition, [
            'typeId' => $typeId,
        ]);
    }

    /**
     * Override store to handle variants
     */
    public function store($element)
    {
        $element->setScenario($this->scenario ?? \craft\base\Element::SCENARIO_DEFAULT);

        $result = \Craft::$app->elements->saveElement($element);

        if (! $result) {
            return false;
        }

        // Create variants after product is saved
        if (empty($this->variantData)) {
            // Create a default variant using the Variant factory
            $variant = Variant::factory()
                ->product($element)
                ->title($element->title ?: 'Default Variant')
                ->sku($element->title ? strtoupper(str_replace(' ', '-', $element->title)) : 'SKU-'.time())
                ->basePrice(0)
                ->isDefault(true)
                ->create();
        } else {
            // Create variants from the provided data
            foreach ($this->variantData as $index => $data) {
                $factory = Variant::factory()->product($element);

                // Set title
                if (! isset($data['title'])) {
                    $data['title'] = $element->title ?: 'Variant';
                }

                // Set isDefault for first variant
                if (! isset($data['isDefault'])) {
                    $data['isDefault'] = ($index === 0);
                }

                // Set all data using the magic method
                foreach ($data as $key => $value) {
                    if (method_exists($factory, $key)) {
                        $factory->$key($value);
                    } else {
                        // Use magic __call method
                        $factory->$key($value);
                    }
                }

                $factory->create();
            }
        }

        // Refresh the variants from the database after creating them
        $element->setVariants(\craft\commerce\elements\Variant::find()->ownerId($element->id));

        return $result;
    }
}
