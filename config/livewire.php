<?php

// Chỉ ghi đè phần tải file tạm để cho phép file hoá đơn tới 20 MB (mặc định Livewire là 12 MB).
return [
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:20480'],
        'directory' => null,
        'middleware' => 'throttle:60,1',
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
