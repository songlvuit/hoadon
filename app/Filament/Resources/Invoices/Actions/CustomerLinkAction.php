<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/** Hiện link riêng để gửi khách xem và xác nhận hoá đơn nháp. */
class CustomerLinkAction
{
    public static function make(): Action
    {
        return Action::make('customerLink')
            ->label('Link khách xác nhận')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->modalHeading('Link gửi khách xác nhận nháp')
            ->modalDescription('Khách mở link này để xem thông tin, hạng mục, tải file nháp và bấm xác nhận hoặc yêu cầu sửa — không cần tài khoản. Ai có link đều mở được, nên chỉ gửi đúng người.')
            ->fillForm(fn (Invoice $record) => ['url' => $record->publicUrl()])
            ->schema([
                TextInput::make('url')
                    ->label('Đường link')
                    ->readOnly()
                    ->copyable(copyMessage: 'Đã chép link'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng');
    }
}
