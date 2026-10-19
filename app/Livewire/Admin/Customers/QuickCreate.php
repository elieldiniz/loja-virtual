<?php

namespace App\Livewire\Admin\Customers;

use App\Models\StoreSetting;
use App\Services\CustomerRegistrar;
use Livewire\Component;

/** Cadastro rápido de cliente dentro da venda: salva e já seleciona a cliente. */
class QuickCreate extends Component
{
    public string $name = '';

    public string $whatsapp = '';

    public string $city = '';

    public string $birthday = '';

    public string $notes = '';

    public function mount(string $initialName = ''): void
    {
        if ($initialName !== '' && ! preg_match('/\d/', $initialName)) {
            $this->name = trim($initialName);
        } elseif ($initialName !== '') {
            $this->whatsapp = $initialName;
        }

        $this->city = (string) StoreSetting::current()->city;
    }

    public function save(): void
    {
        $customer = app(CustomerRegistrar::class)->save([
            'name' => $this->name,
            'whatsapp' => $this->whatsapp,
            'city' => $this->city,
            'birthday' => $this->birthday,
            'notes' => $this->notes,
        ]);

        $this->dispatch('customer-created', id: $customer->id);
    }

    public function close(): void
    {
        $this->dispatch('quick-closed');
    }

    public function render()
    {
        return view('livewire.admin.customers.quick-create');
    }
}
