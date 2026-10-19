<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\MessageBuilder;
use App\Services\PricingCalculator;
use App\Services\Storefront\StorefrontCatalog;
use App\Services\Storefront\StorefrontLinks;
use App\Support\ProductCardData;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /** GET /pecas/{id}: dados da página da peça (somente peças visíveis na vitrine). */
    public function show(int $id, StorefrontCatalog $catalog, MessageBuilder $messages, StorefrontLinks $links): JsonResponse
    {
        $product = $catalog->query()->whereKey($id)->firstOrFail();
        $settings = StoreSetting::current();
        $card = ProductCardData::fromProduct($product, PricingCalculator::forStore($settings));
        $photos = $product->getMedia(Product::PHOTOS)->sortBy('order_column')->values();

        return response()->json($card->toArray() + [
            'category' => $product->category->name,
            'material' => $product->material->name,
            'description' => $product->description,
            'photos' => $photos->map(fn ($m) => ['url' => $m->getUrl('card'), 'thumb' => $m->getUrl('thumb')])->all(),
            'is_solid_silver' => $product->material->slug === 'prata-925',
            'warranty' => $settings->plating_warranty,
            'question_url' => $links->whatsapp($messages->productQuestion($product->name)),
        ]);
    }
}
