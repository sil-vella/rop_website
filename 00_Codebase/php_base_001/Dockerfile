# Reign of Play website — PHP backend + dashboard frontends (e.g. Dutch dashboard)
# Build context: 00_Codebase (so we can COPY php_base_001 and 00_dashboard/Dutch)
# Do not COPY .env; provide at runtime via docker-compose or env.

FROM php:8.2-apache

# Enable Apache rewrite (optional, for clean URLs if needed)
RUN a2enmod rewrite

# Install PHP MySQL extension for DB (e.g. dutch_dashboard and other RoP DBs)
RUN docker-php-ext-install -j$(nproc) pdo pdo_mysql

# Document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy PHP backend (api/, lib/, config.php; no .env)
COPY php_base_001/config.php /var/www/html/
COPY php_base_001/api /var/www/html/api
COPY php_base_001/lib /var/www/html/lib

# Copy Dutch dashboard frontend (login only; no register)
COPY 00_dashboard/Dutch/index.html /var/www/html/
COPY 00_dashboard/Dutch/login.html /var/www/html/
COPY 00_dashboard/Dutch/css /var/www/html/css
COPY 00_dashboard/Dutch/js /var/www/html/js

# In container, API is same-origin (/api/); frontend default is same-origin
RUN sed -i "s|../dsh_php_base_001|''|g" /var/www/html/index.html /var/www/html/login.html 2>/dev/null || true

# Allow .htaccess if present (optional)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
