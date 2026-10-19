<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('whatsapp')->nullable()->unique();
            $table->string('city')->nullable();
            $table->smallInteger('birth_day')->nullable();
            $table->smallInteger('birth_month')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE SEQUENCE IF NOT EXISTS sales_number_seq START 1');

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_channel_id')->constrained()->restrictOnDelete();
            $table->smallInteger('installments_count')->default(1);
            $table->date('first_due_date')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('pix_discount', 10, 2)->default(0);
            $table->decimal('other_discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamp('sold_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('sold_at');
            $table->index('cancelled_at');
        });

        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_installments_range CHECK (installments_count BETWEEN 1 AND 6)');

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('category_name')->nullable();
            $table->string('material_name')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->smallInteger('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sale_items ADD CONSTRAINT sale_items_quantity_positive CHECK (quantity >= 1)');

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['sale_id', 'number']);
            $table->index(['paid_at', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        DB::statement('DROP SEQUENCE IF EXISTS sales_number_seq');
        Schema::dropIfExists('customers');
    }
};
