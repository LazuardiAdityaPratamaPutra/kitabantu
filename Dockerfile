FROM php:8.1-apache

# Pasang ekstensi mysqli yang wajib untuk database proyekmu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy seluruh kodinganmu ke folder server apache
COPY . /var/www/html/

# Paksa Apache untuk membaca folder kerja di /var/www/html/
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Buka port standar container
EXPOSE 80