<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Payment $p) => $p->created_by ??= Auth::id());
        static::saved(fn (Payment $p) => $p->invoice?->refreshPayments());
        static::deleted(fn (Payment $p) => $p->invoice?->refreshPayments());
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
