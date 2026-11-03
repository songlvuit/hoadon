<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceActivity extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'by_customer' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public const LABELS = [
        'draft_uploaded' => 'Tải hoá đơn nháp',
        'draft_confirmed' => 'Xác nhận nháp',
        'draft_revise' => 'Yêu cầu sửa nháp',
        'draft_reset' => 'Đặt lại chờ xác nhận',
        'draft_viewed' => 'Khách mở link xác nhận',
        'final_uploaded' => 'Tải hoá đơn final',
        'link_regenerated' => 'Tạo link xác nhận mới',
    ];

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
