# 1. Install packages (includes MySQL server now)
sudo apt update && sudo apt install php php-cli php-fpm php-mysql php-mbstring php-xml php-bcmath php-curl unzip curl git composer nodejs npm mysql-server certbot python3-certbot-nginx -y

# 2. Secure MySQL (optional but recommended)
sudo mysql_secure_installation

# 3. Create MySQL DB + user
DB_NAME=language_app
DB_USER=language_user
DB_PASS=supersecurepassword

sudo mysql -u root -e "CREATE DATABASE ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -u root -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
sudo mysql -u root -e "FLUSH PRIVILEGES;"

# 4. Clone Laravel project
cd /var/www
sudo git clone <your-repo-url> language
cd language

# 5. Laravel app setup
cp .env.example .env
composer install --no-dev --optimize-autoloader
php artisan key:generate

# 6. Update .env for MySQL
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE='${DB_NAME}'/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME='${DB_USER}'/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD='${DB_PASS}'/' .env

# 7. Migrate database and link storage
php artisan migrate
php artisan storage:link

# 8. Set correct permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 9. Build frontend (optional)
npm install
npm run build

# 10. Add Laravel scheduler to crontab //change folder name
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/japaneseToEnglish && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# 11. Edit Nginx config
sudo nano /etc/nginx/sites-available/sitename

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

        location ^~ /.well-known/acme-challenge/ {
            alias /var/www/language/public/.well-known/acme-challenge/;
            default_type "text/plain";
            allow all;
        }
    }

    sudo ln -s /etc/nginx/sites-available/englishToJapanese /etc/nginx/sites-enabled/
    sudo ln -s /etc/nginx/sites-available/japaneseToEnglish /etc/nginx/sites-enabled/

# 12. Reload Nginx
sudo nginx -t && sudo systemctl reload nginx

# 13. Test Let's Encrypt path
sudo mkdir -p /var/www/japaneseToEnglish/public/.well-known/acme-challenge
echo "it works" | sudo tee /var/www/japaneseToEnglish/public/.well-known/acme-challenge/test

# 14. Run Certbot to get SSL cert
sudo certbot --nginx -d nihongo.email -d www.nihongo.email -d xn--dj1a40n.email -d www.xn--dj1a40n.email 


# 15
Cache::forget('deepseek_n3_japanese_sentence');

# 16 DNS

A Record	
@
128.199.160.197

CNAME Record	
www
namtokmoo.com

TXT Record	
@
v=spf1 include:qcloudmail.com ~all

TXT Record	
_dmarc
v=DMARC1; p=none

TXT Record	
qcloud._domainkey
v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCzddgkMMQnGBTARCF2WuaVK8kAQ8/RADG4kzxL2NkPPBDL1ByDi+9oFlpbNOvzMoM85bYfre0WIahnpMtaDn71AFk1h/H/S0HLJ7vpGimSUrmHF8biBzg53l1IpNebOQ9BM4u/6jYLA3GMLW6/30Ng8Dr92oW1nO9Ex2rPLbtHlwIDAQAB



