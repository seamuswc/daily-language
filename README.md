# 1. Go into the Laravel project directory
cd /path/to/your-laravel-app

# 2. Copy the example environment config
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Install Composer dependencies
composer install --no-dev --optimize-autoloader

# 5. Run database migrations (if needed)
php artisan migrate

# 6. Link the storage directory
php artisan storage:link

# 7. Install Node dependencies (optional, only if you use Vite/JS/CSS)
npm install
npm run build

# 8. Daily mail send
crontab -e
add below to bottom
* * * * * cd /path/to/your-laravel-app && php artisan schedule:run >> /dev/null 2>&1
🔄 What This Does:
    Your cron job runs Laravel's scheduler every minute
    Laravel internally decides which commands should actually run
    Your send:daily-japanese command will be triggered automatically