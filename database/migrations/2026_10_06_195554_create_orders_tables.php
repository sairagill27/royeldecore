<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // If a user account is deleted, the order stays (user_id becomes empty)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('order_number')->unique();          // e.g. SGH-261006-K7M2Q
            $table->string('status')->default('pending')->index();

            // Money. Always calculated on the server.
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Customer and shipping details, saved as a COPY at the time of the order
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->string('shipping_address');
            $table->string('shipping_city', 100);
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_country', 100);
            $table->text('notes')->nullable();

            // Payment (ready for a gateway later)
            $table->string('payment_method')->default('cod');
            $table->string('payment_status')->default('pending');

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // If an order is deleted, its items go with it
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // If a product is deleted later, the order line stays (product_id becomes empty)
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // COPY of the product details at the time of purchase
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
