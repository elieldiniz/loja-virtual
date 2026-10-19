<?php

namespace App\Livewire\Admin\Customers;

use App\Livewire\Concerns\ReceivesInstallments;
use App\Models\Customer;
use App\Models\Installment;
use App\Models\StoreSetting;
use App\Services\CustomerRegistrar;
use App\Services\CustomerSummary;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    use ReceivesInstallments;

    public ?Customer $customer = null;

    public string $name = '';

    public string $whatsapp = '';

    public string $city = '';

    public string $birthday = '';

    public string $notes = '';

    public bool $showAllSales = false;

    public bool $showAllParcels = false;

    public bool $confirmingDelete = false;

    public function mount(?Customer $customer = null): void
    {
        $this->city = (string) StoreSetting::current()->city;

        if ($customer?->exists) {
            $this->customer = $customer;
            $this->name = $customer->name;
            $this->whatsapp = $customer->whatsapp ? $customer->whatsappFormatted() : '';
            $this->city = (string) $customer->city;
            $this->birthday = (string) $customer->birthdayLabel();
            $this->notes = (string) $customer->notes;
        }
    }

    public function save()
    {
        $saved = app(CustomerRegistrar::class)->save([
            'name' => $this->name,
            'whatsapp' => $this->whatsapp,
            'city' => $this->city,
            'birthday' => $this->birthday,
            'notes' => $this->notes,
        ], $this->customer);

        if ($this->customer === null) {
            return $this->redirectRoute('admin.customers.edit', $saved);
        }

        $this->customer = $saved;
        $this->dispatch('toast', message: 'Cliente salva');
    }

    public function askDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function closeConfirm(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete()
    {
        if ($this->customer === null || $this->customer->sales()->exists()) {
            $this->confirmingDelete = true;

            return null;
        }

        $this->customer->delete();

        return $this->redirectRoute('admin.customers');
    }

    public function render()
    {
        $data = ['isEditing' => $this->customer?->exists === true];

        if ($data['isEditing']) {
            $customer = $this->customer;
            $data['summary'] = CustomerSummary::for($customer);

            $parcels = Installment::query()
                ->whereHas('sale', fn ($q) => $q->where('customer_id', $customer->id)->whereNull('cancelled_at'))
                ->with('sale')
                ->orderByRaw('paid_at is not null')
                ->orderBy('due_date')
                ->get();

            $open = $parcels->whereNull('paid_at')->values();
            $paid = $parcels->whereNotNull('paid_at')->sortByDesc('paid_at')->values();

            $data['parcels'] = $open->concat($this->showAllParcels ? $paid : $paid->take(3));
            $data['hiddenPaid'] = $paid->count() > 3;

            $sales = $customer->sales()->whereNull('cancelled_at')->with(['items', 'paymentMethod', 'channel'])->latest('sold_at');
            $data['purchases'] = $this->showAllSales ? $sales->get() : $sales->limit(8)->get();
            $data['hasPurchases'] = $data['summary']->purchases > 0;
            $data['hasSales'] = $customer->sales()->exists();
        }

        return view('livewire.admin.customers.form', $data)
            ->layoutData(['current' => 'clients', 'title' => $data['isEditing'] ? $this->customer->name : 'Nova cliente']);
    }
}
