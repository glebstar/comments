# Comments API (Laravel)

Simple API for news, video posts, and threaded comments.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (for local/dev)

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create environment file:
   ```bash
   cp .env.example .env
   ```
3. Generate app key:
   ```bash
   php artisan key:generate
   ```
4. Configure database in `.env` (DB_* variables).
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. (Optional) Run the dev server:
   ```bash
   php artisan serve
   ```

## Testing

Tests use an in-memory SQLite database by default (see `phpunit.xml`).

Run all tests:
```bash
php artisan test
```
