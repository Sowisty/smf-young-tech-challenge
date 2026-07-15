# Wybór oficjalnego obrazu PHP z rozszerzeniami FPM wspierającego PHP 8.4
FROM php:8.4-fpm

# Zezwolenie Composerowi na uruchamianie jako root wewnątrz kontenera
ENV COMPOSER_ALLOW_SUPERUSER=1

# Instalacja niezbędnych bibliotek systemowych, narzędzi do pracy z SQLite oraz silnika Tesseract OCR
# Dodajemy tesseract-ocr oraz polskie słowniki tesseract-ocr-pol
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    tesseract-ocr \
    tesseract-ocr-pol

# Czyszczenie pamięci podręcznej instalatora pakietów systemowych
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalacja rozszerzeń PHP niezbędnych dla prawidłowego działania Laravela oraz bazy SQLite
RUN docker-php-ext-install pdo mbstring exif pcntl bcmath gd pdo_sqlite

# Pobranie oficjalnego, najnowszego managera pakietów Composer do kontenera
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ustawienie głównego katalogu roboczego wewnątrz kontenera
WORKDIR /var/www

# Skopiowanie kodu źródłowego aplikacji z Twojego komputera do kontenera
COPY . /var/www

# Instalacja wszystkich zależności PHP zdefiniowanych w pliku composer.json
# Używamy flagi --no-scripts, aby zapobiec uruchamianiu skryptów artisan przed montowaniem wolumenów
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# Nadanie uprawnień do zapisu i modyfikacji dla serwera WWW
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Otwarcie portu 9000
EXPOSE 9000

# Uruchomienie domyślnego procesu PHP-FPM
CMD ["php-fpm"]