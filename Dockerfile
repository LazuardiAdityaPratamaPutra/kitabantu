FROM php:8.1-apache

# Mengaktifkan ekstensi mysqli secara resmi di dalam Docker
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua kodingan ke folder server Apache
COPY . /var/www/html/

# Buka port standar container
EXPOSE 80