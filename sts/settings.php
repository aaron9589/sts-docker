<html>
  <head>
    <title>STS - System Settings</title>
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

<h3>
Current STS server IPv4 address(s):<br />
</h3>
<?php
  // determine what kind of OS is running
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
  {
    // find the STS server's ip address if it's running via XAMPP on a Windows PC
    $ipconfig_output = shell_exec("ipconfig");
    $ipconfig_array = explode('<br />', nl2br($ipconfig_output));
    for ($i=0; $i<sizeof($ipconfig_array); $i++)
    {
      if (strpos($ipconfig_array[$i], 'adapter') > 0)
      {
        $adapter_name = $ipconfig_array[$i];
      }
      if (strpos($ipconfig_array[$i], 'IPv4') > 0)
      {
        print $adapter_name . ' ' . $ipconfig_array[$i] . '<br />';
      }
    }
  }
  else
  {
    // try to find the STS server's ip address if it's running plain Apache/PHP on a *nix box
    print $_SERVER['SERVER_ADDR'];
  }
?>
</h3>

<h3>System Settings</h3>
    <form>
      <div id="instructions">
        After modifying setting values, click the <b>UPDATE</b> button to put the new<br />
        values into effect.<br /><br />
        When entering a new value for the Print Width setting, be certain to follow<br />
        the numerical value with a valid unit of measurement, such as in for inches,<br />
        cm for centimeters, etc... A blank space between the numeric value and the<br />
        unit of measurement will generate an error.<br /><br />
        Examples - Legal: 7.5in &nbsp;&nbsp; Illegal: 7.5 in<br /><br />
        7.5in is the default setting for US letter size paper set to print in portrait<br />
        mode and 190mm is correct for A4.
      </div>
      <br />
      <input name="update_btn" value="UPDATE" type="submit"><br /><br />

      <?php
        // display a list of settings and update the settings values if the user clicks the Update button

        // bring in the utility files
        require "open_db.php";

        // get a database connection
        $dbc = open_db();

        // was the Update button clicked?
        if (isset($_GET["update_btn"]))
        {
          // collect the names of all of the settings
          $row_count = 0;
          $sql = 'select setting_name from settings';
          $rs = mysqli_query($dbc, $sql);
          while ($row = mysqli_fetch_array($rs))
          {
            $settings[$row_count] = $row[0];
            $row_count++;
          }

          // now go through the array of setting names and update any changes
          for ($i=0; $i<$row_count; $i++)
          {
            if (strlen($_GET[$settings[$i]]) > 0)
            {
              // only make a change if there's a new value
              $setting_value = $_GET[$settings[$i]];
              $sql = 'update settings set setting_value = "' . $setting_value . '" where setting_name = "' . $settings[$i] . '"';
              if (!mysqli_query($dbc, $sql))
              {
                print "Update Error: " . mysqli_error($dbc) . " SQL: " . $sql;
              }
            }
          }

        }

        // build an sql query to bring in the settings and their values
        $sql = 'select * from settings';
        $rs = mysqli_query($dbc, $sql);

        if (mysqli_num_rows($rs) > 0)
        {
          print '<table>';
          print '<tr><th>Setting</th><th>Current Value</th><th>New Value</th></tr>';

          while ($row = mysqli_fetch_array($rs))
          {
            print '<tr>
                     <td>' . $row[1] . '</td>
                     <td>' . $row[2] . '</td>
                     <td><input name="' . $row[0] . '" type="text"></td>
                   </tr>';
          }

          print '</table>';
        }
        else
        {
          print "No settings found. Curious... There should be some here. :-(";
        }
      ?>
    </form>
  </body>
</html>
