<!DOCTYPE HTML>
<html>
  <head>
    <title>Display the instructions for the specified job</title>
  </head>
  <body>
<?php
  // this program is called from get_cars_in_job.php, which is part of set_out.php
  
  // query the sts database for the instructions associated with the incoming job_id
  require 'open_db.php';
  $dbc = open_db();
  $sql = 'select name, description from jobs where id = "' . $_GET['job_id'] . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);
  
  print '<h1>Job Name: ' . $row['name'] . '</h1>';
  print '<h2>Instructions: <br /><hr />' . nl2br($row['description']) . '</h2><br /><br />';
  
?>
  </body>
</html>