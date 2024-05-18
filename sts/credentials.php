<?php
  // standard routine to set up database credentials
  // this is the only place that they have to be set

  // Set default values
  $server_name = '127.0.0.1';
  $user_name = 'sts_user';
  $password = 'sts_password';
  $db_name = 'sts_db3';

  // Check if environment variables are set and override default values if present
  if (getenv('MYSQL_HOST')) {
      $server_name = getenv('MYSQL_HOST');
  }
  if (getenv('MYSQL_USER')) {
      $user_name = getenv('MYSQL_USER');
  }
  if (getenv('MYSQL_PASSWORD')) {
      $password = getenv('MYSQL_PASSWORD');
  }
  if (getenv('MYSQL_DATABASE')) {
      $db_name = getenv('MYSQL_DATABASE');
  }
?>
