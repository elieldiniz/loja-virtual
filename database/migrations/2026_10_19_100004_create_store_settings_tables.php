<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('hours_weekdays')->nullable();
            $table->string('hours_saturday')->nullable();
            $table->string('hours_sunday')->nullable();
            $table->smallInteger('pix_discount_percent')->default(5);
            $table->smallInteger('max_installments')->default(3);
            $table->string('plating_warranty')->nullable();
            $table->string('welcome_message')->nullable();
            $table->string('chat_button_photo_path')->nullable();
            $table->boolean('help_invite_enabled')->default(true);
            $table->smallInteger('help_invite_minutes')->default(2);
            $table->smallInteger('birthday_gift_percent')->default(10);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE store_settings ADD CONSTRAINT store_settings_pix_discount_range CHECK (pix_discount_percent BETWEEN 0 AND 30)');
        DB::statement('ALTER TABLE store_settings ADD CONSTRAINT store_settings_installments_range CHECK (max_installments BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE store_settings ADD CONSTRAINT store_settings_birthday_gift_range CHECK (birthday_gift_percent BETWEEN 0 AND 50)');
        DB::statement('ALTER TABLE store_settings ADD CONSTRAINT store_settings_help_invite_minutes_allowed CHECK (help_invite_minutes IN (1, 2, 3, 5))');

        Schema::create('promo_phrases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_setting_id')->constrained()->cascadeOnDelete();
            $table->string('phrase');
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('chat_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_setting_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('whatsapp_message')->nullable();
            $table->boolean('opens_gift_section')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_shortcuts');
        Schema::dropIfExists('promo_phrases');
        Schema::dropIfExists('store_settings');
    }
};
