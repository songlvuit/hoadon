<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\InvoiceActivity;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Lịch sử';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('H:i d/m/Y'),
                TextColumn::make('action')
                    ->label('Hoạt động')
                    ->formatStateUsing(fn ($state, InvoiceActivity $record) => $record->label())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft_confirmed' => 'success',
                        'draft_revise' => 'danger',
                        'final_uploaded' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('actor')->label('Người thực hiện')->placeholder('—'),
                IconColumn::make('by_customer')->label('Khách')->boolean(),
                TextColumn::make('note')->label('Ghi chú')->placeholder('—')->wrap()->limit(200),
            ])
            ->emptyStateHeading('Chưa có hoạt động');
    }
}
