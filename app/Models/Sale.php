<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['number', 'customer_id', 'payment_method_id', 'sale_channel_id', 'installments_count', 'first_due_date', 'subtotal', 'pix_discount', 'other_discount', 'total', 'sold_at', 'cancelled_at', 'cancel_reason'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Venda não se edita: só o cancelamento (e a reativação) altera o registro.
        static::updating(function (Sale $sale) {
            $allowed = ['cancelled_at', 'cancel_reason', 'updated_at'];

            if (array_diff(array_keys($sale->getDirty()), $allowed) !== []) {
                throw new LogicException('Vendas não podem ser editadas. Cancele e lance de novo.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'installments_count' => 'integer',
            'first_due_date' => 'date',
            'subtotal' => 'decimal:2',
            'pix_discount' => 'decimal:2',
            'other_discount' => 'decimal:2',
            'total' => 'decimal:2',
            'sold_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    /**
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return BelongsTo<SaleChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class, 'sale_channel_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<Installment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }
}
