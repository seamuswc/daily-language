#!/bin/bash

# Interactive Laravel Production Setup Script

read -p "Enter your domain name (e.g. namtokmoo.com): " DOMAIN
read -p "Enter your Laravel repo git URL: " REPO
read -p "Enter MySQL DB name: " DB_NAME
read -p "Enter MySQL DB username: " DB_USER
read -s -p "Enter MySQL DB password: " DB_PASS

# Install required packages
sudo apt update && sudo apt install \
php php-cli php-fpm php-mysql php-mbstring php-xml php-bcmath php-curl \
unzip curl git composer nodejs npm \
mysql-server certbot python3-certbot-nginx -y

# Secure MySQL
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -u root -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
sudo mysql -u root -e "FLUSH PRIVILEGES;"

# Clone project
cd /var/www
sudo git clone ${REPO} language
cd language

# Set ownership
sudo chown -R $USER:www-data .

# Laravel install & setup
php artisan env:setup-production
cp .env.production .env
php artisan key:generate


# Inject DB credentials into .env
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
# Force production-safe defaults
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env.production
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=database/' .env.production


php artisan migrate
php artisan storage:link

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

npm install
npm run build

# Crontab
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/language && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Nginx setup
CONF="/etc/nginx/sites-available/language"
sudo bash -c "cat > \$CONF" <<EOL
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};

    root /var/www/language/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ^~ /.well-known/acme-challenge/ {
        alias /var/www/language/public/.well-known/acme-challenge/;
        default_type "text/plain";
        allow all;
    }
}
EOL

sudo ln -s /etc/nginx/sites-available/language /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# Certbot
sudo mkdir -p /var/www/language/public/.well-known/acme-challenge
sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Done
echo "✅ Deployment complete! Your site should be live at: https://${DOMAIN}"
