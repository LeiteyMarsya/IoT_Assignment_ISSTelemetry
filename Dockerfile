FROM php:8.1-apache

RUN apt-get update && apt-get install -y r-base libmariadb-dev && docker-php-ext-install mysqli

COPY ./iss_dashboard /var/www/html/

RUN R -e "install.packages(c('httr','jsonlite','dplyr','RMySQL'), repos='http://cran.r-project.org')"

EXPOSE 80

CMD ["apache2-foreground"]
