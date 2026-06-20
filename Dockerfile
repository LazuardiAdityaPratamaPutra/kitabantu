FROM webdevops/php-nginx:8.1

# Pasang ekstensi mysqli yang dibutuhkan kitabantu
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy semua file kodinganmu ke folder web server Nginx
COPY . /app/

# Buka port standar
EXPOSE 80