<?php

namespace App\Filament\Widgets;

use App\Enums\DraftStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $url = fn (array $filters) => InvoiceResource::getUrl('index', ['filters' => $filters]);

        $pending = Invoice::where('draft_status', DraftStatus::Pending)->count();
        $revise = Invoice::where('draft_status', DraftStatus::Revise)->count();
        $waitingFinal = Invoice::where('draft_status', DraftStatus::Confirmed)->whereNull('final_file')->count();

        $open = Invoice::where('payment_status', '!=', PaymentStatus::Paid)->whereNotNull('final_file');
        $receivable = (int) (clone $open)->sum('total') - (int) (clone $open)->sum('paid_amount');
        $overdue = (clone $open)->whereDate('payment_due_date', '<', now())->count();

        return [
            Stat::make('Chờ khách xác nhận nháp', $pending)
                ->description($revise ? $revise.' hồ sơ khách yêu cầu sửa' : 'Không có yêu cầu sửa')
                ->descriptionColor($revise ? 'danger' : 'gray')
                ->url($url(['draft_status' => ['value' => DraftStatus::Pending->value]])),
            Stat::make('Chờ xuất hoá đơn final', $waitingFinal)
                ->description('Nháp đã xác nhận, chưa có final')
                ->color('warning')
                ->url($url(['draft_status' => ['value' => DraftStatus::Confirmed->value], 'has_final' => ['value' => 0]])),
            Stat::make('Còn phải thu', Money::vnd($receivable))
                ->description($overdue ? $overdue.' hồ sơ quá hạn thanh toán' : 'Không có hồ sơ quá hạn')
                ->descriptionColor($overdue ? 'danger' : 'success')
                ->url($url(['has_final' => ['value' => 1]])),
        ];
    }
}
