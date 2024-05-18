<html>
  <head>
    <title>STS - Restart System</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
  </head>
  <body>
<p><img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
  <map name="Map3">
    <area shape="rect" coords="567,5,710,47" href="index.html">
    <area shape="rect" coords="568,98,708,142" href="index-t.html">
    <area shape="rect" coords="567,54,711,92" href="db-maint.html">
  </map>
</p>
<h2>Database Maintenance</h2>
    <h3>Restart Simulation</h3>

    <?php
      // this program restarts all shippers, cancels waybills, and sets all cars to "Empty-Available"

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
        $sql = 'truncate table car_orders';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Truncate Table Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'All car orders canceled... <br /><br />';
        }

        // set all cars not listed as Unavailable to "Empty" and remove any "handled_by" information 
        $sql = 'update cars set status = "Empty", handled_by_job_id = "0", last_spotted = "0" where status != "Unavailable"';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'All available cars set to Empty... <br /><br />';
        }
        
        // if a car is currently in a train, set it's current location to it's home location if it has one
        $sql = 'update cars set current_location_id = home_location where home_location >= 1';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
        }
        else
        {
          $msg .= 'Cars in trains with home locations repositioned... <br /><br />';
          $msg .= 'Cars in trains without home locations must be repositioned manually...<br /><br />';
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

    Click the check box to activate the <b>RESTART</b> button<br />
    Click the <b>RESTART</b> button to restart all shippers, cancel all waybills, and set all cars to "Empty-Available"
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
    <form method="get" action="restart.php">
      <input id="reset_check_box" value="reset" type="checkbox" onclick="toggle_restart_btn();">
      Yes, I'm certain I want to restart the simulation!<br /><br />
      <input id="restart_btn" name="restart_btn" value="RESTART" type="submit" disabled><br /><br />
    </form>
    
<?php print $msg; ?> 
</body>
</html>
