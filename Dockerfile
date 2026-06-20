FROM php:8.1-fpm-alpine

# Pasang ekstensi mysqli wajib
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Pasang Nginx standar
RUN apk add --no-cache nginx

# Hapus config bawaan dan masukkan file nginx.conf kita
RUN rm /etc/nginx/http.d/default.conf
COPY nginx.conf /etc/nginx/http.d/kitabantu.conf

# Copy semua file kodingan lu ke folder server
COPY . /var/www/html

# Jalankan PHP-FPM dan Nginx
CMD php-fpm -D && nginx -g "daemon off;"

EXPOSE 80