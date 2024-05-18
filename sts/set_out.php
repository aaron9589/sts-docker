<html>
  <head>
    <title>STS - Set-out Cars</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
      pre {white-space: pre-wrap;}
      @media print
      {
        .noprint {display:none;}
      }
    </style>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
    ?>
  </head>
  <body style="margin-left: 50px;">
    <div class="noprint">
      <div id="debug"> 
        <p><img src="ImageStore/GUI/Menu/operations.jpg" width="716" height="145" border="0" usemap="#Map2">
          <map name="Map2">
            <area shape="rect" coords="568,5,712,46" href="index.html">
            <area shape="rect" coords="570,97,710,138" href="index-t.html">
            <area shape="rect" coords="568,52,717,93" href="operations.html">
          </map>
        </p>
        <p>&nbsp; </p>
      </div>
    </div>
    <h2>Simulation Operations</h2>
    <h3>Set Out Cars</h3>
    <form action="set_out.php" method="get">
    <?php
      // bring in the utility files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // get a database connection
      $dbc = open_db();

      // get the current session number
      $sql = 'select setting_value from settings where setting_name = "session_nbr"';
      $rs = mysqli_query($dbc, $sql);
      $row = mysqli_fetch_array($rs);
      $current_session = $row[0];

      // was the Finish button clicked?
      if (isset($_GET['finish_btn']))
      {
        // only try to close out a switchlist if there was one to be closed out in the first place
        if (isset($_GET['row_count']))
        {
          // get the number of rows that were on the page
          $row_count = $_GET['row_count'];

          // update the current location of the cars as specified by the user
          for ($i=0; $i<$row_count; $i++)
          {
            // construct the list and car field names
            $list_name = 'station_list' . $i;
            $car_name = 'car' . $i;

            // does the drop-down list have a job name in it?
            if (strlen($_GET[$list_name]) > 0)
            {
              // before marking the car as set out, save the job that is handling it for the history file
              $sql = 'select jobs.name as job_name
                      from jobs, cars
                      where cars.id = "' . $_GET[$car_name] . '"
                        and jobs.id = cars.handled_by_job_id';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $job_name = $row['job_name'];
//print 'SQL: ' . $sql . ' / job name: ' . $job_name . '<br /><br />';              
              // build a query to update the car's current location field, remove the contents of it's "handled_by" field, and set it's position to 0
              $sql = 'update cars
                      set current_location_id = "' . $_GET[$list_name] . '",
                          handled_by_job_id = 0,
                          position = "0"
                      where id = "' . $_GET[$car_name] . '"';
  // print 'SQL: ' . $sql . '<br /><br />';
              if(!mysqli_query($dbc, $sql))
              {
                print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
              }

              // get the info that the history table needs
              $sql = 'select setting_value from settings where setting_name = "session_nbr"';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $session_nbr = $row['setting_value'];
              
              $sql = 'select current_location_id from cars where id = "' . $_GET[$car_name] . '"';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $location = $row['current_location_id'];
//print 'SQL: ' . $sql . ' Location: ' . $location . '<br /><br />';        
              // insert a car history record
              $sql = 'insert into history(car_id, session_nbr, event_date, event, location)
                      values ("' . $_GET[$car_name] . '", 
                              "' . $session_nbr . '", 
                              "' . date("Y-m-d H:i:s") . '", 
                              "Set out by Job ' . $job_name . '", 
                              "' . $location . '")';
                              
              if (!mysqli_query($dbc, $sql))
              {
                print 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
              }



              // build a query to update an "ordered" car's status if it is at it's loading location
              // and update "last_spotted" to the current session number
              $sql = 'update cars,
                             car_orders,
                             shipments
                      set cars.status = "Loading",
                          cars.last_spotted = "' . $current_session . '"
                      where cars.id = "' . $_GET[$car_name] . '"
                        and cars.status = "Ordered"
                        and car_orders.car = cars.id
                        and car_orders.shipment = shipments.id
                        and cars.current_location_id = shipments.loading_location';
// print 'SQL: ' . $sql . '<br /><br />';
              if(!mysqli_query($dbc, $sql))
              {
                print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
              }

              // build a query to update a "Loaded" car's status if it is at it's unloading location
              // and update "last_spotted" to the current session number
              $sql = 'update cars,
                             car_orders,
                             shipments
                      set cars.status = "Unloading",
                          cars.last_spotted = "' . $current_session . '"
                      where cars.id = "' . $_GET[$car_name] . '"
                        and cars.status = "Loaded"
                        and car_orders.car = cars.id
                        and car_orders.shipment = shipments.id
                        and cars.current_location_id = shipments.unloading_location';
// print 'SQL: ' . $sql . '<br /><br />';
              if(!mysqli_query($dbc, $sql))
              {
                print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
              }

              // build a query to update the car's status if it is a non-revenue move and at it's destination
              $sql = 'update cars,
                             car_orders
                      set cars.status = "Empty"
                      where car_orders.car = cars.id
                        and car_orders.waybill_number like "___-E__"
                        and cars.status = "Ordered"
                        and cars.current_location_id = car_orders.shipment
                        and cars.id = "' . $_GET[$car_name] . '"';
                        
// print 'SQL: ' . $sql . '<br /><br />';
              if(!mysqli_query($dbc, $sql))
              {
                print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
              }
              else
              {
// print 'Updated rows = ' . mysqli_affected_rows($dbc) . '<br /><br />';
                if (mysqli_affected_rows($dbc) > 0)
                {
                  // if the repositioned car was successfully changed from "Ordered" to "Empty", remove it's car order
                  $sql = 'delete from car_orders where car = "' . $_GET[$car_name]. '"';
                  if (!mysqli_query($dbc, $sql))
                  {
                    print 'Delete Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
                  }
                  else
                  {
                    $sql2 = 'select reporting_marks from cars where id = ' . $_GET[$car_name];
                    $rs2 = mysqli_query($dbc, $sql2);
                    $row2 = mysqli_fetcH_array($rs2);
                    print $row2['reporting_marks'] . ' has been successfully repositioned and is now ready for further use.<br />';
                  }
                }
              }
              
              // check to see if there are any cars that have just been set to "Loading" or "Unloading" where the applicable
              // shipment has min & max load & unload times of zero
              // if so, bump their status to the next setting
              
              // first get the min & max load & unload values for the shipment associated with this car
              $sql = 'select shipments.min_load_time as min_load_time,
                              shipments.max_load_time as max_load_time,
                              shipments.min_unload_time as min_unload_time,
                              shipments.max_unload_time as max_unload_time,
                              cars.status as status
                         from shipments, car_orders, cars
                        where car_orders.shipment = shipments.id
                          and car_orders.car = "' . $_GET[$car_name] . '"
                          and cars.id = "' . $_GET[$car_name] . '"';

              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
// print 'SQL: ' . $sql . '<br /><br />';
              // convert blank min & max load & unload times to an integer value
                $min_load_time = (int)$row['min_load_time'];
                $max_load_time = (int)$row['max_load_time'];
                $min_unload_time = (int)$row['min_unload_time'];
                $max_unload_time = (int)$row['max_unload_time'];
              
              // if the car's status is "Loading" and it's shipment has negative values for either the min or max loading time,
              // change it's status to "Loaded"
              if (($row['status'] == "Loading") && (($min_load_time < 0) || ($max_load_time < 0)))
              {
                $sql2 = 'update cars set status = "Loaded", last_spotted = "0" where id = "' . $_GET[$car_name] . '"';
                if (!mysqli_query($dbc, $sql2))
                {
                  print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql2;
                }
              }
              
              // likewise if the car's status is "Unloading" and it's shipment has negative values for either min or max unloading time,
              // change it's status to "Empty"
              if (($row['status'] == "Unloading") && (($min_unload_time < 0) || ($max_unload_time < 0)))
              {
                $sql2 = 'update cars set status = "Empty", last_spotted = "0" where id = "' . $_GET[$car_name] . '"';
                if (!mysqli_query($dbc, $sql2))
                {
                  print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql2;
                }
                // for the cars with this new status, delete the car orders linked to them
                $sql2 = 'delete from car_orders where car = "' . $_GET[$car_name]. '"';
                if (!mysqli_query($dbc, $sql2))
                {
                  print 'Delete Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql2;
                }
              }
            }
          }
        }
      }
      print '<div class="noprint">';
      // choose to display all set out location possibilities for the selected job or only the default locations
      print '<br /><input type="checkbox" name="default_loc" id="default_loc" onchange="reset_job();">&nbsp;Check to display default set-out locations only</br />';

      // generate the list of jobs from which the user can choose
      print '<br />Select a job to do do the setouts</br /><br />';
      print drop_down_jobs("job_list", '', "get_jobs_and_cars();");
    ?>
      <!-- generate a print button -->
      <button onclick="window.print()" style="background-color: #ffff00; font-size: 24px;">PRINT</button>&nbsp;&nbsp;
      <br /><br />
      <div id="instructions" style="visibility: hidden;">
      Mark where each car was left by selecting it's set-out location from it's drop-down list.<br /><br />
      To update the current location of each car that was set out, click the <b>SET OUT</b> button.<br /><br />
      After placing the cars, click <a href="organize_cars.php">here</a> to update the positions of the cars at the setout location<br />
      as well as those remaining in the train.<br /><br />
      <input id="finish_btn" name="finish_btn" value="SET OUT" type="submit" disabled style="background-color: #80ff00; font-size: 24px;"><br /><br />
      </div>
    </div>
    <div id="job_table_div">
      <!-- the guts of the table are filled in by the HttpRequest call-back function -->
    </div>
    </form>
  </body>

    <script>
      // this script resets the drop down job list when the default set-out location checkbox is clicked
      function reset_job()
      {
        document.getElementById('job_list').selectedIndex = "0";
        document.getElementById('job_table_div').innerHTML = "";
      }
      
      // this javascript routine makes an HttpRequest that provides a list of cars in the selected
      // train and each car will have a drop-down list of locations where it could set out

      function get_jobs_and_cars()
      {
        // check to see if the job list has a job selected
        if (document.getElementById('job_list').value.length > 0)
        {
          // enable the finish button
          document.getElementById("finish_btn").disabled = false;
          
          // submit the request for the cars at the selected station
          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function()
          {
            if (this.readyState == 4 && this.status == 200)
            {
               populate_job_table(this);
            }
          }
          // set a flag to send the value of the checkbox through
          if (document.getElementById('default_loc').checked == true)
          {
            var default_flag = 'Y';
          }
          else
          {
            var default_flag = 'N';
          }
          var url = 'get_cars_in_job.php?job=' + encodeURIComponent(document.getElementById('job_list').value) + '&default_loc=' + encodeURIComponent(default_flag);
          // alert(url);
          xmlhttp.open('GET', url, true);
          xmlhttp.send();
        }
      };

      // this is the call back function for the list of cars in the selected train
      function populate_job_table(xmlhttp)
      {
        if (xmlhttp.responseText == "None")
        {
          // get the name of the selected job
          job_name = document.getElementById("job_list").value;
          
          // tell the user that there aren't any cars in this job
          document.getElementById("job_table_div").innerHTML = "<tr><td>The switchlist for " + job_name + " doesn't contain any cars.</td></tr>";
        }
        else
        {
          // make the instruction block visible
          document.getElementById("instructions").style.visibility = "visible";
          
          // display the table being returned from the server
          document.getElementById("job_table_div").innerHTML = xmlhttp.responseText;
        }
      }

    </script>

</html>
