#!/bin/bash

# Check if the database should be provisioned
if [ "$PROVISION_DATABASE" = "1" ]; then
    # Check if the database has been initialized
    if [ ! -f /opt/sql.initialized ]; then

        # Execute the SQL script on the newly created database with authentication
        if mysql -h "$MYSQL_HOST" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < /var/www/html/sts/create_sts_db3.sql; then
            # Create a marker to indicate that database initialization is completed
            touch /opt/sql.initialized
        else
            echo "MySQL command failed. Database initialization aborted."
        fi

    fi
fi

# Set a Time Zone for PHP if one is set in the PHP containers TZ Env var.
if [ -n "$TZ" ]; then
    echo "date.timezone = $TZ" > /usr/local/etc/php/conf.d/timezone.ini
else
    rm -f /usr/local/etc/php/conf.d/timezone.ini
fi

# Start Apache
exec apache2-foreground
