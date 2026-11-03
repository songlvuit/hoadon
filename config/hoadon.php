<?php

return [
    // Thông tin bên bán, hiển thị trên trang khách hàng xác nhận nháp
    'company_name' => env('COMPANY_NAME', env('APP_NAME', 'Xuất hoá đơn')),
    'company_tax_code' => env('COMPANY_TAX_CODE'),
    'company_phone' => env('COMPANY_PHONE'),

    // Dung lượng tối đa mỗi file nháp / final (KB)
    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 20480),

    // Thuế suất GTGT cho phép; -1 = không chịu thuế (KCT)
    'vat_rates' => [0 => '0%', 5 => '5%', 8 => '8%', 10 => '10%', -1 => 'KCT'],
    'default_vat_rate' => (int) env('DEFAULT_VAT_RATE', 8),
];
