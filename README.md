# 1. ```bash
chmod +x setup.sh
./setup.sh

# 2. clear cache
php artisan tinker --execute="Cache::forget('daily_sentence_english_to_japanese');"
