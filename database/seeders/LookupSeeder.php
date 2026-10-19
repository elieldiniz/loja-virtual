<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['name' => 'Ouro 18k', 'slug' => 'ouro-18k'],
            ['name' => 'Prata 925', 'slug' => 'prata-925'],
        ] as $row) {
            DB::table('materials')->upsert($row + ['created_at' => $now, 'updated_at' => $now], ['slug'], ['name']);
        }

        foreach ([
            ['name' => 'WhatsApp', 'slug' => 'whatsapp'],
            ['name' => 'Loja física', 'slug' => 'loja-fisica'],
            ['name' => 'Instagram', 'slug' => 'instagram'],
        ] as $row) {
            DB::table('sale_channels')->upsert($row + ['created_at' => $now, 'updated_at' => $now], ['slug'], ['name']);
        }

        foreach ([
            ['name' => 'Pix', 'slug' => 'pix', 'allows_installments' => false, 'max_installments' => 1, 'applies_pix_discount' => true, 'generates_receivables' => false],
            ['name' => 'Dinheiro', 'slug' => 'dinheiro', 'allows_installments' => false, 'max_installments' => 1, 'applies_pix_discount' => false, 'generates_receivables' => false],
            ['name' => 'Débito', 'slug' => 'debito', 'allows_installments' => false, 'max_installments' => 1, 'applies_pix_discount' => false, 'generates_receivables' => false],
            ['name' => 'Crédito', 'slug' => 'credito', 'allows_installments' => true, 'max_installments' => 6, 'applies_pix_discount' => false, 'generates_receivables' => false],
            ['name' => 'Crediário', 'slug' => 'crediario', 'allows_installments' => true, 'max_installments' => 6, 'applies_pix_discount' => false, 'generates_receivables' => true],
        ] as $row) {
            DB::table('payment_methods')->upsert(
                $row + ['created_at' => $now, 'updated_at' => $now],
                ['slug'],
                ['name', 'allows_installments', 'max_installments', 'applies_pix_discount', 'generates_receivables'],
            );
        }
    }
}
