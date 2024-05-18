# Use php:7.4-apache-buster as base image
FROM php:7.4-apache-buster

# Expose port 80
EXPOSE 80

# Install MariaDB server and client
RUN apt-get update && \
    apt-get install -y mariadb-client

# Install graphics library dependencies
RUN apt-get update && \
    apt-get install -y zlib1g-dev libpng-dev

# Cleanup
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure and install GD extension
RUN docker-php-ext-configure gd && \
    docker-php-ext-install -j$(nproc) gd

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Add additional folders
ADD php-barcode /var/www/html/php-barcode
ADD phpqrcode /var/www/html/phpqrcode
ADD sts /var/www/html/sts

# Edit permissions for directories
RUN chmod 757 /var/www/html/sts/backups && \
    chmod -R 757 /var/www/html/sts/ImageStore && \
    chmod 757 /var/www/html/sts/temp && \
    chmod 757 /var/www/html/sts/uploads

# Copy start script
COPY start.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start.sh

# Define entrypoint
ENTRYPOINT ["/usr/local/bin/start.sh"]
