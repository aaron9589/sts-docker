#!/bin/bash

# Check if the database should be provisioned
if [ "$PROVISION_DATABASE" = "1" ]; then
    # Check if the database has been initialized
    if [ ! -f /opt/sql.initialized ]; then

        # Execute the SQL script on the newly created database with authentication
        mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /var/www/html/sts/create_sts_db3.sql && \

        # Create a marker to indicate that database initialization is complete
        touch /opt/sql.initialized

    fi
fi

# Start Apache
exec apache2-foreground
