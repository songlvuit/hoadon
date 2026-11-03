@php
    use App\Enums\DraftStatus;
    use App\Support\Money;
    $status = $invoice->draft_status;
    $vatLabel = $invoice->isVatExempt() ? 'KCT' : $invoice->vat_rate.'%';
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Xác nhận hoá đơn {{ $invoice->code }} · {{ config('hoadon.company_name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap">
    <style>
        :root{--paper:#f3f5f4;--sheet:#fff;--ink:#17221e;--muted:#5b6965;--line:#dce3e0;--soft:#eef3f1;--accent:#0f5f57;--accent-soft:#e0efec;--seal:#b3261e;--seal-soft:#fbe9e7;--warn:#8a5a00;--warn-soft:#fbf0d9;--ok:#1d7a3e;--ok-soft:#e3f3e8}
        *{box-sizing:border-box}
        body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.55 "Be Vietnam Pro",system-ui,"Segoe UI",Roboto,sans-serif}
        .wrap{max-width:900px;margin:0 auto;padding:24px max(16px,env(safe-area-inset-right)) 56px max(16px,env(safe-area-inset-left))}
        .num{font-family:"JetBrains Mono",ui-monospace,Menlo,Consolas,monospace;font-variant-numeric:tabular-nums}
        .sheet{background:var(--sheet);border:1px solid var(--line);border-radius:14px;overflow:hidden}
        .head{padding:22px 24px;display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--line)}
        .seller{font-size:13px;color:var(--muted)}
        .seller b{color:var(--ink);font-size:15px;display:block}
        h1{margin:6px 0 0;font-size:22px;letter-spacing:-.01em}
        .code{color:var(--muted);font-size:13px}
        .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:600}
        .b-pending{background:var(--warn-soft);color:var(--warn)}.b-revise{background:var(--seal-soft);color:var(--seal)}.b-confirmed{background:var(--ok-soft);color:var(--ok)}.b-none{background:var(--soft);color:var(--muted)}
        section{padding:20px 24px;border-bottom:1px solid var(--line)}
        section:last-child{border-bottom:0}
        h2{margin:0 0 12px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:600}
        dl{display:grid;grid-template-columns:170px 1fr;gap:6px 16px;margin:0}
        dt{color:var(--muted)}dd{margin:0;min-width:0;overflow-wrap:anywhere}
        @media (max-width:560px){dl{grid-template-columns:1fr}dt{margin-top:6px}}
        .tbl{overflow-x:auto;border:1px solid var(--line);border-radius:10px}
        table{border-collapse:collapse;width:100%;min-width:600px}
        th,td{padding:9px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}
        th{background:var(--soft);font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600}
        tr:last-child td{border-bottom:0}
        .r{text-align:right}.c{text-align:center}
        .totals{display:grid;grid-template-columns:auto auto;gap:4px 28px;justify-content:end;margin-top:14px}
        .totals span:nth-child(odd){color:var(--muted);text-align:right}
        .totals .g{font-weight:700;font-size:17px;color:var(--ink)}
        .words{text-align:right;font-style:italic;color:var(--muted);margin-top:4px}
        .files{display:grid;gap:8px}
        .file{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:var(--paper);flex-wrap:wrap}
        .file span{min-width:0;overflow-wrap:anywhere;font-weight:500}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--line);background:var(--sheet);color:var(--ink);border-radius:9px;padding:9px 16px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;min-height:42px}
        .btn:hover{border-color:var(--accent)}
        .btn-primary{background:var(--accent);border-color:var(--accent);color:#fff}
        .btn-primary:hover{filter:brightness(1.08)}
        .btn-danger{color:var(--seal);border-color:#efc4c0}
        :focus-visible{outline:2px solid var(--accent);outline-offset:2px}
        .respond{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media (max-width:700px){.respond{grid-template-columns:1fr}}
        .card{border:1px solid var(--line);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:12px}
        .card h3{margin:0;font-size:16px}
        label{display:flex;flex-direction:column;gap:4px;font-size:13px;color:var(--muted);font-weight:500}
        input[type=text],textarea{font:inherit;color:var(--ink);border:1px solid var(--line);border-radius:9px;padding:9px 11px;background:var(--paper);width:100%}
        textarea{min-height:96px;resize:vertical}
        .check{flex-direction:row;align-items:flex-start;gap:8px;color:var(--ink)}
        .check input{margin-top:4px;width:18px;height:18px;accent-color:var(--accent)}
        .alert{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:500}
        .a-ok{background:var(--ok-soft);color:var(--ok)}.a-err{background:var(--seal-soft);color:var(--seal)}
        .err{color:var(--seal);font-size:13px}
        .stamp{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
        .seal{border:3px double var(--seal);color:var(--seal);padding:8px 18px;border-radius:8px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;transform:rotate(-4deg);font-size:15px}
        .muted{color:var(--muted)}
        footer{text-align:center;color:var(--muted);font-size:12px;margin-top:20px}
    </style>
</head>
<body>
<div class="wrap">
    @if (session('success'))
        <div class="alert a-ok" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert a-err" role="alert">{{ session('error') }}</div>
    @endif

    <article class="sheet">
        <header class="head">
            <div>
                <div class="seller">
                    <b>{{ config('hoadon.company_name') }}</b>
                    @if (config('hoadon.company_tax_code')) MST {{ config('hoadon.company_tax_code') }} @endif
                    @if (config('hoadon.company_phone')) · {{ config('hoadon.company_phone') }} @endif
                </div>
                <h1>Xác nhận thông tin xuất hoá đơn</h1>
                <div class="code num">{{ $invoice->code }}@if ($invoice->quote_ref) · Báo giá {{ $invoice->quote_ref }}@endif</div>
            </div>
            <span class="badge b-{{ $status->value }}">{{ $status->getLabel() }}</span>
        </header>

        <section>
            <h2>1. Thông tin bên mua hàng</h2>
            <dl>
                <dt>Tên đơn vị</dt><dd><strong>{{ $invoice->company_name }}</strong></dd>
                <dt>Mã số thuế</dt><dd class="num">{{ $invoice->tax_code }}</dd>
                <dt>Địa chỉ</dt><dd>{{ $invoice->address }}</dd>
                @if ($invoice->email)<dt>Email nhận hoá đơn</dt><dd>{{ $invoice->email }}</dd>@endif
                @if ($invoice->contact_name)<dt>Người liên hệ</dt><dd>{{ $invoice->contact_name }}@if ($invoice->contact_phone) · {{ $invoice->contact_phone }}@endif</dd>@endif
                <dt>Hình thức thanh toán</dt><dd>{{ $invoice->payment_method }}</dd>
            </dl>
        </section>

        <section>
            <h2>2. Hạng mục xuất hoá đơn</h2>
            <div class="tbl">
                <table>
                    <thead><tr><th class="c">STT</th><th>Nội dung</th><th class="r">Số lượng</th><th>Đơn vị</th><th class="r">Đơn giá</th><th class="r">Thành tiền</th></tr></thead>
                    <tbody>
                    @forelse ($invoice->items as $i => $item)
                        <tr>
                            <td class="c num">{{ $i + 1 }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="r num">{{ Money::qty($item->quantity) }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="r num">{{ Money::vnd($item->unit_price, false) }}</td>
                            <td class="r num">{{ Money::vnd($item->amount, false) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Chưa có hạng mục.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="totals num">
                <span>Cộng tiền hàng</span><span>{{ Money::vnd($invoice->subtotal) }}</span>
                <span>Thuế GTGT ({{ $vatLabel }})</span><span>{{ Money::vnd($invoice->vat_amount) }}</span>
                <span class="g">Tổng thanh toán</span><span class="g">{{ Money::vnd($invoice->total) }}</span>
            </div>
            <div class="words">Bằng chữ: {{ Money::words($invoice->total) }}</div>
        </section>

        <section>
            <h2>3. Hoá đơn nháp</h2>
            @if (count($files))
                <div class="files">
                    @foreach ($files as $idx => $f)
                        <div class="file">
                            <span>📄 {{ $f['name'] }}</span>
                            <a class="btn" href="{{ route('customer.draft-file', [$invoice->public_token, $idx]) }}">Tải xuống</a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="muted" style="margin:0">Bên bán chưa tải hoá đơn nháp. Anh/chị vui lòng quay lại link này sau.</p>
            @endif
        </section>

        @if ($status === DraftStatus::Confirmed)
            <section>
                <div class="stamp">
                    <span class="seal">Đã xác nhận</span>
                    <div>
                        <strong>{{ $invoice->draft_confirmed_by }}</strong>
                        <div class="muted">lúc {{ $invoice->draft_confirmed_at?->format('H:i, d/m/Y') }}</div>
                    </div>
                </div>
            </section>
        @elseif ($status === DraftStatus::Revise)
            <section>
                <h2>Yêu cầu sửa đã gửi</h2>
                <p style="margin:0 0 6px"><strong>{{ $invoice->draft_confirmed_by }}</strong> <span class="muted">· {{ $invoice->draft_confirmed_at?->format('H:i, d/m/Y') }}</span></p>
                <p style="margin:0;white-space:pre-line">{{ $invoice->draft_feedback }}</p>
                <p class="muted" style="margin:10px 0 0">Bên bán sẽ cập nhật nháp mới. Khi có nháp mới, trang này sẽ mở lại phần xác nhận.</p>
            </section>
        @elseif ($canRespond)
            <section>
                <h2>Phản hồi của anh/chị</h2>
                <div class="respond">
                    <form class="card" method="POST" action="{{ route('customer.confirm', $invoice->public_token) }}">
                        @csrf
                        <input type="hidden" name="form" value="confirm">
                        <h3>Thông tin đã đúng</h3>
                        <label>Họ tên người xác nhận
                            <input type="text" name="name" id="confirm-name" value="{{ old('name', $invoice->contact_name) }}" required maxlength="255">
                        </label>
                        <label class="check"><input type="checkbox" name="agree" value="1" required> Tôi đã kiểm tra thông tin bên mua, hạng mục và số tiền trên hoá đơn nháp.</label>
                        @if ($errors->any() && old('form') === 'confirm')
                            @foreach ($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
                        @endif
                        <button class="btn btn-primary" type="submit">Xác nhận nháp đúng</button>
                    </form>
                    <form class="card" method="POST" action="{{ route('customer.revise', $invoice->public_token) }}">
                        @csrf
                        <input type="hidden" name="form" value="revise">
                        <h3>Cần chỉnh sửa</h3>
                        <label>Họ tên
                            <input type="text" name="name" id="revise-name" value="{{ old('name', $invoice->contact_name) }}" required maxlength="255">
                        </label>
                        <label>Nội dung cần sửa
                            <textarea name="feedback" id="revise-feedback" required maxlength="5000" placeholder="VD: Sửa địa chỉ thành …; đơn giá dòng 2 là …">{{ old('feedback') }}</textarea>
                        </label>
                        @if ($errors->any() && old('form') === 'revise')
                            @foreach ($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
                        @endif
                        <button class="btn btn-danger" type="submit">Gửi yêu cầu sửa</button>
                    </form>
                </div>
            </section>
        @endif

        @if ($invoice->final_file)
            <section>
                <h2>4. Hoá đơn chính thức</h2>
                <div class="file">
                    <span>
                        🧾 {{ $invoice->final_file_name ?: 'Hoá đơn' }}
                        @if ($invoice->final_number)<span class="muted num"> · Ký hiệu {{ $invoice->final_symbol }} số {{ $invoice->final_number }}@if ($invoice->final_date) ngày {{ $invoice->final_date->format('d/m/Y') }}@endif</span>@endif
                    </span>
                    <a class="btn btn-primary" href="{{ route('customer.final-file', $invoice->public_token) }}">Tải hoá đơn</a>
                </div>
                @if ($invoice->final_lookup_code)<p class="muted" style="margin:10px 0 0">Mã tra cứu: <span class="num">{{ $invoice->final_lookup_code }}</span></p>@endif
            </section>
        @endif
    </article>

    <footer>Trang này chỉ dành cho người nhận link. Vui lòng không chia sẻ công khai.</footer>
</div>
</body>
</html>
