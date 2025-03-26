FROM php:8.2-apache

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git unzip curl zip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd intl calendar

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Включение mod_rewrite
RUN a2enmod rewrite

# Установка прав
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

# Настройка Apache для Laravel
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>' >> /etc/apache2/apache2.conf

# Заменяем DocumentRoot на public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf
