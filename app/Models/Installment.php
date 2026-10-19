<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\InstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sale_id', 'number', 'amount', 'due_date', 'paid_at'])]
class Installment extends Model
{
    /** @use HasFactory<InstallmentFactory> */
    use HasFactory;

    public const DUE_SOON_DAYS = 7;

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    /**
     * @param  Builder<Installment>  $query
     * @return Builder<Installment>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }

    /**
     * @param  Builder<Installment>  $query
     * @return Builder<Installment>
     */
    public function scopeOverdue(Builder $query, ?CarbonInterface $today = null): Builder
    {
        return $query->whereNull('paid_at')->whereDate('due_date', '<', ($today ?? today())->toDateString());
    }

    public function status(?CarbonInterface $today = null): InstallmentStatus
    {
        $today = ($today ?? today())->startOfDay();

        if ($this->paid_at !== null) {
            return InstallmentStatus::Paid;
        }

        $due = $this->due_date->copy()->startOfDay();

        if ($due->lt($today)) {
            return InstallmentStatus::Overdue;
        }

        return $today->diffInDays($due) <= self::DUE_SOON_DAYS ? InstallmentStatus::DueSoon : InstallmentStatus::Open;
    }

    public function daysUntilDue(?CarbonInterface $today = null): int
    {
        return (int) ($today ?? today())->startOfDay()->diffInDays($this->due_date->copy()->startOfDay(), false);
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
