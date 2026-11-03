<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'người dùng';

    protected static ?string $pluralModelLabel = 'Người dùng';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
            TextInput::make('email')->label('Email đăng nhập')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')
                ->label('Mật khẩu')
                ->password()
                ->revealable()
                ->minLength(8)
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi mật khẩu.' : null),
            Toggle::make('is_active')->label('Được phép đăng nhập')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
                TextColumn::make('created_at')->label('Ngày tạo')->date('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (User $record) => $record->id === Auth::id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
