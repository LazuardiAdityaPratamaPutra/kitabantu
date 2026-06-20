FROM php:8.1-fpm-alpine

# Pasang ekstensi mysqli wajib
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Pasang Nginx standar
RUN apk add --no-cache nginx

# Amankan konfigurasi agar murni memakai file kita
RUN rm -f /etc/nginx/http.d/*.conf
COPY nginx.conf /etc/nginx/http.d/kitabantu.conf

# Copy semua kodingan lu
COPY . /var/www/html

# Jalankan PHP-FPM dan Nginx secara bersamaan
CMD php-fpm -D && nginx -g "daemon off;"

EXPOSE 80