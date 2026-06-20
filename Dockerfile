FROM php:8.1-fpm-alpine

# Pasang ekstensi mysqli yang wajib buat database proyek lu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Pasang Nginx standar Linux alpine
RUN apk add --no-cache nginx

# Hapus konfigurasi default Nginx yang bikin bad gateway
RUN rm /etc/nginx/http.d/default.conf

# Buat konfigurasi Nginx baru langsung di dalam Dockerfile
RUN echo 'server { \n\
    listen 80; \n\
    root /app; \n\
    index index.php index.html; \n\
    location / { \n\
        try_files $uri $uri/ /index.php?$args; \n\
    } \n\
    location ~ \.php$ { \n\
        try_files $uri =404; \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        include fastcgi_params; \n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
    } \n\
}' > /etc/nginx/http.d/kitabantu.conf

# Copy semua file kodingan lu ke folder /app (aman dari folder sistem)
COPY . /app

# Jalankan PHP-FPM dan Nginx barengan saat container nyala
CMD php-fpm -D && nginx -g "daemon off;"

EXPOSE 80