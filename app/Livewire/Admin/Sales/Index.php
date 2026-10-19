<?php

namespace App\Livewire\Admin\Sales;

use App\Models\PaymentMethod;
use App\Services\Sales\SalesQuery;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public const PER_PAGE = 50;

    #[Url(as: 'periodo')]
    public string $period = 'today';

    #[Url(as: 'busca')]
    public string $search = '';

    #[Url(as: 'pagamento')]
    public string $payment = '';

    #[Url(as: 'situacao')]
    public string $status = '';

    public int $limit = self::PER_PAGE;

    public function mount(): void
    {
        if (session()->has('status')) {
            $this->dispatch('toast', message: session('status'));
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, SalesQuery::PERIODS) ? $period : 'today';
        $this->limit = self::PER_PAGE;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'payment', 'status'], true)) {
            $this->limit = self::PER_PAGE;
        }
    }

    public function showMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function render(SalesQuery $queries)
    {
        $filtered = $queries->filtered($this->period, $this->payment ?: null, $this->status === 'canceled', $this->search);
        $total = (clone $filtered)->count();
        $shown = (clone $filtered)->with(['customer', 'items', 'paymentMethod', 'channel'])->limit($this->limit)->get();

        $groups = $shown->groupBy(fn ($sale) => $sale->sold_at->toDateString())->map(function ($sales) {
            $valid = $sales->whereNull('cancelled_at');

            return [
                'date' => $sales->first()->sold_at,
                'sales' => $sales,
                'count' => $valid->count(),
                'total' => Money::from($valid->sum('total')),
            ];
        });

        return view('livewire.admin.sales.index', [
            'groups' => $groups,
            'summary' => $queries->summary($filtered),
            'remaining' => max(0, $total - $shown->count()),
            'methods' => PaymentMethod::where('is_active', true)->orderBy('id')->get(),
            'hasFilters' => trim($this->search) !== '' || $this->payment !== '' || $this->status !== '',
        ])->layoutData(['current' => 'sales', 'title' => 'Vendas']);
    }
}
