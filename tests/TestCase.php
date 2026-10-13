<?php

namespace Tests;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // SAFETY GUARD: tests wipe their database. If they were ever pointed at your real
    // MySQL database, they would erase it. So we stop right away unless it is the
    // temporary in-memory database.
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $default = config('database.default');

        if ($default !== 'sqlite' || config("database.connections.{$default}.database") !== ':memory:') {
            throw new \RuntimeException('STOPPED: tests must use the in-memory sqlite database. Check phpunit.xml.');
        }
    }

    // ---------- helpers ----------
    // is_active is set on purpose: the database default is not loaded into the
    // new object, and the admin guard would otherwise refuse the user.

    protected function makeAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin', 'is_active' => true], $overrides));
    }

    protected function makeStaff(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'staff', 'is_active' => true], $overrides));
    }

    protected function makeCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer', 'is_active' => true], $overrides));
    }

    // A ready-to-use active product. Pass values to change any of them.
    protected function makeProduct(array $overrides = []): Product
    {
        static $n = 0;
        $n++;

        $category = Category::firstOrCreate(
            ['slug' => 'test-decor'],
            ['name' => 'Test Decor', 'is_active' => true, 'sort_order' => 1]
        );

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name'        => "Test Product {$n}",
            'slug'        => "test-product-{$n}",
            'sku'         => "TP-{$n}",
            'description' => 'A test product.',
            'price'       => 1000,
            'stock'       => 10,
            'status'      => 'active',
            'is_featured' => false,
        ], $overrides));
    }

    // Valid checkout form values
    protected function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'customer_name'    => 'Sara Test',
            'customer_email'   => 'sara@example.com',
            'customer_phone'   => '0300 1234567',
            'shipping_address' => 'House 1, Test Street',
            'shipping_city'    => 'Lahore',
            'shipping_country' => 'Pakistan',
        ], $overrides);
    }
}
