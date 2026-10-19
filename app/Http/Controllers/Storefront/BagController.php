<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\BagSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BagController extends Controller
{
    /** GET /sacola/resumo?i=12:2,15:1 */
    public function __invoke(Request $request, BagSummary $summary): JsonResponse
    {
        $quantities = [];

        foreach (array_slice(explode(',', (string) $request->query('i')), 0, 60) as $pair) {
            [$id, $quantity] = array_pad(explode(':', $pair, 2), 2, 0);

            if (ctype_digit($id) && ctype_digit((string) $quantity)) {
                $quantities[(int) $id] = (int) $quantity;
            }
        }

        return response()->json($summary->summarize($quantities));
    }
}
