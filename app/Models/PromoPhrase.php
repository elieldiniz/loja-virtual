<?php

namespace App\Models;

use Database\Factories\PromoPhraseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_setting_id', 'phrase', 'position'])]
class PromoPhrase extends Model
{
    /** @use HasFactory<PromoPhraseFactory> */
    use HasFactory;

    /** @return BelongsTo<StoreSetting, $this> */
    public function storeSetting(): BelongsTo
    {
        return $this->belongsTo(StoreSetting::class);
    }
}
