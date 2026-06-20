FROM php:8.1-apache

# Pasang ekstensi mysqli yang stabil langsung di dalam container Docker
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua file kodinganmu ke folder root web server Apache
COPY . /var/www/html/

# Buka port standar Apache
EXPOSE 80