<html>
  <head>
    <title>STS - Reset System</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
  </head>
  <body>
<img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
<map name="Map3">
  <area shape="rect" coords="567,5,710,47" href="index.html">
  <area shape="rect" coords="568,98,708,142" href="index-t.html">
  <area shape="rect" coords="567,54,711,92" href="db-maint.html">
</map>
<h2>Database Maintenance</h2>
    <h3>Reset Simulation</h3>

    <?php
      // this program restarts all shippers, cancels waybills, and sets all cars to "Empty-Available"
      // it also places all cars at their home location if one is specified, otherwise it leaves them where they are
      // it also sets all car load counts to 0 (zero)

      // bring in the function files
      require 'open_db.php';

      // get a database connection
      $dbc = open_db();

      // initialize a message
      $msg = '';

      // was the "Restart" button clicked?
      if (isset($_GET['restart_btn']))
      {
        // restart the shipments
        $sql = 'update shipments set last_ship_date = 0';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg = 'All shippers restarted... <br /><br />';
        }

        // remove all car orders
        $sql = 'delete from car_orders';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Delete Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'All car orders canceled... <br /><br />';
        }

        // set all cars not listed as Unavailable to "Empty" and remove any "handled_by" information 
        $sql = 'update cars set status = "Empty", handled_by_job_id = "0" where status != "Unavailable"';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'All available cars set to Empty... <br /><br />';
        }
        
        // set all car locations to their home location if they have one
        $sql = 'update cars set current_location_id = home_location, handled_by_job_id = "0", last_spotted = "0" where home_location >= 1';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'Cars with home locations repositioned...<br /><br />';
        }

        // set all car load counts to 0 (zero)
        $sql = 'update cars set load_count = 0';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'Car load counts set to 0 (zero)...<br /><br />';
        }

        // set the operating session to 0
        $sql = 'update settings set setting_value = "0" where setting_name = "session_nbr"';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'Operating session number set to 0... <br /><br />';
        }
      }
    ?>

    Click the check box to activate the <b>RESET</b> button<br />
    Click the <b>RESET</b> button to restart all shippers, cancel all waybills, set all cars to "Empty-Available",<br />
    reposition all cars to their home locations if they have one specified, and set all car load counts to 0 (zero).
    <br /><br />
    <script>
      function toggle_restart_btn()
      {
        if (document.getElementById("restart_btn").disabled)
          document.getElementById("restart_btn").disabled = false;
        else
          document.getElementById("restart_btn").disabled = true;
      }
    </script>
    <form method="get" action="reset.php">
      <input id="reset_check_box" value="reset" type="checkbox" onclick="toggle_restart_btn();">
      Yes, I'm certain I want to reset the simulation!<br /><br />
      <input id="restart_btn" name="restart_btn" value="RESET" type="submit" disabled><br /><br />
    </form>
    <?php print $msg; ?>
  </body>
</html>
