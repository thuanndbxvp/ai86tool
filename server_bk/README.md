# License Server Setup Guide

## Cấu trúc thư mục

```
/sync.gomhuongcanh.vn/
├── config.php              # Cấu hình (đã có sẵn DB và Private Key)
├── db.php                  # Database + LicenseManager class
├── auth.php                # Authentication
├── api/
│   └── verify.php          # API endpoint cho client
├── admin/
│   ├── index.php           # Login
│   ├── dashboard.php       # Dashboard
│   ├── create.php          # Tạo license
│   ├── view.php            # Xem chi tiết
│   ├── revoke.php          # Thu hồi license
│   └── logout.php          # Đăng xuất
├── install.php             # Cài đặt database (XÓA SAU KHI DÙNG!)
└── .htaccess               # Bảo mật
```

## Cài đặt

### 1. Upload files
Upload toàn bộ nội dung thư mục `server/` lên `sync.gomhuongcanh.vn/`

### 2. Chạy install
Truy cập: `https://sync.gomhuongcanh.vn/install.php`

Database sẽ tự động tạo các bảng:
- `licenses` - Danh sách license
- `devices` - Thiết bị đã kích hoạt
- `audit_log` - Nhật ký

**QUAN TRỌNG**: Xóa `install.php` ngay sau khi cài đặt!

### 3. Đăng nhập admin
- URL: `https://sync.gomhuongcanh.vn/admin/`
- Username: `admin`
- Password: `admin123`

**Đổi mật khẩu ngay sau lần đầu đăng nhập!**

### 4. Tạo license đầu tiên
Vào "Create New License" để tạo key.

Format key: `XXXX-XXXX-XXXX-XXXX` (16 ký tự, gồm chữ và số)

## Client Public Key

Public key cho client (`license_client.py`):
```
dqYJZLKtz2Zq0212L/FUaC/jMhwFcvyUPRYXIhGhWh4=
```

## API Endpoint

```
POST https://sync.gomhuongcanh.vn/api/verify
Content-Type: application/json

{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "device_id": "hardware_fingerprint",
  "nonce": "random_string",
  "timestamp": 1234567890,
  "app_version": "1.0.0"
}
```

## Bảo mật

1. **Đã có sẵn trong code:**
   - `.htaccess` chặn truy cập config, db files
   - Session timeout 1 giờ
   - Rate limiting login (5 lần/5 phút)
   - Ed25519 signature verification

2. **Nên làm thêm:**
   - Enable HTTPS (nếu chưa có)
   - Đổi admin password
   - Giới hạn IP truy cập admin (nếu có thể)

## Troubleshooting

**Lỗi 500**: Check error log tại `logs/error.log`

**Key không hoạt động**: 
- Verify PRIVATE_KEY trong config.php
- Check database có table `licenses` chưa

**Device limit**: Mặc định 2 devices/license, có thể đổi khi tạo license
