<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_normalized')->unique();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_new_release')->default(false);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index(['is_active', 'is_best_seller']);
            $table->index(['is_active', 'is_new_release']);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_price_positive CHECK (price > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
