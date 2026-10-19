<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CustomerRegistrar;
use App\Services\Sales\SaleData;
use App\Services\Sales\SaleRegistrar;
use App\Support\Money;
use App\Support\SearchText;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Dados de demonstração para desenvolvimento (nunca roda em produção). */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoSeeder ignorado em produção.');

            return;
        }

        $this->call([LookupSeeder::class, StoreSettingSeeder::class]);

        User::updateOrCreate(['email' => 'contato@orvalha.test'], ['name' => 'Orvalha', 'password' => Hash::make('senha123')]);

        StoreSetting::current()->update([
            'whatsapp' => '69999990000', 'instagram' => '@orvalha.semijoias', 'address' => 'Av. Marechal Rondon, 1234 · Centro',
            'hours_weekdays' => '9h às 18h', 'hours_saturday' => '9h às 13h', 'hours_sunday' => 'Fechado', 'plating_warranty' => '6 meses', 'cnpj' => '00.000.000/0001-00',
        ]);

        $gold = Material::where('slug', 'ouro-18k')->first();
        $silver = Material::where('slug', 'prata-925')->first();

        $categories = collect(['Brincos', 'Colares', 'Anéis', 'Pulseiras', 'Conjuntos', 'Piercings'])
            ->map(fn ($name, $i) => Category::firstOrCreate(['name_normalized' => SearchText::normalize($name)], ['name' => $name, 'position' => $i + 1]))
            ->keyBy('name');

        $catalog = [
            ['Argola Lisa Média', 'Brincos', $gold, 89.90, null, true, false],
            ['Brinco Gota Zircônia', 'Brincos', $gold, 119.90, 139.90, true, false],
            ['Colar Ponto de Luz', 'Colares', $gold, 99.90, null, true, false],
            ['Anel Solitário Zircônia', 'Anéis', $silver, 79.90, null, true, false],
            ['Pulseira Elos Portugueses', 'Pulseiras', $gold, 149.90, null, true, false],
            ['Choker Riviera', 'Colares', $silver, 189.90, 219.90, true, false],
            ['Conjunto Pérola Clássica', 'Conjuntos', $gold, 219.90, null, true, false],
            ['Ear Cuff Trançado', 'Brincos', $silver, 69.90, null, false, true],
            ['Colar Coração Vazado', 'Colares', $gold, 129.90, null, false, true],
            ['Pulseira Riviera', 'Pulseiras', $silver, 199.90, null, false, true],
            ['Piercing Fake Duo', 'Piercings', $gold, 49.90, null, false, true],
        ];

        $products = collect($catalog)->map(function ($row, $i) use ($categories) {
            [$name, $category, $material, $price, $old, $best, $new] = $row;

            if ($existing = Product::where('name', $name)->first()) {
                return $existing;
            }

            $product = Product::create([
                'category_id' => $categories[$category]->id, 'material_id' => $material->id, 'name' => $name,
                'price' => $price, 'old_price' => $old, 'is_active' => true, 'is_best_seller' => $best, 'is_new_release' => $new,
            ]);

            foreach ([0, 1] as $shot) {
                $product->addMediaFromString($this->image($name, $material->slug === 'prata-925', $shot))
                    ->usingFileName(str($name)->slug().'-'.($shot + 1).'.jpg')->toMediaCollection(Product::PHOTOS);
            }

            return $product;
        });

        if (Customer::exists()) {
            return;
        }

        $customers = collect([
            ['Ana Paula Souza', '(69) 99812-4431', '12/03'], ['Bruna Oliveira', '(69) 99934-1202', '28/07'], ['Camila Ferreira', '(69) 98411-7720', '05/11'],
            ['Daniela Lima', '(69) 99245-3318', '19/01'], ['Gabriela Martins', '(69) 99654-2280', '14/10'], ['Mariana Costa', '(69) 99903-4417', today()->format('d/m')],
        ])->map(fn ($c) => app(CustomerRegistrar::class)->save(['name' => $c[0], 'whatsapp' => $c[1], 'birthday' => $c[2]]));

        $registrar = app(SaleRegistrar::class);
        $methods = ['pix', 'pix', 'credito', 'dinheiro', 'debito', 'crediario'];
        $channels = ['whatsapp', 'whatsapp', 'loja-fisica', 'instagram'];

        mt_srand(11);
        for ($daysAgo = 75; $daysAgo >= 0; $daysAgo--) {
            foreach (range(1, mt_rand(0, 3)) as $_) {
                $method = $methods[array_rand($methods)];
                $registrar->register(new SaleData(
                    items: collect(range(1, mt_rand(1, 2)))->map(fn () => ['product_id' => $products->random()->id, 'quantity' => 1])->all(),
                    paymentSlug: $method,
                    channelSlug: $channels[array_rand($channels)],
                    installments: $method === 'crediario' ? mt_rand(2, 4) : ($method === 'credito' ? mt_rand(1, 3) : 1),
                    customerId: ($method === 'crediario' || mt_rand(0, 2) === 0) ? $customers->random()->id : null,
                    date: today()->subDays($daysAgo),
                    otherDiscount: Money::zero(),
                ));
            }
        }
    }

    private function image(string $name, bool $silver, int $shot): string
    {
        $w = 800;
        $h = 1000;
        $img = imagecreatetruecolor($w, $h);
        $bg = $shot === 0 ? [247, 239, 236] : [239, 221, 215];
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, ...$bg));
        $metal = $silver ? [170, 176, 184] : [196, 154, 74];

        foreach ([0, 1, 2, 3] as $ring) {
            imagesetthickness($img, 22 - $ring * 4);
            imagearc($img, 400, 500, 360 - $ring * 8, 360 - $ring * 8, 0, 360, imagecolorallocate($img, ...array_map(fn ($c) => min(255, $c + $ring * 14), $metal)));
        }

        imagefilledellipse($img, 400, 330, 46, 46, imagecolorallocate($img, 240, 244, 248));
        ob_start();
        imagejpeg($img, null, 82);

        return (string) ob_get_clean();
    }
}
