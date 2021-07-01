<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PickBazar\Enums\ProductType;

class CreateNewPickbazarTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variation_options', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('price');
            $table->string('sale_price')->nullable();
            $table->string('quantity');
            $table->boolean('is_disable')->default(false);
            $table->string('sku')->nullable();
            $table->json('options');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products');
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->float('max_price')->nullable();
            $table->float('min_price')->nullable();
        });

        Schema::table('order_product', function (Blueprint $table) {
            $table->unsignedBigInteger('variation_option_id')->after('product_id')->nullable();
            $table->foreign('variation_option_id')->references('id')->on('variation_options');
        });
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->string('meta')->after('value')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider_user_id');
            $table->string('provider');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_options');
    }
}
