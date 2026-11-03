<?php

namespace App\Models;

use App\Enums\DraftStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'code', 'public_token', 'subtotal', 'vat_amount', 'total', 'paid_amount', 'payment_status'];

    /** Hoạt động chờ ghi sau khi lưu (vd: tải nháp mới). */
    protected array $pendingActivities = [];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'payment_due_date' => 'date',
            'final_date' => 'date',
            'draft_confirmed_at' => 'datetime',
            'draft_files' => 'array',
            'draft_file_names' => 'array',
            'draft_status' => DraftStatus::class,
            'payment_status' => PaymentStatus::class,
            'vat_rate' => 'integer',
            'subtotal' => 'integer',
            'vat_amount' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            $invoice->code ??= static::nextCode();
            $invoice->public_token ??= static::newToken();
            $invoice->created_by ??= Auth::id();
            $invoice->vat_rate ??= (int) config('hoadon.default_vat_rate', 8);
        });

        static::saving(function (Invoice $invoice) {
            if ($invoice->isDirty('draft_files')) {
                $old = (array) ($invoice->getOriginal('draft_files') ?? []);
                $new = array_values((array) ($invoice->draft_files ?? []));
                $added = array_diff($new, $old);

                if (empty($new)) {
                    $invoice->draft_status = DraftStatus::None;
                    $invoice->clearConfirmation();
                } elseif (! empty($added)) {
                    // Có file nháp mới: khách phải xác nhận lại
                    $invoice->draft_status = DraftStatus::Pending;
                    $invoice->clearConfirmation();
                    $invoice->pendingActivities[] = ['draft_uploaded', count($added).' file nháp mới'];
                }
            }

            if ($invoice->isDirty('final_file') && $invoice->final_file) {
                $invoice->pendingActivities[] = ['final_uploaded', $invoice->final_file_name];
            }

            if ($invoice->exists && $invoice->isDirty('vat_rate')) {
                $invoice->applyTotals();
            }
        });

        static::saved(function (Invoice $invoice) {
            foreach ($invoice->pendingActivities as [$action, $note]) {
                $invoice->log($action, $note);
            }
            $invoice->pendingActivities = [];
        });
    }

    public static function nextCode(): string
    {
        $prefix = 'HD-'.now()->format('ym').'-';
        $n = static::withTrashed()->where('code', 'like', $prefix.'%')->count() + 1;

        do {
            $code = $prefix.str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /* ---------------- Quan hệ ---------------- */

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('paid_at')->orderByDesc('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(InvoiceActivity::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------- Tiền ---------------- */

    public function isVatExempt(): bool
    {
        return $this->vat_rate === -1;
    }

    /** Tính tổng tiền từ hạng mục (không lưu). */
    public function applyTotals(): void
    {
        $subtotal = (int) $this->items()->sum('amount');
        $vat = $this->isVatExempt() ? 0 : (int) round($subtotal * max(0, $this->vat_rate) / 100);

        $this->subtotal = $subtotal;
        $this->vat_amount = $vat;
        $this->total = $subtotal + $vat;
        $this->applyPaymentStatus();
    }

    public function recalculateTotals(): void
    {
        $this->applyTotals();
        $this->saveQuietly();
    }

    public function applyPaymentStatus(): void
    {
        $paid = (int) $this->paid_amount;
        $this->payment_status = match (true) {
            $paid <= 0 => PaymentStatus::Unpaid,
            $this->total > 0 && $paid >= $this->total => PaymentStatus::Paid,
            default => PaymentStatus::Partial,
        };
    }

    public function refreshPayments(): void
    {
        $this->paid_amount = (int) $this->payments()->sum('amount');
        $this->applyPaymentStatus();
        $this->saveQuietly();
    }

    public function remaining(): int
    {
        return max(0, (int) $this->total - (int) $this->paid_amount);
    }

    /* ---------------- Nháp & xác nhận ---------------- */

    /** Danh sách file nháp: [[path, name], ...] */
    public function draftFileList(): array
    {
        $names = (array) ($this->draft_file_names ?? []);

        return collect((array) ($this->draft_files ?? []))
            ->values()
            ->map(fn (string $path) => ['path' => $path, 'name' => $names[$path] ?? basename($path)])
            ->all();
    }

    protected function clearConfirmation(): void
    {
        $this->draft_confirmed_by = null;
        $this->draft_confirmed_at = null;
        $this->draft_feedback = null;
    }

    public function confirmDraft(string $name, bool $byCustomer = false, ?string $ip = null): void
    {
        $this->draft_status = DraftStatus::Confirmed;
        $this->draft_confirmed_by = $name;
        $this->draft_confirmed_at = now();
        $this->draft_feedback = null;
        $this->saveQuietly();
        $this->log('draft_confirmed', null, $name, $byCustomer, $ip);
    }

    public function requestRevision(string $name, string $feedback, bool $byCustomer = false, ?string $ip = null): void
    {
        $this->draft_status = DraftStatus::Revise;
        $this->draft_confirmed_by = $name;
        $this->draft_confirmed_at = now();
        $this->draft_feedback = $feedback;
        $this->saveQuietly();
        $this->log('draft_revise', $feedback, $name, $byCustomer, $ip);
    }

    public function resetDraft(): void
    {
        $this->draft_status = empty($this->draft_files) ? DraftStatus::None : DraftStatus::Pending;
        $this->clearConfirmation();
        $this->saveQuietly();
        $this->log('draft_reset');
    }

    public function regenerateToken(): void
    {
        $this->public_token = static::newToken();
        $this->saveQuietly();
        $this->log('link_regenerated');
    }

    public function publicUrl(): string
    {
        return route('customer.show', $this->public_token);
    }

    public function log(string $action, ?string $note = null, ?string $actor = null, bool $byCustomer = false, ?string $ip = null): void
    {
        $this->activities()->create([
            'action' => $action,
            'note' => $note,
            'actor' => $actor ?? Auth::user()?->name,
            'by_customer' => $byCustomer,
            'ip' => $ip,
        ]);
    }

    /** Bước hiện tại của hồ sơ, dùng cho bảng danh sách. */
    public function stageLabel(): string
    {
        return match (true) {
            $this->payment_status === PaymentStatus::Paid && $this->final_file => 'Hoàn tất',
            (bool) $this->final_file => 'Chờ thanh toán',
            $this->draft_status === DraftStatus::Confirmed => 'Chờ xuất final',
            $this->draft_status === DraftStatus::Revise => 'Nháp cần sửa',
            $this->draft_status === DraftStatus::Pending => 'Chờ khách xác nhận',
            default => 'Đang chuẩn bị',
        };
    }

    public function stageColor(): string
    {
        return match ($this->stageLabel()) {
            'Hoàn tất' => 'success',
            'Nháp cần sửa' => 'danger',
            'Đang chuẩn bị' => 'gray',
            default => 'warning',
        };
    }
}
