# 🚀 Laravel Deployment (Ubuntu + Nginx + SQLite + SSL)

## 🧰 Full Setup (One Paste, Run Step-by-Step)

```bash
# 1. Install all necessary packages
sudo apt update && sudo apt install php php-cli php-fpm php-sqlite3 php-mbstring php-xml php-bcmath php-curl unzip curl git composer nodejs npm certbot python3-certbot-nginx -y

# 2. Clone Laravel project
cd /var/www
sudo git clone <your-repo-url> language
cd language

# 3. Laravel app setup
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate

# 4. SQLite setup
touch database/database.sqlite
sudo chown www-data:www-data database/database.sqlite
sudo chmod 664 database/database.sqlite

# 5. Set DB connection in .env
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
echo "DB_DATABASE=$(pwd)/database/database.sqlite" >> .env

# 6. Laravel migration and storage link
php artisan migrate
php artisan storage:link

# 7. Set correct permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 8. Build frontend (optional, if using Vite or Mix)
npm install
npm run build

# 9. Add Laravel scheduler to crontab
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/language && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# 10. Edit Nginx config
sudo nano /etc/nginx/sites-available/default

server {
    listen 80;
    server_name namtokmoo.com www.namtokmoo.com;

    root /var/www/language/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Let’s Encrypt challenge path
    location ^~ /.well-known/acme-challenge/ {
        alias /var/www/language/public/.well-known/acme-challenge/;
        default_type "text/plain";
        allow all;
    }
}

# 11. Reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# 12. Test Let's Encrypt challenge path
sudo mkdir -p /var/www/language/public/.well-known/acme-challenge
echo "it works" | sudo tee /var/www/language/public/.well-known/acme-challenge/test

# 13. Run Certbot to issue SSL certificate
sudo certbot --nginx -d namtokmoo.com -d www.namtokmoo.com
