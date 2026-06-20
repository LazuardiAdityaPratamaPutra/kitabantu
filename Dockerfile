FROM webdevops/php-nginx:8.1

# Konfigurasi folder utama web
ENV WEB_DOCUMENT_ROOT=/app
ENV WEB_DOCUMENT_INDEX=index.php

# PAKSA Nginx bawaan image ini untuk berjalan di port 8000 secara sistem
ENV WEB_HTTP_PORTS=8000

# Pasang ekstensi mysqli bawaan proyekmu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua file kodinganmu ke folder web server
COPY . /app/

# Buka port 8000
EXPOSE 8000