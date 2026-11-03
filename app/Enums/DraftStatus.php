<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DraftStatus: string implements HasColor, HasLabel
{
    case None = 'none';
    case Pending = 'pending';
    case Revise = 'revise';
    case Confirmed = 'confirmed';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Chưa có nháp',
            self::Pending => 'Chờ khách xác nhận',
            self::Revise => 'Khách yêu cầu sửa',
            self::Confirmed => 'Đã xác nhận',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Pending => 'warning',
            self::Revise => 'danger',
            self::Confirmed => 'success',
        };
    }
}
