FROM php:8.1-apache

# Install PostgreSQL dependencies + MySQL + R base
RUN apt-get update && apt-get install -y \
    r-base \
    libpq-dev \
    libmariadb-dev \
    && docker-php-ext-install mysqli pgsql pdo_pgsql

# Copy website files
COPY ./ /var/www/html/

# Install R packages
RUN R -e "install.packages(c('httr','jsonlite','dplyr','RMySQL'), repos='http://cran.r-project.org')"

EXPOSE 80

CMD ["apache2-foreground"]
