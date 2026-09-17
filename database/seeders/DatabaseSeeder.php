<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $electronics = Category::updateOrCreate(
            ['slug' => 'dien-tu'],
            ['name' => 'Điện tử', 'description' => 'Thiết bị điện tử và phụ kiện công nghệ', 'status' => 'active'],
        );

        Category::updateOrCreate(
            ['slug' => 'thoi-trang'],
            ['name' => 'Thời trang', 'description' => 'Quần áo, giày dép và phụ kiện', 'status' => 'active'],
        );

        Category::updateOrCreate(
            ['slug' => 'thuc-pham'],
            ['name' => 'Thực phẩm', 'description' => 'Thực phẩm và đồ uống', 'status' => 'inactive'],
        );

        foreach ([
            ['sku' => 'PRD001', 'name' => 'Gaming Joy Stick', 'brand' => 'Brand Name', 'price' => 99.99, 'quantity' => 150, 'image' => 'product-1.png'],
            ['sku' => 'PRD002', 'name' => 'Wireless Earphones', 'brand' => 'Tech Pro', 'price' => 89.99, 'quantity' => 320, 'image' => 'product-2.png'],
            ['sku' => 'PRD003', 'name' => 'Smart Watch Pro', 'brand' => 'Tech Pro', 'price' => 98.00, 'quantity' => 200, 'image' => 'product-3.png'],
            ['sku' => 'PRD004', 'name' => 'USB-C Fast Charger', 'brand' => 'Tech Pro', 'price' => 86.00, 'quantity' => 80, 'image' => 'product-4.png'],
            ['sku' => 'PRD005', 'name' => 'Portable Bluetooth Speaker', 'brand' => 'Tech Pro', 'price' => 32.00, 'quantity' => 110, 'image' => 'product-5.png'],
            ['sku' => 'PRD006', 'name' => 'Magic Keyboard', 'brand' => 'Tech Pro', 'price' => 49.00, 'quantity' => 10, 'image' => 'product-6.png'],
            ['sku' => 'PRD007', 'name' => 'MacBook Pro 16"', 'brand' => 'Tech Pro', 'price' => 99.00, 'quantity' => 10, 'image' => 'product-7.png'],
            ['sku' => 'PRD008', 'name' => 'Wireless Earphones', 'brand' => 'Tech Pro', 'price' => 109.00, 'quantity' => 200, 'image' => 'product-8.png'],
        ] as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                array_merge($product, ['category_id' => $electronics->id, 'unit' => 'pcs', 'status' => 'active']),
            );
        }
    }
}
