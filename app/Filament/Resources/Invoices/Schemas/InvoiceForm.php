<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\DraftStatus;
use App\Models\Invoice;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;

class InvoiceForm
{
    public const FILE_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'application/xml',
        'text/xml',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::infoSection(),
                self::itemsSection(),
                self::draftSection(),
                self::finalSection(),
                self::paymentSection(),
            ]);
    }

    /* 1. Thông tin cần xuất hoá đơn */
    protected static function infoSection(): Section
    {
        return Section::make('1. Thông tin cần xuất hoá đơn')
            ->description('Thông tin bên mua sẽ ghi trên hoá đơn.')
            ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
            ->schema([
                TextInput::make('company_name')
                    ->label('Tên đơn vị mua hàng')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['md' => 2, 'xl' => 2]),
                TextInput::make('tax_code')
                    ->label('Mã số thuế')
                    ->required()
                    ->maxLength(20)
                    ->regex('/^\d{10}(-\d{3})?$|^\d{12}$/')
                    ->validationMessages(['regex' => 'MST gồm 10 số, 10 số kèm -xxx, hoặc 12 số (CCCD).']),
                TextInput::make('quote_ref')
                    ->label('Số báo giá / hợp đồng')
                    ->maxLength(100),
                TextInput::make('address')
                    ->label('Địa chỉ')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->label('Email nhận hoá đơn')
                    ->email()
                    ->maxLength(255),
                TextInput::make('contact_name')
                    ->label('Người liên hệ')
                    ->maxLength(255),
                TextInput::make('contact_phone')
                    ->label('Số điện thoại')
                    ->tel()
                    ->maxLength(30),
                Select::make('payment_method')
                    ->label('Hình thức thanh toán')
                    ->options(['Chuyển khoản' => 'Chuyển khoản', 'Tiền mặt' => 'Tiền mặt', 'TM/CK' => 'TM/CK'])
                    ->default('Chuyển khoản')
                    ->selectablePlaceholder(false),
                DatePicker::make('requested_date')
                    ->label('Ngày cần xuất')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->columnSpan(['md' => 2, 'xl' => 3]),
            ]);
    }

    /* 2. Hạng mục xuất theo báo giá */
    protected static function itemsSection(): Section
    {
        return Section::make('2. Hạng mục xuất theo báo giá')
            ->schema([
                Repeater::make('items')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('sort')
                    ->defaultItems(1)
                    ->minItems(1)
                    ->addActionLabel('Thêm hạng mục')
                    ->compact()
                    ->table([
                        TableColumn::make('Nội dung')->markAsRequired(),
                        TableColumn::make('Số lượng')->width('110px')->alignment(Alignment::End),
                        TableColumn::make('Đơn vị')->width('120px'),
                        TableColumn::make('Đơn giá (₫)')->width('170px')->alignment(Alignment::End),
                        TableColumn::make('Thành tiền (₫)')->width('170px')->alignment(Alignment::End),
                    ])
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(1000),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true),
                        TextInput::make('unit')
                            ->maxLength(50),
                        TextInput::make('unit_price')
                            ->mask(RawJs::make("\$money(\$input, ',', '.', 0)"))
                            ->stripCharacters('.')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),
                        TextEntry::make('line_amount')
                            ->hiddenLabel()
                            ->alignEnd()
                            ->state(fn (Get $get): string => Money::vnd(
                                Money::parseQty($get('quantity')) * Money::parse($get('unit_price')),
                                false
                            )),
                    ]),

                Grid::make(['default' => 1, 'md' => 3])
                    ->schema([
                        Select::make('vat_rate')
                            ->label('Thuế suất GTGT')
                            ->options(config('hoadon.vat_rates'))
                            ->default(config('hoadon.default_vat_rate'))
                            ->selectablePlaceholder(false)
                            ->live(),
                        TextEntry::make('live_totals')
                            ->label('Tổng cộng')
                            ->columnSpan(['md' => 2])
                            ->state(fn (Get $get) => self::totalsHtml($get('items') ?? [], (int) $get('vat_rate'))),
                    ]),
            ]);
    }

    public static function totalsHtml(array $items, int $vatRate): HtmlString
    {
        $sub = collect($items)->sum(fn ($i) => Money::parseQty($i['quantity'] ?? 0) * Money::parse($i['unit_price'] ?? 0));
        $sub = (int) round($sub);
        $vat = $vatRate === -1 ? 0 : (int) round($sub * $vatRate / 100);
        $total = $sub + $vat;
        $vatLabel = $vatRate === -1 ? 'KCT' : $vatRate.'%';

        return new HtmlString(
            '<div style="display:grid;grid-template-columns:auto auto;gap:2px 24px;justify-content:end;font-variant-numeric:tabular-nums">'
            .'<span style="opacity:.7">Cộng tiền hàng</span><span style="text-align:right">'.e(Money::vnd($sub)).'</span>'
            .'<span style="opacity:.7">Thuế GTGT ('.e($vatLabel).')</span><span style="text-align:right">'.e(Money::vnd($vat)).'</span>'
            .'<strong>Tổng thanh toán</strong><strong style="text-align:right">'.e(Money::vnd($total)).'</strong>'
            .'</div><div style="text-align:right;font-style:italic;opacity:.7;margin-top:4px">Bằng chữ: '.e(Money::words($total)).'</div>'
        );
    }

    /* 3. Hoá đơn nháp + xác nhận */
    protected static function draftSection(): Section
    {
        return Section::make('3. Hoá đơn nháp & xác nhận')
            ->description('Tải nháp lên rồi gửi link cho khách xác nhận. Tải nháp mới sẽ đưa trạng thái về “Chờ khách xác nhận”.')
            ->schema([
                TextEntry::make('draft_status')
                    ->label('Tình trạng xác nhận')
                    ->badge()
                    ->state(fn (?Invoice $record) => $record?->draft_status ?? DraftStatus::None)
                    ->hiddenOn('create'),
                TextEntry::make('draft_confirm_info')
                    ->label('Chi tiết')
                    ->hiddenOn('create')
                    ->visible(fn (?Invoice $record) => $record?->draft_confirmed_at !== null)
                    ->state(function (?Invoice $record) {
                        if (! $record?->draft_confirmed_at) {
                            return null;
                        }
                        $line = ($record->draft_confirmed_by ?: 'Không rõ').' · '.$record->draft_confirmed_at->format('H:i d/m/Y');

                        return $record->draft_status === DraftStatus::Revise
                            ? $line.' — Yêu cầu sửa: '.$record->draft_feedback
                            : $line;
                    }),
                FileUpload::make('draft_files')
                    ->label('File hoá đơn nháp')
                    ->multiple()
                    ->reorderable()
                    ->disk('local')
                    ->directory('invoices/drafts')
                    ->visibility('private')
                    ->storeFileNamesIn('draft_file_names')
                    ->acceptedFileTypes(self::FILE_TYPES)
                    ->maxSize((int) config('hoadon.max_upload_kb'))
                    ->maxFiles(10)
                    ->previewable(false)
                    ->downloadable()
                    ->openable()
                    ->helperText('PDF, ảnh hoặc XML, tối đa 20 MB mỗi file.'),
            ]);
    }

    /* 4. Hoá đơn final */
    protected static function finalSection(): Section
    {
        return Section::make('4. Hoá đơn final')
            ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
            ->schema([
                FileUpload::make('final_file')
                    ->label('File hoá đơn chính thức')
                    ->disk('local')
                    ->directory('invoices/finals')
                    ->visibility('private')
                    ->storeFileNamesIn('final_file_name')
                    ->acceptedFileTypes(self::FILE_TYPES)
                    ->maxSize((int) config('hoadon.max_upload_kb'))
                    ->previewable(false)
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull()
                    ->helperText(fn (?Invoice $record) => $record && $record->draft_status !== DraftStatus::Confirmed
                        ? 'Lưu ý: nháp chưa được khách xác nhận.'
                        : null),
                TextInput::make('final_symbol')
                    ->label('Ký hiệu hoá đơn')
                    ->placeholder('1C26TAA')
                    ->maxLength(20),
                TextInput::make('final_number')
                    ->label('Số hoá đơn')
                    ->placeholder('00000123')
                    ->maxLength(20),
                DatePicker::make('final_date')
                    ->label('Ngày hoá đơn')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('final_lookup_code')
                    ->label('Mã tra cứu')
                    ->maxLength(100),
            ]);
    }

    /* 5. Tình trạng thanh toán (chi tiết các lần thu ở bảng “Thanh toán” bên dưới) */
    protected static function paymentSection(): Section
    {
        return Section::make('5. Tình trạng thanh toán')
            ->description('Ghi từng lần nhận tiền ở bảng “Thanh toán” cuối trang; trạng thái tự cập nhật.')
            ->columns(['default' => 1, 'md' => 4])
            ->schema([
                TextEntry::make('payment_status')
                    ->label('Trạng thái')
                    ->badge()
                    ->hiddenOn('create'),
                TextEntry::make('paid_amount')
                    ->label('Đã thu')
                    ->formatStateUsing(fn ($state) => Money::vnd($state))
                    ->hiddenOn('create'),
                TextEntry::make('remaining')
                    ->label('Còn phải thu')
                    ->state(fn (?Invoice $record) => Money::vnd($record?->remaining() ?? 0))
                    ->weight('bold')
                    ->hiddenOn('create'),
                DatePicker::make('payment_due_date')
                    ->label('Hạn thanh toán')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
            ]);
    }
}
