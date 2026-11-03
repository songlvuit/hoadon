<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_format_vnd(): void
    {
        $this->assertSame('12.500.000 ₫', Money::vnd(12500000));
        $this->assertSame('0', Money::vnd(0, false));
    }

    public function test_parse_money_input(): void
    {
        $this->assertSame(12500000.0, Money::parse('12.500.000'));
        $this->assertSame(1.5, Money::parseQty('1,5'));
        $this->assertSame(1.5, Money::parseQty('1.5'));
    }

    public function test_read_in_words(): void
    {
        $this->assertSame('Mười hai triệu năm trăm nghìn đồng', Money::words(12500000));
        $this->assertSame('Một triệu không trăm lẻ năm nghìn đồng', Money::words(1005000));
        $this->assertSame('Hai mươi mốt triệu đồng', Money::words(21000000));
        $this->assertSame('Một tỷ hai trăm ba mươi tư triệu năm trăm sáu mươi bảy nghìn tám trăm chín mươi đồng', Money::words(1234567890));
        $this->assertSame('Không đồng', Money::words(0));
    }
}
