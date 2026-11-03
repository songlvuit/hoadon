<?php

namespace App\Support;

class Money
{
    /** 12500000 -> "12.500.000 ₫" */
    public static function vnd(int|float|string|null $amount, bool $symbol = true): string
    {
        $s = number_format((float) $amount, 0, ',', '.');

        return $symbol ? $s.' ₫' : $s;
    }

    /** Số lượng: bỏ phần thập phân thừa, dùng dấu phẩy thập phân. */
    public static function qty(int|float|string|null $qty): string
    {
        $q = (float) $qty;

        return floor($q) == $q ? number_format($q, 0, ',', '.') : rtrim(rtrim(number_format($q, 2, ',', '.'), '0'), ',');
    }

    /** Chuỗi người dùng nhập ("12.500.000", "12500000 đ") -> số nguyên. */
    public static function parse(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $digits = preg_replace('/[^\d,\-]/', '', (string) $value);

        return (float) str_replace(',', '.', $digits);
    }

    /** Số lượng người dùng nhập ("1.5" hoặc "1,5") -> số thực. */
    public static function parseQty(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '.', preg_replace('/[^\d,.\-]/', '', (string) $value));
    }

    /** Đọc số tiền bằng chữ tiếng Việt: 12500000 -> "Mười hai triệu năm trăm nghìn đồng". */
    public static function words(int|float|string|null $amount): string
    {
        $n = (int) round((float) $amount);
        if ($n === 0) {
            return 'Không đồng';
        }

        $negative = $n < 0;
        $n = abs($n);
        $units = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];
        $groups = [];
        while ($n > 0) {
            $groups[] = $n % 1000;
            $n = intdiv($n, 1000);
        }

        $parts = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            if ($groups[$i] === 0) {
                continue;
            }
            $parts[] = trim(self::readThree($groups[$i], $i < count($groups) - 1).' '.$units[$i]);
        }

        $text = implode(' ', $parts);
        $text = mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);

        return ($negative ? 'Âm ' : '').$text.' đồng';
    }

    private static function readThree(int $x, bool $full): string
    {
        $d = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $h = intdiv($x, 100);
        $t = intdiv($x % 100, 10);
        $u = $x % 10;
        $r = [];

        if ($h || $full) {
            $r[] = $d[$h].' trăm';
        }
        if ($t > 1) {
            $r[] = $d[$t].' mươi';
            $r[] = match ($u) { 0 => null, 1 => 'mốt', 4 => 'tư', 5 => 'lăm', default => $d[$u] };
        } elseif ($t === 1) {
            $r[] = 'mười';
            $r[] = match ($u) { 0 => null, 5 => 'lăm', default => $d[$u] };
        } elseif ($u) {
            if ($h || $full) {
                $r[] = 'lẻ';
            }
            $r[] = $d[$u];
        }

        return implode(' ', array_filter($r));
    }
}
