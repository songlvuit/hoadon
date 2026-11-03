<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa thanh toán',
            self::Partial => 'Thanh toán một phần',
            self::Paid => 'Đã thanh toán',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'gray',
            self::Partial => 'warning',
            self::Paid => 'success',
        };
    }
}
