<?php

namespace App\Models;

use Database\Factories\ChatShortcutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_setting_id', 'label', 'whatsapp_message', 'opens_gift_section', 'position'])]
class ChatShortcut extends Model
{
    /** @use HasFactory<ChatShortcutFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['opens_gift_section' => 'boolean'];
    }

    /** @return BelongsTo<StoreSetting, $this> */
    public function storeSetting(): BelongsTo
    {
        return $this->belongsTo(StoreSetting::class);
    }
}
