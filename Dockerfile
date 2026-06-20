FROM php:8.2-apache

# Aktifkan ekstensi mysqli yang dibutuhkan untuk database
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Salin semua source code ke folder root Apache di server
COPY . /var/www/html/

# Berikan izin akses folder agar Apache bisa membaca file
RUN chown -R www-data:www-data /var/www/html/

# Gunakan port standar yang disediakan Railway
EXPOSE 80