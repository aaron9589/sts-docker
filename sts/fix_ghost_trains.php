<html>
  <head>
    <title>STS - Fix Ghost Trains</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      // this javascript function is triggered by the user changing the "All" checkbox
      function checkall()
      {
        var row_count = document.getElementById('step_table').rows.length-1;
        if (document.getElementById('check_all').checked == true)
        {
          for (var i=0; i < row_count; i++)
          {
            var checkbox_name = "check" + i.toString();
            document.getElementById(checkbox_name).checked = true;
          }
        }
        else
        {
          for (var i=0; i < row_count; i++)
          {
            var checkbox_name = "check" + i.toString();
            document.getElementById(checkbox_name).checked = false;
          }
        }
      }
    </script>
  </head>
  <body>
<p><img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
  <map name="Map3">
    <area shape="rect" coords="567,5,710,47" href="index.html">
    <area shape="rect" coords="568,98,708,142" href="index-t.html">
    <area shape="rect" coords="567,54,711,92" href="db-maint.html">
  </map>
</p>
<h2><a href="validate_db.php"><img src="ImageStore/GUI/Menu/validate.png" width="166" height="40" border="0"></a></h2>
<h2>Database Maintenance</h2>
    <h3 >Fix Ghost Trains</h3>

    <?php
      // pull in the utility files
      require 'open_db.php';

      // get a database connection
      $dbc = open_db();

      // was the fix it button clicked?
      if (isset($_GET['fix_ghosts_btn']))
      {
        // go through the incoming rows and fix the selected cars
        for ($i=0; $i<$_GET['row_counter']; $i++)
        {
          if (isset($_GET['check' . $i]))
          {
            print '<br />Removing ' . $_GET['table_name' . $i] . ' abandoned job steps...';
            
            // remove the ghost table
            $sql = 'drop table `' . $_GET['table_name' . $i] . '`';
  //print 'SQL: ' . $sql . '<br />';
            if (!mysqli_query($dbc, $sql))
            {
              print 'Unable to drop location<br />';
              die();
            }
          }
        }
      }

      // get a list of all job in the database that aren't in the jobs table
      $sql = 'SELECT table_name
                FROM information_schema.tables
               WHERE table_type = "base table"
                 AND table_schema="sts_db3"
                 and table_name not in (select name from sts_db3.jobs)
                 and table_name not in ("blocks",
                                        "cars",
                                        "car_codes",
                                        "car_orders",
                                        "commodities",
                                        "empty_locations",
                                        "jobs",
                                        "locations",
                                        "owners",
                                        "ownership",
                                        "pool",
                                        "pu_criteria",
                                        "routing",
                                        "settings",
                                        "shipments")';
      $rs = mysqli_query($dbc, $sql);
      if (mysqli_num_rows($rs) > 0)
      {
        print'<div id="instructions">
                The job steps for the following ghost trains do not appear on the Jobs screen because the job/train itself has been<br />
                removed, however sets of steps for each of the jobs/trains still remain in the system. When the FIX GHOSTS button is clicked,<br />
                the selected sets of job steps will be removed from the database.<br /><br />
              </div>';

        print '<form action="fix_ghost_trains.php" method="get">';
        print '<input type="submit" id="fix_ghosts_btn" name="fix_ghosts_btn" value="FIX GHOSTS">&nbsp;';
    
        print 'Check/Uncheck all ghost trains: <input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
        print '<table id="step_table" name="step_table">';
        print '<th>Fix?</th>
               <th>Job Name</th>';
               
        $row_counter = 0;
        while($row = mysqli_fetch_array($rs))
        {
          print '<tr>
                   <td style="text-align: center;">
                     <input type="checkbox" id="check' . $row_counter . '" name="check' . $row_counter . '">
                   </td>
                   <td>' . $row['table_name'] . '
                     <input type="hidden" id="table_name' . $row_counter . '" name="table_name' . $row_counter . '" value="' . $row['table_name'] . '">
                   </td>
                 </tr>';
          $row_counter++;
        }
        print '</table>';
        print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
      
        print '</form>';
      }
      else
      {
        // if no ghosts found, say so
        print '<br /><br />No ghost trains found';
      }
    ?>

</body>
</html>


