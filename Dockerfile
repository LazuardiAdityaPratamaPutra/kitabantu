FROM php:8.1-fpm-alpine

# Pasang ekstensi mysqli wajib
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Pasang Nginx standar
RUN apk add --no-cache nginx

# HAPUS ALL CONFIG DEFAULT (Biar gak ada conflict server name di port 80)
RUN rm -f /etc/nginx/http.d/*.conf

# Masukkan file nginx.conf kita sebagai satu-satunya config utama
COPY nginx.conf /etc/nginx/http.d/kitabantu.conf

# Copy semua file kodingan lu ke folder server
COPY . /var/www/html

# Jalankan PHP-FPM dan Nginx barengan
CMD php-fpm -D && nginx -g "daemon off;"

EXPOSE 80