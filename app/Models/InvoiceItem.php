<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $guarded = ['id', 'amount'];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->amount = (int) round((float) $item->quantity * (int) $item->unit_price);
        });

        static::saved(fn (InvoiceItem $item) => $item->invoice?->recalculateTotals());
        static::deleted(fn (InvoiceItem $item) => $item->invoice?->recalculateTotals());
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
