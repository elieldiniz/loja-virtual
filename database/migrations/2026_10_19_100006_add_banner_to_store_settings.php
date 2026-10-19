<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            // Banner largo da vitrine (editável na tela Loja). Desligado até a loja preencher.
            $table->boolean('banner_enabled')->default(false);
            $table->string('banner_eyebrow')->nullable();
            $table->string('banner_title')->nullable();
            $table->string('banner_text')->nullable();
            $table->string('banner_button')->nullable();
            // "all", "sale", "gift" ou o id de uma categoria
            $table->string('banner_link')->default('all');
            $table->string('banner_photo_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['banner_enabled', 'banner_eyebrow', 'banner_title', 'banner_text', 'banner_button', 'banner_link', 'banner_photo_path']);
        });
    }
};
