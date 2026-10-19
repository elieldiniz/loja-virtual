<?php

namespace App\Livewire\Admin\Sales;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\StoreSetting;
use App\Services\CustomerSearch;
use App\Services\SaleCalculator;
use App\Services\Sales\SaleData;
use App\Services\Sales\SaleRegistrar;
use App\Support\Money;
use App\Support\SearchText;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
class Create extends Component
{
    public const PRODUCT_LIMIT = 120;

    public string $productSearch = '';

    /** @var array<int, int> id da peça => quantidade */
    public array $cart = [];

    #[Url(as: 'cliente')]
    public ?int $customerId = null;

    public string $customerQuery = '';

    public bool $comboOpen = false;

    public string $payment = 'pix';

    public int $installments = 1;

    public string $channel = 'whatsapp';

    public string $date = '';

    public string $extraDiscount = '';

    public string $firstDue = '';

    public bool $showQuick = false;

    public function mount(): void
    {
        $this->date = today()->toDateString();
        $this->firstDue = today()->addMonthNoOverflow()->toDateString();

        if ($this->customerId !== null && ! Customer::whereKey($this->customerId)->exists()) {
            $this->customerId = null;
        }
    }

    public function add(int $productId): void
    {
        if (! Product::whereKey($productId)->exists()) {
            return;
        }

        $this->cart[$productId] = ($this->cart[$productId] ?? 0) + 1;
        $this->resetErrorBag('items');
    }

    public function changeQuantity(int $productId, int $delta): void
    {
        $quantity = ($this->cart[$productId] ?? 0) + $delta;

        if ($quantity <= 0) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId] = $quantity;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->extraDiscount = '';
    }

    public function updatedPayment(): void
    {
        $method = PaymentMethod::where('slug', $this->payment)->first();

        // Ao trocar para crediário o padrão vira 2x; as demais formas voltam para à vista.
        $this->installments = $method?->generates_receivables ? 2 : 1;
        $this->resetErrorBag(['payment', 'installments', 'customer']);
    }

    public function updatedCustomerQuery(): void
    {
        $this->comboOpen = true;
    }

    public function openCombo(): void
    {
        $this->comboOpen = true;
    }

    public function closeCombo(): void
    {
        $this->comboOpen = false;
    }

    public function pickCustomer(int $id): void
    {
        $customer = Customer::find($id);

        if ($customer === null) {
            return;
        }

        $this->customerId = $customer->id;
        $this->customerQuery = '';
        $this->comboOpen = false;
        $this->resetErrorBag('customer');
        $this->dispatch('toast', message: $customer->name.' selecionada');
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
    }

    public function openQuick(): void
    {
        $this->showQuick = true;
        $this->comboOpen = false;
    }

    #[On('quick-closed')]
    public function closeQuick(): void
    {
        $this->showQuick = false;
    }

    #[On('customer-created')]
    public function customerCreated(int $id): void
    {
        $this->showQuick = false;
        $this->pickCustomer($id);
    }

    public function register(SaleRegistrar $registrar)
    {
        try {
            $sale = $registrar->register(new SaleData(
                items: collect($this->cart)->map(fn ($q, $id) => ['product_id' => (int) $id, 'quantity' => (int) $q])->values()->all(),
                paymentSlug: $this->payment,
                channelSlug: $this->channel,
                installments: $this->installments,
                customerId: $this->customerId,
                date: $this->date !== '' ? Carbon::parse($this->date) : null,
                otherDiscount: Money::parse($this->extraDiscount),
                firstDueDate: $this->firstDue !== '' ? Carbon::parse($this->firstDue) : null,
            ));
        } catch (ValidationException $e) {
            if (isset($e->errors()['customer'])) {
                $this->dispatch('focus-customer');
            }

            throw $e;
        }

        session()->flash('status', 'Venda registrada');

        return $this->redirectRoute('admin.sales.show', $sale);
    }

    public function render(CustomerSearch $search, SaleCalculator $calculator)
    {
        $method = PaymentMethod::where('slug', $this->payment)->first();

        $products = Product::query()->with(['media'])
            ->when(trim($this->productSearch) !== '', fn ($q) => $q->whereRaw(SearchText::sqlNormalized('products.name').' LIKE ?', ['%'.SearchText::normalize($this->productSearch).'%']))
            ->orderBy('name')->limit(self::PRODUCT_LIMIT)->get();

        $lines = Product::query()->with('media')->whereIn('id', array_keys($this->cart))->get()
            ->map(fn (Product $p) => ['product' => $p, 'quantity' => $this->cart[$p->id], 'total' => Money::from($p->price)->times($this->cart[$p->id])]);

        $subtotal = $lines->reduce(fn (Money $carry, array $line) => $carry->add($line['total']), Money::zero());
        $totals = $method ? $calculator->totals($subtotal, $method, Money::parse($this->extraDiscount), StoreSetting::current()->pix_discount_percent) : null;

        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        return view('livewire.admin.sales.create', [
            'products' => $products,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'totals' => $totals,
            'method' => $method,
            'methods' => PaymentMethod::where('is_active', true)->orderBy('id')->get(),
            'channels' => SaleChannel::where('is_active', true)->orderBy('id')->get(),
            'customer' => $customer,
            'matches' => $this->comboOpen && ! $customer ? $search->search($this->customerQuery) : collect(),
            'count' => array_sum($this->cart),
            'maxInstallments' => $method && $method->allows_installments ? $method->max_installments : 1,
            'pixPercent' => StoreSetting::current()->pix_discount_percent,
        ])->layoutData(['current' => 'sales', 'title' => 'Nova venda']);
    }
}
