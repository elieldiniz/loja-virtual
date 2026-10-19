<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Services\Receivables;
use App\Support\Phone;
use App\Support\SearchText;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public const PER_PAGE = 40;

    #[Url(as: 'busca')]
    public string $search = '';

    #[Url(as: 'filtro')]
    public string $filter = 'all';

    public int $limit = self::PER_PAGE;

    public function setFilter(string $filter): void
    {
        $this->filter = $filter === 'open' ? 'open' : 'all';
        $this->limit = self::PER_PAGE;
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PER_PAGE;
    }

    public function showMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function render()
    {
        $base = Customer::query()
            ->when($this->filter === 'open', fn ($q) => $q->whereHas('installments', fn ($i) => $i->whereNull('installments.paid_at')->whereNull('sales.cancelled_at')))
            ->when(trim($this->search) !== '', function ($q) {
                $text = SearchText::normalize($this->search);
                $digits = Phone::normalize($this->search);

                $q->where(function ($w) use ($text, $digits) {
                    $w->whereRaw(SearchText::sqlNormalized('customers.name').' LIKE ?', ['%'.$text.'%']);

                    if ($digits !== '') {
                        $w->orWhere('customers.whatsapp', 'like', '%'.$digits.'%');
                    }
                });
            });

        $total = (clone $base)->count();
        $query = (clone $base)->withSummary()
            ->orderByDesc('overdue_count')
            ->orderByRaw(SearchText::sqlNormalized('customers.name'));
        $receivables = new Receivables;

        return view('livewire.admin.customers.index', [
            'customers' => $query->limit($this->limit)->get(),
            'total' => $total,
            'registered' => Customer::count(),
            'activeLast30' => Customer::whereHas('sales', fn ($s) => $s->whereNull('cancelled_at')->where('sold_at', '>=', today()->subDays(29)))->count(),
            'receivable' => $receivables->openTotal(),
            'overdue' => $receivables->overdueCount(),
        ])->layoutData(['current' => 'clients', 'title' => 'Clientes']);
    }
}
