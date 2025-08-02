# Konfigurasi Upload File - API Paseban Kawis

## Perubahan yang Telah Dibuat

### 1. Laravel Validation

-   **Sebelum**: `max:10240` (10MB)
-   **Sesudah**: `max:51200` (50MB)

File yang diubah:

-   `app/Http/Controllers/ModulController.php` (baris 62 dan 159)

### 2. Server Configuration (.htaccess)

Ditambahkan konfigurasi PHP di `public/.htaccess`:

```apache
# PHP Upload Settings
<IfModule mod_php.c>
    php_value upload_max_filesize 50M
    php_value post_max_size 50M
    php_value memory_limit 256M
    php_value max_execution_time 300
    php_value max_input_time 300
</IfModule>
```

### 3. Custom Middleware

Dibuat middleware baru `app/Http/Middleware/UploadMiddleware.php` untuk mengatur konfigurasi PHP secara dinamis.

### 4. Route Configuration

Route upload modul sekarang menggunakan middleware 'upload':

-   `POST /api/modul` → dengan middleware 'upload'
-   `POST /api/modul/{id}` → dengan middleware 'upload'

## Batasan Upload Baru

| Setting             | Nilai Lama | Nilai Baru |
| ------------------- | ---------- | ---------- |
| Laravel Validation  | 10MB       | 50MB       |
| upload_max_filesize | Default    | 50M        |
| post_max_size       | Default    | 50M        |
| memory_limit        | Default    | 256M       |
| max_execution_time  | Default    | 300s       |
| max_input_time      | Default    | 300s       |

## Cara Kerja

1. **Middleware Upload**: Setiap request ke endpoint upload modul akan melalui middleware yang mengatur konfigurasi PHP
2. **Server Configuration**: File .htaccess mengatur batasan di level server
3. **Laravel Validation**: Validasi di level aplikasi untuk memastikan file tidak melebihi 50MB

## Testing

Untuk memastikan konfigurasi berfungsi, Anda bisa:

1. Upload file PDF dengan ukuran > 10MB
2. Upload file PDF dengan ukuran < 50MB
3. Cek log error jika ada masalah

## Troubleshooting

Jika masih ada masalah upload file besar:

1. **Restart web server** (Apache/Nginx)
2. **Clear Laravel cache**:
    ```bash
    php artisan cache:clear
    php artisan config:clear
    ```
3. **Check PHP configuration**:
    ```bash
    php -i | grep -i "upload_max_filesize"
    php -i | grep -i "post_max_size"
    ```

## Catatan Penting

-   Pastikan server memiliki cukup disk space untuk menyimpan file besar
-   Monitor penggunaan bandwidth untuk upload file besar
-   Pertimbangkan implementasi chunked upload untuk file yang sangat besar (>50MB)
