<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Lojas que nunca configuraram o banner passam a mostrar a capa padrão da STyli Prates.
        DB::table('store_settings')
            ->where('banner_enabled', false)
            ->whereNull('banner_title')
            ->whereNull('banner_photo_path')
            ->update(['banner_enabled' => true]);
    }

    public function down(): void
    {
        // dado de conveniência: nada a desfazer
    }
};
