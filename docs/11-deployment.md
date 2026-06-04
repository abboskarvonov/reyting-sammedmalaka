# 11 — Deploy va Muhit Sozlamalari

## Talablar

| Komponent | Minimal versiya              |
| --------- | ---------------------------- |
| PHP       | 8.2+                         |
| MySQL     | 8.0+                         |
| Nginx     | 1.18+                        |
| Redis     | 6.0+ (ixtiyoriy, kesh uchun) |
| Composer  | 2.x                          |
| Node.js   | 18+                          |

---

## 1. Loyihani clone qilish

```bash
git clone https://github.com/your-org/staff-rating.git
cd staff-rating
```

---

## 2. PHP paketlarini o'rnatish

```bash
composer install --optimize-autoloader --no-dev
```

---

## 3. `.env` fayl sozlamalari

```bash
cp .env.example .env
php artisan key:generate
```

```dotenv
# .env

APP_NAME="Staff Rating"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# O'quv yili va semestr (har semestr yangilanadi)
ACADEMIC_YEAR=2024-2025
SEMESTER=1

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=staff_rating
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Cache (Redis ishlatilsa)
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=480

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Staff Rating"

# Queue
QUEUE_CONNECTION=database
```

---

## 4. `config/app.php` ga qo'shish

```php
// config/app.php
'academic_year' => env('ACADEMIC_YEAR', '2024-2025'),
'semester'      => env('SEMESTER', '1'),
```

---

## 5. Ma'lumotlar bazasini tayyorlash

```bash
# Migratsiyalar
php artisan migrate --force

# Boshlang'ich ma'lumotlar (admin, savollar)
php artisan db:seed --force
```

---

## 6. Frontend build

```bash
npm install
npm run build
```

---

## 7. Storage linki

```bash
php artisan storage:link
```

---

## 8. Cache va optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache   # agar Blade icons ishlatilsa
```

---

## 9. Nginx konfiguratsiyasi

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/staff-rating/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Upload limit (Excel import uchun)
    client_max_body_size 10M;
}
```

---

## 10. PHP-FPM sozlamalari

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10

; Upload limit
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
```

---

## 11. Queue worker (supervisor)

```ini
; /etc/supervisor/conf.d/staff-rating-worker.conf
[program:staff-rating-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/staff-rating/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/staff-rating/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start staff-rating-worker:*
```

---

## 12. Scheduled tasks (cron)

```bash
# crontab -e
* * * * * cd /var/www/staff-rating && php artisan schedule:run >> /dev/null 2>&1
```

```php
// app/Console/Kernel.php (yoki routes/console.php)
Schedule::command('attendances:check-overdue')->dailyAt('17:00');
Schedule::command('tasks:mark-overdue')->daily();
Schedule::command('cache:clear')->weekly();
```

---

## 13. SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d yourdomain.com -d www.yourdomain.com
certbot renew --dry-run   # avtomatik yangilashni tekshirish
```

---

## 14. Birinchi admin yaratish

```bash
php artisan tinker

# Tinker ichida:
\App\Models\User::create([
    'name'     => 'Super Admin',
    'email'    => 'admin@yourorg.com',
    'password' => bcrypt('strongpassword'),
    'role'     => 'admin',
    'is_active' => true,
]);
```

Yoki Seeder orqali:

```php
// database/seeders/AdminSeeder.php
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@yourorg.com'],
            [
                'name'      => 'Admin',
                'password'  => bcrypt(env('ADMIN_PASSWORD', 'changeme123')),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );
    }
}
```

---

## 15. Deploy scripti (yangilanish uchun)

```bash
#!/bin/bash
# deploy.sh

set -e

echo "=== Deploy boshlandi ==="

cd /var/www/staff-rating

git pull origin main

echo "--- Composer o'rnatish ---"
composer install --optimize-autoloader --no-dev

echo "--- NPM build ---"
npm ci && npm run build

echo "--- Migratsiyalar ---"
php artisan migrate --force

echo "--- Cache tozalash va yangilash ---"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "--- Queue restart ---"
php artisan queue:restart

echo "=== Deploy tugadi ==="
```

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## Muhit tekshiruvi

```bash
# Barcha muhit sozlamalarini tekshirish
php artisan about

# Ma'lumotlar bazasi ulanishini tekshirish
php artisan db:show

# Route ro'yxati
php artisan route:list --columns=method,uri,name,middleware

# Queue holati
php artisan queue:monitor database:default
```

---

## Backup (ixtiyoriy)

```bash
# Kunlik backup (cron)
0 2 * * * mysqldump -u user -ppassword staff_rating | gzip > /backups/staff_rating_$(date +%Y%m%d).sql.gz

# 30 kundan eski backuplarni o'chirish
0 3 * * * find /backups -name "*.sql.gz" -mtime +30 -delete
```
