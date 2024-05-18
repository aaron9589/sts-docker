<?php
  // this routine returns the result of a database query in a simple <tr><td></td></tr> format to the calling HttpRequest

  // get a database connection
  require 'open_db.php';
  $dbc = open_db();

  // get the incoming parameter
  $sql = urldecode($_REQUEST['sql_query']);

  // run the query
  if (!$rs = mysqli_query($dbc, $sql))
  {
    print '<tr><td>SQL Error</td></td><tr><td>' . $mysql_error($rs) . '</td></tr>';
  }
  else
  {
    $table_guts = "";
    $num_cols = mysqli_num_fields($rs);
    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_row($rs))
      {
        $table_guts .= '<tr>';
        for ($i=0; $i<$num_cols; $i++)
        {
          $table_guts .= '<td>' . $row[$i] . '</td>';
        }
        $table_guts .= '</td>';
      }
      print $table_guts;
    }
    else
    {
      print '<tr><td>Zero rows returned by query</td></tr>';
    }
  }
?>  
