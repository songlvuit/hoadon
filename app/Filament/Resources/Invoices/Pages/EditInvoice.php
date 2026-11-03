<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\DraftStatus;
use App\Filament\Resources\Invoices\Actions\CustomerLinkAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

/**
 * @property Invoice $record
 */
class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return $this->record->code.' · '.($this->record->company_name ?: 'Hồ sơ chưa có tên');
    }

    public function getSubheading(): ?string
    {
        return 'Bước hiện tại: '.$this->record->stageLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            CustomerLinkAction::make()->color('primary'),

            Action::make('confirmOnBehalf')
                ->label('Xác nhận thay khách')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->visible(fn () => ! empty($this->record->draft_files) && $this->record->draft_status !== DraftStatus::Confirmed)
                ->modalDescription('Dùng khi khách xác nhận qua email, Zalo hoặc điện thoại. Ghi rõ ai xác nhận để lưu vết.')
                ->schema([
                    TextInput::make('name')
                        ->label('Người xác nhận')
                        ->required()
                        ->maxLength(255)
                        ->default(fn () => $this->record->contact_name),
                    TextInput::make('channel')
                        ->label('Kênh xác nhận')
                        ->placeholder('Email, Zalo, điện thoại…')
                        ->maxLength(100),
                ])
                ->action(function (array $data) {
                    $who = $data['name'].(filled($data['channel'] ?? null) ? ' (qua '.$data['channel'].', ghi bởi '.Auth::user()->name.')' : ' (ghi bởi '.Auth::user()->name.')');
                    $this->record->confirmDraft($who);
                    Notification::make()->title('Đã ghi nhận xác nhận nháp')->success()->send();
                }),

            Action::make('requestRevision')
                ->label('Ghi yêu cầu sửa')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger')
                ->outlined()
                ->visible(fn () => ! empty($this->record->draft_files) && $this->record->draft_status === DraftStatus::Pending)
                ->schema([
                    TextInput::make('name')->label('Người yêu cầu')->required()->maxLength(255),
                    Textarea::make('feedback')->label('Nội dung cần sửa')->required()->rows(4),
                ])
                ->action(function (array $data) {
                    $this->record->requestRevision($data['name'], $data['feedback']);
                    Notification::make()->title('Đã ghi yêu cầu sửa')->warning()->send();
                }),

            ActionGroup::make([
                Action::make('resetDraft')
                    ->label('Đặt lại chờ xác nhận')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn () => in_array($this->record->draft_status, [DraftStatus::Confirmed, DraftStatus::Revise], true))
                    ->requiresConfirmation()
                    ->modalDescription('Xoá kết quả xác nhận hiện tại để khách xác nhận lại.')
                    ->action(function () {
                        $this->record->resetDraft();
                        Notification::make()->title('Đã đặt lại trạng thái chờ xác nhận')->success()->send();
                    }),
                Action::make('regenerateLink')
                    ->label('Tạo link mới (vô hiệu link cũ)')
                    ->icon(Heroicon::OutlinedLink)
                    ->requiresConfirmation()
                    ->modalDescription('Link cũ sẽ không mở được nữa. Dùng khi lỡ gửi nhầm người.')
                    ->action(function () {
                        $this->record->regenerateToken();
                        Notification::make()->title('Đã tạo link mới')->success()->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]),
        ];
    }

    /** Bảng Thanh toán vừa thay đổi: nạp lại số liệu hiển thị ở mục 5. */
    #[On('refreshInvoice')]
    public function refreshInvoice(): void
    {
        $this->record->refresh();
    }

    protected function afterSave(): void
    {
        $this->record->refresh()->recalculateTotals();
    }
}
