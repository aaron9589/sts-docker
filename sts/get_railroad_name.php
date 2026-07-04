<?php
// Retrieve the configured railroad name for the main menu.

require "open_db.php";

$dbc = open_db();

$sql = 'select setting_value from settings where setting_name = "railroad_name"';
$rs = mysqli_query($dbc, $sql);

if ($rs && mysqli_num_rows($rs) > 0)
{
  $row = mysqli_fetch_row($rs);
  print trim($row[0]);
}
?>
