# Xuất hoá đơn

Web quản lý quy trình xuất hoá đơn cho công ty dịch vụ, gồm 5 bước:

1. **Thông tin cần xuất hoá đơn** — tên đơn vị, MST, địa chỉ, email nhận hoá đơn, người liên hệ, số báo giá.
2. **Hạng mục theo báo giá** — STT, nội dung, số lượng, đơn vị, đơn giá, thành tiền; tự tính thuế GTGT (0/5/8/10%/KCT), tổng tiền và đọc số tiền bằng chữ.
3. **Hoá đơn nháp + xác nhận** — tải nháp (PDF/ảnh/XML), gửi **link riêng** cho khách. Khách mở link (không cần tài khoản) để xem và bấm *Xác nhận nháp đúng* hoặc *Yêu cầu sửa*. Tải nháp mới thì trạng thái tự về “Chờ khách xác nhận”.
4. **Hoá đơn final** — tải file chính thức, ghi ký hiệu, số, ngày, mã tra cứu. Khách tải được từ cùng link.
5. **Tình trạng thanh toán** — ghi từng lần nhận tiền; trạng thái *Chưa thanh toán / Một phần / Đã thanh toán* và số còn phải thu tự cập nhật.

Kèm theo: bảng tổng quan (chờ xác nhận, chờ final, còn phải thu, quá hạn), thông báo chuông khi khách xác nhận/yêu cầu sửa, lịch sử thao tác từng hồ sơ, quản lý người dùng.

**Công nghệ:** PHP 8.3 · Laravel 12 · Filament 5 · PostgreSQL 16 · Docker Compose. Giao diện tiếng Việt, VND, múi giờ Asia/Ho_Chi_Minh.

---

## Cài lên VPS (Docker)

Yêu cầu: VPS Ubuntu có Docker + Docker Compose plugin, tên miền trỏ qua Cloudflare.

```bash
# 1. Đưa mã nguồn lên VPS
sudo mkdir -p /opt/xuat-hoa-don && sudo chown $USER /opt/xuat-hoa-don
cd /opt/xuat-hoa-don          # giải nén / git clone mã nguồn vào đây

# 2. Tạo file cấu hình
cp .env.example .env
nano .env    # sửa APP_URL, DB_PASSWORD, ADMIN_EMAIL, ADMIN_PASSWORD, COMPANY_NAME...

# 3. Build image và tạo APP_KEY
docker compose build
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
#   -> chép chuỗi base64:... vào dòng APP_KEY= trong .env

# 4. Chạy
docker compose up -d
docker compose logs -f app     # xem migrate + tạo admin chạy xong
```

App chạy ở `127.0.0.1:8080` trên VPS (đổi bằng `APP_PORT` trong `.env`). Đăng nhập tại `https://<tên-miền>/admin` bằng `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

### Nginx trên VPS (phía trước, nhận từ Cloudflare)

```nginx
server {
    listen 80;
    server_name hoadon.example.com;
    client_max_body_size 25M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}
```

Trên Cloudflare: bật proxy (mây cam), SSL/TLS chế độ **Full**, hoặc dùng Origin Certificate để chạy **Full (strict)**. Nếu chỉ thử qua HTTP không có TLS, đặt `SESSION_SECURE_COOKIE=false` trong `.env`, nếu không sẽ không đăng nhập được.

### Cập nhật phiên bản mới

```bash
cd /opt/xuat-hoa-don
# chép mã mới vào (git pull hoặc giải nén đè, giữ nguyên .env)
docker compose build && docker compose up -d
```

Migrate chạy tự động mỗi lần container khởi động.

### Sao lưu

```bash
sh docker/backup.sh     # tạo backups/db-*.sql.gz và backups/files-*.tar.gz, giữ 14 bản
```

Đặt chạy hằng đêm: `crontab -e` rồi thêm
`15 2 * * * cd /opt/xuat-hoa-don && sh docker/backup.sh >> backups/backup.log 2>&1`

Khôi phục database: `gunzip -c backups/db-XXXX.sql.gz | docker compose exec -T db psql -U hoadon -d hoadon`

---

## Cách dùng

1. **Hồ sơ hoá đơn → Tạo hồ sơ mới**: nhập thông tin bên mua và hạng mục, bấm *Tạo*.
2. Ở trang hồ sơ, tải **file nháp** ở mục 3 rồi bấm **Lưu**.
3. Bấm **Link khách xác nhận** ở góc trên → chép link gửi khách qua email/Zalo.
4. Khách xác nhận hoặc yêu cầu sửa → bạn nhận thông báo ở biểu tượng chuông; mục 3 hiện kết quả. Khách báo qua điện thoại thì dùng **Xác nhận thay khách** / **Ghi yêu cầu sửa**.
5. Có hoá đơn chính thức: tải ở mục 4, điền số/ký hiệu/ngày.
6. Nhận tiền: kéo xuống bảng **Thanh toán** → **Ghi nhận thanh toán**.

Link khách dùng chuỗi ngẫu nhiên 40 ký tự; ai có link đều xem được hồ sơ đó. Lỡ gửi nhầm người: menu **⋯ → Tạo link mới (vô hiệu link cũ)**.

## Chạy thử trên máy (không Docker)

```bash
composer install
cp .env.example .env    # đặt DB_CONNECTION=sqlite, xoá các dòng DB_HOST..DB_PASSWORD, SESSION_SECURE_COOKIE=false, APP_URL=http://localhost:8000
touch database/database.sqlite
php artisan key:generate && php artisan migrate && php artisan db:seed
php artisan serve
php artisan test
```

## Cấu trúc chính

| Đường dẫn | Nội dung |
|---|---|
| `app/Models/Invoice.php` | Hồ sơ, tính tổng tiền, trạng thái nháp/thanh toán, lịch sử |
| `app/Filament/Resources/Invoices/` | Màn hình quản trị: form 5 bước, danh sách, thanh toán, lịch sử |
| `app/Http/Controllers/CustomerInvoiceController.php` | Trang khách xác nhận qua link |
| `resources/views/customer/invoice.blade.php` | Giao diện trang khách |
| `app/Support/Money.php` | Định dạng VND, đọc số bằng chữ |
| `config/hoadon.php` | Thông tin bên bán, thuế suất, dung lượng file |
| `docker/` | Dockerfile, Nginx, PHP, sao lưu |
