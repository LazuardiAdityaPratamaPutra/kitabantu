FROM webdevops/php-nginx:8.1

# Setel port internal container agar mematuhi Railway
ENV WEB_DOCUMENT_ROOT=/app
ENV WEB_DOCUMENT_INDEX=index.php

# Pasang ekstensi mysqli bawaan proyekmu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua file kodinganmu ke folder web server
COPY . /app/

# Buka port standar
EXPOSE 80