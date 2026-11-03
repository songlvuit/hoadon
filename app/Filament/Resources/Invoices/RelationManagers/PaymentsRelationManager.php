<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Thanh toán';

    protected static ?string $modelLabel = 'lần thanh toán';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Invoice $ownerRecord */
        return $ownerRecord->payment_status?->getLabel();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var Invoice $ownerRecord */
        return $ownerRecord->payment_status?->getColor();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('paid_at')
                    ->label('Ngày nhận tiền')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('amount')
                    ->label('Số tiền (₫)')
                    ->required()
                    ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
                    ->stripCharacters('.')
                    ->numeric()
                    ->minValue(1)
                    ->default(fn () => $this->getOwnerRecord()->remaining() ?: null),
                Select::make('method')
                    ->label('Hình thức')
                    ->options(['Chuyển khoản' => 'Chuyển khoản', 'Tiền mặt' => 'Tiền mặt', 'Bù trừ công nợ' => 'Bù trừ công nợ'])
                    ->default('Chuyển khoản')
                    ->selectablePlaceholder(false),
                TextInput::make('reference')
                    ->label('Số UNC / mã giao dịch')
                    ->maxLength(100),
                Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn () => 'Thanh toán — đã thu '.Money::vnd($this->getOwnerRecord()->paid_amount).' / '.Money::vnd($this->getOwnerRecord()->total))
            ->description(fn () => 'Còn phải thu: '.Money::vnd($this->getOwnerRecord()->remaining()))
            ->columns([
                TextColumn::make('paid_at')->label('Ngày')->date('d/m/Y')->sortable(),
                TextColumn::make('amount')->label('Số tiền')->alignEnd()->formatStateUsing(fn ($state) => Money::vnd($state)),
                TextColumn::make('method')->label('Hình thức'),
                TextColumn::make('reference')->label('Số UNC')->placeholder('—')->fontFamily('mono'),
                TextColumn::make('note')->label('Ghi chú')->placeholder('—')->limit(60)->wrap(),
                TextColumn::make('creator.name')->label('Người ghi')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Chưa ghi nhận khoản thanh toán nào')
            ->emptyStateDescription('Bấm “Ghi nhận thanh toán” mỗi lần nhận được tiền.')
            ->headerActions([
                CreateAction::make()
                    ->label('Ghi nhận thanh toán')
                    ->after(fn () => $this->refreshOwner()),
            ])
            ->recordActions([
                EditAction::make()->after(fn () => $this->refreshOwner()),
                DeleteAction::make()->after(fn () => $this->refreshOwner()),
            ]);
    }

    protected function refreshOwner(): void
    {
        $this->getOwnerRecord()->refresh();
        // Cho trang sửa hồ sơ tải lại phần “Tình trạng thanh toán”
        $this->dispatch('refreshInvoice');
    }
}
