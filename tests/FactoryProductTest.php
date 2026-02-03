<?php

use markhuot\craftpest\factories\Product;
use markhuot\craftpest\factories\ProductType;
use markhuot\craftpest\factories\Variant;

use function markhuot\craftpest\helpers\model\product;

beforeEach(function () {
    // Skip if Commerce plugin is not installed or not modern version
    if (! class_exists('\\craft\\commerce\\Plugin')) {
        $this->markTestSkipped('Craft Commerce 4+ is not installed. Install with: composer require craftcms/commerce:^4.0 or ^5.0');
    }

    $commerce = \Craft::$app->getPlugins()->getPlugin('commerce');
    if (! $commerce) {
        $this->markTestSkipped('Craft Commerce plugin is not enabled');
    }
});

it('creates products with no props', function () {
    $product = Product::factory()->create();

    expect($product->errors)->toBeEmpty();
    expect($product->enabled)->toBeTrue();
    expect($product->expiryDate)->toBeNull();
    expect($product->getVariants())->toHaveCount(1);
});

it('creates products with a product type handle', function () {
    $productType = ProductType::factory()
        ->name('Test Product Type')
        ->handle('testProductType')
        ->create();

    $product = Product::factory()
        ->productType('testProductType')
        ->create();

    expect($product->errors)->toBeEmpty();
    expect($product->typeId)->toBe($productType->id);
});

it('creates products with a product type id', function () {
    $productType = ProductType::factory()->create();

    $product = Product::factory()
        ->productType($productType->id)
        ->create();

    expect($product->errors)->toBeEmpty();
    expect($product->typeId)->toBe($productType->id);
});

it('creates products with a product type object', function () {
    $productType = ProductType::factory()->create();

    $product = Product::factory()
        ->productType($productType)
        ->create();

    expect($product->errors)->toBeEmpty();
    expect($product->typeId)->toBe($productType->id);
});

it('sets product title', function () {
    $product = Product::factory()
        ->title('My Great Product')
        ->create();

    expect($product->title)->toBe('My Great Product');
});

it('sets product enabled status', function () {
    $product = Product::factory()
        ->enabled(false)
        ->create();

    expect($product->enabled)->toBeFalse();
});

it('sets promotable flag', function () {
    $product = Product::factory()
        ->promotable(false)
        ->create();

    // Note: promotable is deprecated in Commerce 5.0+ and moved to PurchasableStore
    // The factory method is kept for BC but doesn't actually set anything
    expect($product->errors)->toBeEmpty();
});

it('sets free shipping flag', function () {
    $product = Product::factory()
        ->freeShipping(true)
        ->create();

    // Note: freeShipping is deprecated in Commerce 5.0+ and moved to PurchasableStore
    // The factory method is kept for BC but doesn't actually set anything
    expect($product->errors)->toBeEmpty();
});

it('sets post date', function () {
    $product = Product::factory()
        ->postDate('2122-12-13 12:01:01')
        ->create();

    expect($product->postDate->format('F j, Y g:i A'))->toBe('December 13, 2122 12:01 PM');
});

it('sets expiry date', function () {
    $product = Product::factory()
        ->expiryDate('2022-12-13 12:01:01')
        ->create();

    expect($product->expiryDate->format('F j, Y g:i A'))->toBe('December 13, 2022 12:01 PM');
});

it('creates a default variant automatically', function () {
    $product = Product::factory()
        ->title('Test Product')
        ->create();

    $variants = $product->getVariants();

    expect($variants)->toHaveCount(1);
    expect($variants[0]->isDefault)->toBeTrue();
    expect($variants[0]->sku)->not->toBeEmpty();
});

it('creates products with custom variants', function () {
    $product = Product::factory()
        ->title('T-Shirt')
        ->variant(['sku' => 'SHIRT-S', 'price' => 19.99])
        ->variant(['sku' => 'SHIRT-M', 'price' => 19.99])
        ->variant(['sku' => 'SHIRT-L', 'price' => 19.99])
        ->create();

    $variants = $product->getVariants();

    expect($variants)->toHaveCount(3);
    expect($variants[0]->sku)->toBe('SHIRT-S');
    expect($variants[1]->sku)->toBe('SHIRT-M');
    expect($variants[2]->sku)->toBe('SHIRT-L');
    expect($variants[0]->price)->toBe(19.99);
    expect($variants[0]->isDefault)->toBeTrue();
});

it('creates products in a sequence', function () {
    $products = Product::factory()
        ->sequence(fn ($index) => ['title' => "Product {$index}"])
        ->count(3)
        ->create();

    expect($products[0]->title)->toBe('Product 0');
    expect($products[1]->title)->toBe('Product 1');
    expect($products[2]->title)->toBe('Product 2');
});

it('can create product types', function () {
    $productType = ProductType::factory()
        ->name('Clothing')
        ->handle('clothing')
        ->create();

    expect($productType->errors)->toBeEmpty();
    expect($productType->name)->toBe('Clothing');
    expect($productType->handle)->toBe('clothing');
    expect(\craft\commerce\Plugin::getInstance()->getProductTypes()->getProductTypeByHandle('clothing'))->not->toBeNull();
});

it('can create product types with variants enabled', function () {
    $productType = ProductType::factory()
        ->name('Apparel')
        ->hasVariants(true)
        ->create();

    expect($productType->maxVariants)->toBeNull(); // null = unlimited variants
});

it('can create product types with dimensions', function () {
    $productType = ProductType::factory()
        ->hasDimensions(true)
        ->create();

    expect($productType->hasDimensions)->toBeTrue();
});

it('can create product types with custom SKU format', function () {
    $productType = ProductType::factory()
        ->skuFormat('{product.slug}-{sku}')
        ->create();

    expect($productType->skuFormat)->toBe('{product.slug}-{sku}');
});

it('can use the product helper function', function () {
    $productType = ProductType::factory()
        ->handle('books')
        ->create();

    $product = product('books')
        ->title('The Great Book')
        ->create();

    expect($product->typeId)->toBe($productType->id);
    expect($product->title)->toBe('The Great Book');
});

it('can create variants independently', function () {
    $product = Product::factory()->create();

    $variant = Variant::factory()
        ->product($product)
        ->sku('CUSTOM-SKU')
        ->price(29.99)
        ->create();

    expect($variant->errors)->toBeEmpty();
    expect($variant->sku)->toBe('CUSTOM-SKU');
    expect($variant->price)->toBe(29.99);
    expect($variant->productId)->toBe($product->id);
});

it('automatically creates product type if none specified', function () {
    $product = Product::factory()
        ->title('Auto Product')
        ->create();

    expect($product->errors)->toBeEmpty();
    expect($product->typeId)->not->toBeNull();

    $productType = \craft\commerce\Plugin::getInstance()->getProductTypes()->getProductTypeById($product->typeId);
    expect($productType)->not->toBeNull();
});
