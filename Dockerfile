#checkov:skip=CKV_DOCKER_3: not using a default user.
# Use php:7.4-apache-buster as base image
FROM php:8.1-apache-buster

# Expose port 80
EXPOSE 80

# Install MariaDB client
# hadolint ignore=DL3008,DL3009,DL3015
RUN apt-get update && \
    apt-get install -y mariadb-client

# Install graphics library dependencies
# hadolint ignore=DL3008,DL3009,DL3015
RUN apt-get update && \
    apt-get install -y zlib1g-dev libpng-dev

# Cleanup
RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Configure and install GD extension
RUN docker-php-ext-configure gd && \
    docker-php-ext-install -j"$(nproc)" gd

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Add additional folders
COPY php-barcode /var/www/html/php-barcode
COPY phpqrcode /var/www/html/phpqrcode
COPY sts /var/www/html/sts

# Edit permissions for directories and create folder structure
RUN mkdir /var/www/html/sts/temp && \
    mkdir /var/www/html/sts/ImageStore/DB_Images/barcodes && \
    mkdir /var/www/html/sts/ImageStore/DB_Images/qrcodes && \
    chmod 757 /var/www/html/sts/backups && \
    chmod -R 757 /var/www/html/sts/ImageStore && \
    chmod 757 /var/www/html/sts/temp && \
    chmod 757 /var/www/html/sts/uploads

# Copy start script
COPY start.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start.sh

# Health check using curl
HEALTHCHECK --interval=30s --timeout=10s \
  CMD curl --silent --fail http://localhost/sts || exit 1

# Define entrypoint
ENTRYPOINT ["/usr/local/bin/start.sh"]
