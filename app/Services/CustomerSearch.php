<?php

namespace App\Services;

use App\Models\Customer;
use App\Support\Phone;
use App\Support\SearchText;
use Illuminate\Support\Collection;

/** Busca de cliente na venda: começo de qualquer palavra do nome (sem acento) ou 3+ dígitos do telefone. */
class CustomerSearch
{
    public const LIMIT = 6;

    /** @return Collection<int, Customer> */
    public function search(string $query, int $limit = self::LIMIT): Collection
    {
        $text = SearchText::normalize($query);
        $digits = Phone::normalize($query);
        $name = SearchText::sqlNormalized('customers.name');

        return Customer::query()
            ->when($text !== '' || $digits !== '', function ($q) use ($text, $digits, $name) {
                $q->where(function ($w) use ($text, $digits, $name) {
                    if ($text !== '') {
                        $w->whereRaw("(' ' || {$name}) LIKE ?", ['% '.addcslashes($text, '%_\\').'%']);
                    }

                    if (strlen($digits) >= 3) {
                        $w->orWhere('customers.whatsapp', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->orderByRaw($name)
            ->limit($limit)
            ->get();
    }
}
