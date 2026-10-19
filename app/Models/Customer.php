<?php

namespace App\Models;

use App\Support\Phone;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['name', 'whatsapp', 'city', 'birth_day', 'birth_month', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_day' => 'integer',
            'birth_month' => 'integer',
        ];
    }

    /**
     * Acrescenta os agregados da ficha: total comprado, nº de compras, saldo em aberto,
     * parcelas vencidas e data da última compra. Vendas canceladas não contam.
     *
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    public function scopeWithSummary(Builder $query): Builder
    {
        return $query
            ->withCount(['sales as purchases_count' => fn ($q) => $q->whereNull('cancelled_at')])
            ->withSum(['sales as total_spent' => fn ($q) => $q->whereNull('cancelled_at')], 'total')
            ->withMax(['sales as last_purchase_at' => fn ($q) => $q->whereNull('cancelled_at')], 'sold_at')
            ->withMin(['sales as first_purchase_at' => fn ($q) => $q->whereNull('cancelled_at')], 'sold_at')
            ->withSum(['installments as open_balance' => fn ($q) => $q->whereNull('installments.paid_at')->whereNull('sales.cancelled_at')], 'amount')
            ->withCount(['installments as overdue_count' => fn ($q) => $q->whereNull('installments.paid_at')->whereNull('sales.cancelled_at')->whereDate('installments.due_date', '<', today()->toDateString())]);
    }

    public function whatsappFormatted(): string
    {
        return Phone::format($this->whatsapp);
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    /** "21/06" para exibir; null quando a cliente não informou. */
    public function birthdayLabel(): ?string
    {
        return $this->birth_day && $this->birth_month
            ? sprintf('%02d/%02d', $this->birth_day, $this->birth_month)
            : null;
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** @return HasManyThrough<Installment, Sale, $this> */
    public function installments(): HasManyThrough
    {
        return $this->hasManyThrough(Installment::class, Sale::class);
    }
}
