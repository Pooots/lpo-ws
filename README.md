# LPO API (`lpo-ws`)

Laravel backend for LPO. Foundation mirrors LPay (`lpay-ws`).

## Quick start (XAMPP MySQL)

1. Start Apache + MySQL in XAMPP.
2. Create database `lpo` (phpMyAdmin or CLI).
3. Install and run:

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan serve
```

- API root: http://localhost:8000/api  
- Health: http://localhost:8000/api/health  
