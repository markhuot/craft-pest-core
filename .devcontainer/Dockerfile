FROM mcr.microsoft.com/devcontainers/php:8.2
RUN apt update && apt install -y libicu-dev libz-dev libzip-dev vim mariadb-client
RUN docker-php-ext-install bcmath intl zip pdo_mysql
