FROM php:8.3-apache

# Instalar las extensiones que requiere tu proyecto (PDO MySQL, cURL y mbstring)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli curl mbstring

# Habilitar el módulo rewrite de Apache (esencial para enrutadores y URLs limpias)
RUN a2enmod rewrite

# Copiar todos los archivos de tu proyecto al directorio web de Apache
COPY . /var/www/html/

# Dar permisos de escritura a la carpeta de subidas para que la app guarde las imágenes
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Exponer el puerto por defecto
EXPOSE 80
