<html>
  <head>
    <title>STS - Fix Ghost Job Steps</title>
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
<p> <img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
  <map name="Map3">
    <area shape="rect" coords="567,5,710,47" href="index.html">
    <area shape="rect" coords="568,98,708,142" href="index-t.html">
    <area shape="rect" coords="567,54,711,92" href="db-maint.html">
  </map>
</p>
    
<h2><a href="validate_db.php"><img src="ImageStore/GUI/Menu/validate.png" width="166" height="40" border="0"></a></h2>
<h2>Database Maintenance</h2>
    <h3 >Fix Ghost Job Steps</h3>

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
            print '<br />Removing Step ' . $_GET['step' . $i] . ' in Job ' . $_GET['name' . $i] . '...';
            
            // remove the ghost location
            $sql = 'delete from `' . $_GET['name' . $i] . '` where step_number = "' . $_GET['step' . $i] . '"';
  //print 'SQL: ' . $sql . '<br />';
            if (!mysqli_query($dbc, $sql))
            {
              print 'Unable to remove location<br />';
              die();
            }
          }
        }
      }

      // get a list of all jobs in the database
      $sql = 'select name from jobs';
      $rs = mysqli_query($dbc, $sql);
      if (mysqli_num_rows($rs) > 0)
      {
        $row_counter = 0;
        $header_not_printed = true;
        $no_ghosts_found = true;
        // use the list of job names from the first query to provide table names for the second search
        while ($row = mysqli_fetch_array($rs))
        {
          $sql2 = 'select step_number as step from `' . $row['name'] . '` where station not in (select id from routing)';
          $rs2 = mysqli_query($dbc, $sql2);
          
          // if we found some ghosts, ask the user to remove them
          if (mysqli_num_rows($rs2))
          {
            $no_ghosts_found = false;
            if ($header_not_printed)
            {
              print'<div id="instructions">
                      The following locations do not appear on the List Locations screen because they are not  linked to a station.<br /><br />
                      When the FIX GHOSTS button is clicked, all selected locations will be removed from the database.<br /><br />
                    </div>';

              print '<form action="fix_ghost_steps.php" method="get">';
              print '<input type="submit" id="fix_ghosts_btn" name="fix_ghosts_btn" value="FIX GHOSTS">&nbsp;';
          
              print 'Check/Uncheck all ghost steps: <input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
              print '<table id="step_table" name="step_table">';
              print '<th>Fix?</th>
                     <th>Job Name</th>
                     <th>Job Step</th>';
              $header_not_printed = false;
            }
            while($row2 = mysqli_fetch_array($rs2))
            {
              print '<tr>
                       <td style="text-align: center;">
                         <input type="checkbox" id="check' . $row_counter . '" name="check' . $row_counter . '">
                       </td>
                       <td>' . $row['name'] . '
                         <input type="hidden" id="name' . $row_counter . '" name="name' . $row_counter . '" value="' . $row['name'] . '">
                       </td>
                       <td style="text-align: center;">' . $row2['step'] . '
                         <input type="hidden" id="step' . $row_counter . '" name="step' . $row_counter . '" value="' . $row2['step'] . '">
                       </td>
                     </tr>';
              $row_counter++;
            }
          }
        }
        if (!$header_not_printed)
        {
          print '</table>';
          print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
        
          print '</form>';
        }
      }
      // if no ghosts found, say socket_accept
      if ($no_ghosts_found)
      {
        print 'No Job Steps with bad links detected';
      }
    ?>

</body>
</html>


