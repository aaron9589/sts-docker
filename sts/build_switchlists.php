<html>
  <head>
    <title>STS - Assign Cars to Jobs/Trains</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      function go_to_auto_assign()
      {
        $job = document.getElementById("auto_assign_job").value;
        location.href = "auto_assign.php?job=" + $job;
      }
    </script>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
      
      // bring in the utility files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

    ?>
  </head>
  <body style="margin-left: 50px;">
    <p>
      <img src="ImageStore/GUI/Menu/operations.jpg" width="716" height="145" border="0" usemap="#Map2">
      <map name="Map2">
      <area shape="rect" coords="568,5,712,46" href="index.html">
      <area shape="rect" coords="570,97,710,138" href="index-t.html">
      <area shape="rect" coords="568,52,717,93" href="operations.html">
    </map>
    </p>
  <h2>Simulation Operations</h2>
  <table>
    <tr>
      <th>Assign Individual Cars to<br />Jobs/Trains station-by-station</th>
      <th>Use the Auto-Assign function<br /> to assign car to Jobs/Trains</th>
    </tr>
    <tr>
      <td>
        Select a station where cars<br /> are to be assigned for pickup.
        <?php print drop_down_stations('station_list', '', 'get_cars_and_jobs();'); ?>
      </td>
      <td>
        Select a job/train and then<br /> click the AUTO-ASSIGN button.
        <?php print drop_down_jobs("auto_assign_job", 2, "") . '&nbsp;&nbsp;'; ?>
        <input type="button" name="auto_assign_btn" value="AUTO-ASSIGN" onclick="go_to_auto_assign();"
          style="background-color: #ffff00; font-size: 24px;">
      </td>
    </tr>
  </table>
    <form action="build_switchlists.php" method="POST">
    <?php
      // get a database connection
      $dbc = open_db();

      // was the Build button clicked?
      if ((isset($_POST['build_btn'])) && (isset($_POST['row_count'])))
      {
        // get the number of rows that were on the page
        $row_count = $_POST['row_count'];

        // mark the cars selected for pickup by the user
        for ($i=0; $i<$row_count; $i++)
        {
          // construct the list and car field names
          $list_name = 'job_list' . $i;
          $car_name = 'car' . $i;

          // does the drop-down list have a job name in it?
          if (strlen($_POST[$list_name]) > 0)
          {
            // build a query to update the car's "handled_by" field
            $sql = 'update cars set handled_by_job_id = "' . $_POST[$list_name];
            $sql = $sql . '" where id = "' . $_POST[$car_name] . '"';

            if(!mysqli_query($dbc, $sql))
            {
              print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
            }
            
            // get the info that the history table needs
            $sql = 'select setting_value from settings where setting_name = "session_nbr"';
            $rs = mysqli_query($dbc, $sql);
            $row = mysqli_fetch_array($rs);
            $session_nbr = $row['setting_value'];
            
            $sql = 'select current_location_id from cars where id = "' . $_POST[$car_name] . '"';
            $rs = mysqli_query($dbc, $sql);
            $row = mysqli_fetch_array($rs);
            $location = $row['current_location_id'];
            
            $sql = 'select name from jobs where id = ' . $_POST[$list_name];
//print 'SQL: ' . $sql . ' $_POST[$list_name]; ' . $_POST[$list_name] . '<br /><br />';        
            $rs = mysqli_query($dbc, $sql);
            $row = mysqli_fetch_array($rs);
            $job_name = $row['name'];

            // insert a car history record
            $sql = 'insert into history(car_id, session_nbr, event_date, event, location)
                    values ("' . $_POST[$car_name] . '", 
                            "' . $session_nbr . '", 
                            "' . date("Y-m-d H:i:s") . '", 
                            "Assigned to Job ' . $job_name . '", 
                            "' . $location . '")';
                            
            if (!mysqli_query($dbc, $sql))
            {
              print 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
            }
          }
        }       
      }
    ?>
    <br /><br />
    <div id="nothing_to_move" style="visibility: hidden;">
    There are no cars at this location that are ready to move.
    </div>
    <div id="instructions" style="visibility: hidden;">
    Select which job will pick up each of the cars and then click on the <b>ASSIGN</b> button.<br /> 
    The cars wll be added to the selected job for pick up at this location.<br /><br />
    If the job column is left blank, the car will remain in place.<br /><br />
    The next destination in each car's route is displayed in <b>bold</b> text<br /><br />
    <input id="build_btn" name="build_btn" value="ASSIGN" type="submit" disabled 
     style="background-color: #80ff00; font-size: 24px;"><br /><br />
    </div>
    <div id="car_table_div">
      <!-- the guts of the table are filled in by the HttpRequest call-back function -->
    </div>
    </form>
  </body>

    <script>
      // this javascript routine makes an HttpRequest that provides a list of cars at the selected
      // station and each car will have a drop-down list of jobs that could add it to their pickup switchlist

      function get_cars_and_jobs()
      {
        // check to see if the selection from the station list is non-blank
        if (document.getElementById('station_list').value.length > 0)
        {
          // enable the build button
          document.getElementById("build_btn").disabled = false;
          
          // submit the request for the cars at the selected station
          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function()
          {
            if (this.readyState == 4 && this.status == 200)
            {
               populate_car_table(this);
            }
          }
          var url = 'get_cars_at_station.php?station=' + encodeURIComponent(document.getElementById('station_list').value);
          xmlhttp.open('GET', url, true);
          xmlhttp.send();
        }
      };

      // this is the call back function for the list of cars at the selected station
      function populate_car_table(xmlhttp)
      {
        if (xmlhttp.responseText == "None")
        {
          // make the instruction block invisible
          document.getElementById("instructions").style.visibility = "hidden";

          // tell the user that there aren't any cars at this location that are ready to move
          document.getElementById("nothing_to_move").style.visibility = "visible";
          
          // hide the table that doesn't contain any cars
          document.getElementById("car_table_div").style.visibility = "hidden";
        }
        else
        {
          // make the instruction block visible
          document.getElementById("instructions").style.visibility = "visible";
          
          // hide the "nothing to move" div
          document.getElementById("nothing_to_move").style.visibility = "hidden";

          // display the table being returned from the server
          document.getElementById("car_table_div").innerHTML = xmlhttp.responseText;
          document.getElementById("car_table_div").style.visibility = "visible";
        }
      }

    </script>

</html>
