<?php
// retrieve the current operating session number and return it to the calling page

// bring in the utility files
require "open_db.php";

// get a database connection
$dbc = open_db();

// query the database
$sql = 'select setting_value from settings where setting_name = "session_nbr"';
$rs = mysqli_query($dbc, $sql);
$row = mysqli_fetch_row($rs);

// return the session number
print $row[0];
?>
