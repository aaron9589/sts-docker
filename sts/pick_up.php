<html>
  <head>
    <title>STS - Pick-up Cars</title>
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
    <script>
      // this javascript function is triggered by the user changing the "All" checkbox
      function checkall()
      {
        var row_count = document.getElementById('job_table').rows.length-1;
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
      </div>
    </div>
    <h2>Simulation Operations</h2>
    <h3>Pick Up Cars</h3>
    <div class="noprint">
    Select a job to do do the pickups</br /><br />
    </div>
    <form action="pick_up.php" method="get">
    <?php
      // bring in the utility files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // get a database connection
      $dbc = open_db();

      // was the Finish button clicked?
      if (isset($_GET['finish_btn']))
      {
        // only try to pick cars if there were some to be picked up in the first place
        if (isset($_GET['row_count']))
        {
          // get the number of rows that were on the page
          $row_count = $_GET['row_count'];

          // update the position (set to 1) of all cars with a checkmark
          for ($i=0; $i<$row_count; $i++)
          {
            // construct the list and car field names
            $check_name = 'check' . $i;
            $car_name = 'car' . $i;

            // go through all of the check boxes and for cars with a checkmark set their position to 1
            if (isset($_GET[$check_name]))
            {
              // save the car's current location to give to the history file after the car's been picked up
              $sql = 'select current_location_id from cars where id = "' . $_GET[$car_name] . '"';
//print 'SQL: ' . $sql . '<br /><br />';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $location = $row['current_location_id'];
//print 'location: ' . $location . '<br /><br />';              
              /*            // and it's position to 0 (zero) so they appear at the top of the list when reorganizing the car order
                            $sql = 'update cars
                                    set current_location_id = "0",
                                        position="0"
                                    where id = "' . $_GET[$car_name] . '"';
              */
              
              // build a query to set the car's current location to 0 (zero) indicating that it's in a train              
              // don't set the position to 0 because that undoes any organization performed by the user
              $sql = 'update cars
                      set current_location_id = "0"
                      where id = "' . $_GET[$car_name] . '"';

              if(!mysqli_query($dbc, $sql))
              {
                print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br />';
              }

              // get the info that the history table needs
              $sql = 'select setting_value from settings where setting_name = "session_nbr"';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $session_nbr = $row['setting_value'];
              
              $sql = 'select jobs.name as job_name 
                        from jobs, cars
                       where cars.id = "' . $_GET[$car_name] . '" and jobs.id = cars.handled_by_job_id';
//print 'SQL: ' . $sql . '<br /><br />';
              $rs = mysqli_query($dbc, $sql);
              $row = mysqli_fetch_array($rs);
              $job_name = $row['job_name'];
        
              // insert a car history record
              $sql = 'insert into history(car_id, session_nbr, event_date, event, location)
                      values ("' . $_GET[$car_name] . '", 
                              "' . $session_nbr . '", 
                              "' . date("Y-m-d H:i:s") . '", 
                              "Picked up by Job ' . $job_name . '", 
                              "' . $location . '")';
                              
              if (!mysqli_query($dbc, $sql))
              {
                print 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
              }              
            }
          }
        }
      }
      print '<div class="noprint">';
      // generate the list of jobs from which the user can choose
      print drop_down_jobs("job_list", '', "get_jobs_and_cars();");
      print '&nbsp;<input id="finish_btn" name="finish_btn" value="PICK UP" type="submit" disabled
             style="background-color: #80ff00; font-size: 24px;">&nbsp;';
    ?>
      <!-- generate a print button -->
      <button onclick="window.print()" style="background-color: #ffff00; font-size: 24px;">PRINT</button>&nbsp;&nbsp;
      <br /><br />
      <div id="instructions" style="visibility: hidden;">
      Mark the cars that have been picked up with check marks and then click the <b>PICK UP</b> button.<br /><br />
      After picking up the cars, click <a href="organize_cars.php"><b>here</b></a> to update the positions of the cars in the train.<br /><br />
      Click <a href="display_switchlist.php"><b>here</b></a> to generate an updated switch list if desired.
      </div>
    </div>
    <br />
    <div id="job_table_div">
      <!-- the guts of the table are filled in by the HttpRequest call-back function -->
    </div>
    </form>
  </body>

    <script>
      // this javascript routine makes an HttpRequest that provides a list of cars in the selected
      // job including a checkbox that will indicate that the car was picked up by the job

      function get_jobs_and_cars()
      {
        // check to see if the job list has a job selected
        if (document.getElementById('job_list').value.length > 0)
        {
          // enable the pick up button
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
          var url = 'get_cars_position_in_job.php?job=' + encodeURIComponent(document.getElementById('job_list').value);
          xmlhttp.open('GET', url, true);
          xmlhttp.send();
        }
      };

      // this is the call back function for the list of cars at the selected station
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
