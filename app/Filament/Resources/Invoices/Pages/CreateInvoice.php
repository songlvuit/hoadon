<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        // Hạng mục được lưu sau hồ sơ: tính lại tổng lần cuối cho chắc
        $this->getRecord()->refresh()->recalculateTotals();
    }
}
