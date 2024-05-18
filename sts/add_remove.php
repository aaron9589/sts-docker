<html>
  <head>
    <title>STS Club Ops - Rolling Stock Management</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top;}
      th {border: 1px solid black; padding: 10px;}
      td {border: 1px solid black; padding: 10px;}
      td.checkbox {text-align: center;}
    </style>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
    ?>
    <script>
      // this javascript function is triggered by the user changing the "All" checkbox
      function checkall()
      {
        var row_count = document.getElementById('car_table').rows.length-1;
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
<p><img src="ImageStore/GUI/Menu/club_operations.jpg" width="716" height="147" border="0" usemap="#Map4"> 
  <map name="Map4">
    <area shape="rect" coords="570,3,709,49" href="index.html">
    <area shape="rect" coords="567,94,709,141" href="index-t.html">
    <area shape="rect" coords="569,53,710,93" href="club_index.html">
  </map>
</p>
<h2>Fleet Management</h2>
<h3>Add and/or Remove Rolling Stock from the Railroad</h3>
  <?php
      // connect to the database using the credentials from sts04
      require 'open_db.php';
      $dbc = open_db();
      
      // let's see if the owners and ownership tables exist
      $sql = 'show tables like "owners"';
      $rs = mysqli_query($dbc, $sql);
      $row = mysqli_fetch_row($rs);
      if ($row[0] != "owners")
      {
        print 'You need to set up the car owners prior to adding or removing cars.';
        die();
      }
      
      $sql = 'show tables like "ownership"';
      $rs = mysqli_query($dbc, $sql);
      $row = mysqli_fetch_row($rs);
      if ($row[0] != "ownership")
      {
        print 'You need to link cars and owners prior to adding or removing cars.';
        die();
      }

      // get a list of of the owners for use later
      $sql = 'select id, name from owners order by name';
      if (!$rs = mysqli_query($dbc, $sql))
      {
        print 'Unable to query the owners table. You need to set up the car owners.';
        die();
      }
      $owners = array();
      $owner_count = 0;
      while ($row = mysqli_fetch_array($rs))
      {
        $owners[$owner_count][0] = $row[0];
        $owners[$owner_count][1] = $row[1];
        $owner_count++;
      }
      
      // if we got here via a click on the Update button, run through each car and update it's on/off railroad setting
      // and if it's being taken off the railroad, clean up any car order, location, etc... information
      if (isset($_POST['update']))
      {
        for ($i = 0; $i < $_POST['row_count']; $i++)
        {
          // take a look at each checkbox
          if (isset($_POST['check' . $i]))
          {
            // if the checkbox is checked, take a look at the car's on_off_rr flag to see if we need to add it to the railroad
            // -- if the car is off the railroad, mark it as on the railroad and restore it's status from the ownership table
            // -- if the status is anything else, it is still on the railroad so don't do anything
            if ($_POST['on_off_rr' . $i] != 'on')
            {
              // run a query to restore the car's status from what's in the on_off_rr field of the ownership table
              // this removes the "Unavailable" flag in it's status field
              $sql = 'update cars set status = "' . $_POST['on_off_rr' . $i] . '" where id = ' . $_POST['car_id' . $i];
              if (!$rtncd = mysqli_query($dbc, $sql))
              {
                print 'Unable to restore car status';
              }
              
              // update the car's on_off_rr flag to indicate that the car is back on the railroad
              $sql = 'update ownership set on_off_rr = "on" where car_id = "' . $_POST['car_id' . $i] . '"';
              if (!$rtncd = mysqli_query($dbc,$sql))
              {
                print 'Unable to move car back onto the railroad';
              }
            }
          }
          else
          {
            // if the checkbox isn't checked, see if we need to remove the car from the railroad
            // -- if the car's on_off_rr flag isn't "on", that means that the car is off the railroad so don't do anything
            // -- if the car's status is anything else, it needs to be removed from the railroad so save it's current status
            //    in the on_off_rr field of the ownership table
            if ($_POST['on_off_rr' . $i] == 'on')
            {
              // save the car's current status in the on_off_rr field
              $sql = 'update ownership set on_off_rr = "' . $_POST['status' . $i] . '" where ownership.car_id = "' . $_POST['car_id' . $i] . '"';
              if (!$rtncd = mysqli_query($dbc, $sql))
              {
                print 'Unable to update car on_off_rr status';
              }
              
              // set the car's status to "Unavailable"
              $sql = 'update cars set status = "Unavailable" where id = ' . $_POST['car_id' . $i];
              if (!$rtncd = mysqli_query($dbc, $sql))
              {
                print 'Unable to move car off the railroad';
              }
            }
          }
        }
      }
      
      // if we got here via a click on the Select Owner button, display cars owned by that person
      // otherwise, display all of the cars
      if (isset($_POST['select']))
      {
        // if the owner selected is "ALL", display all of the cars, otherwise display those
        // owned by the person selected
        if ($_POST['selected_owner'] == 'ALL')
        {
          $sql = 'select distinct cars.id as car_id,
                         cars.reporting_marks as reporting_marks,
                         cars.status as status,
                         car_codes.code as car_code,
                         jobs.name as job_name,
                         loc01.code as current_location,
                         loc02.code as home_location,
                         owners.name as owners_name,
                         ownership.on_off_rr as on_off_rr
                    from cars
                    left join car_codes on cars.car_code_id = car_codes.id
                    left join jobs on cars.handled_by_job_id = jobs.id
                    left join locations loc01 on cars.current_location_id = loc01.id
                    left join locations loc02 on cars.home_location = loc02.id
                    left join ownership on ownership.car_id = cars.id
                    left join owners on owners.id = ownership.owner_id
                    order by cars.reporting_marks';
        }
        else
        {
          $sql = 'select distinct cars.id as car_id,
                         cars.reporting_marks as reporting_marks,
                         cars.status as status,
                         car_codes.code as car_code,
                         jobs.name as job_name,
                         loc01.code as current_location,
                         loc02.code as home_location,
                         owners.name as owners_name,
                         ownership.on_off_rr as on_off_rr
                    from cars
                    left join car_codes on cars.car_code_id = car_codes.id
                    left join jobs on cars.handled_by_job_id = jobs.id
                    left join locations loc01 on cars.current_location_id = loc01.id
                    left join locations loc02 on cars.home_location = loc02.id
                    left join ownership on ownership.car_id = cars.id
                    left join owners on owners.id = ownership.owner_id
                    where ownership.owner_id = ' . $_POST['selected_owner'] . '
                    order by cars.reporting_marks';
        }
      }
      else
      {
        // if we didn't get here via a click on the Select Owner button, just list all the cars
        $sql = 'select distinct cars.id as car_id,
                       cars.reporting_marks as reporting_marks,
                       cars.status as status,
                       car_codes.code as car_code,
                       jobs.name as job_name,
                       loc01.code as current_location,
                       loc02.code as home_location,
                       owners.name as owners_name,
                       ownership.on_off_rr as on_off_rr
                  from cars
                  left join car_codes on cars.car_code_id = car_codes.id
                  left join jobs on cars.handled_by_job_id = jobs.id
                  left join locations loc01 on cars.current_location_id = loc01.id
                  left join locations loc02 on cars.home_location = loc02.id
                  left join ownership on ownership.car_id = cars.id
                  left join owners on owners.id = ownership.owner_id
                  order by cars.reporting_marks';
      }
//print 'SQL: ' . $sql . '<br /><br />';      
      // run the query
      if (!$rs = mysqli_query($dbc, $sql))
      {
        print 'Unable to query the cars table';
      }
      
      // start a form
      print '<form method="POST" action="add_remove.php">';
      
      // display a drop-down list of all the owners and a Select button
      print 'Select owner and click on the SELECT button:&nbsp;';
      print '<select id="selected_owner" name="selected_owner">';
      print '<option value="ALL">ALL</option>';
      for ($i = 0; $i < $owner_count; $i++)
      {
        print '<option value="' . $owners[$i][0] . '">' . $owners[$i][1] . '</option>';
      }
      print '</select>';
      print '&nbsp;';
      print '<input type="submit" id="select" name="select" value="SELECT">';
      
      // add a 'check/uncheck all' checkbox that fires a javascript function
      print '<br /><br />Check/Uncheck all selected cars&nbsp;';
      print '<input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
      
      // put a Submit button on both the top and bottom of the table
      print '<input type="submit" id="update" name="update" value="UPDATE"><br /><br />';
      
      // set up the table
      print '<table id="car_table">
               <tr>
                 <th>Owner</th>
                 <th>On Railroad</th>
                 <th>Reporting Marks</th>
                 <th>Car Type</th>
                 <th>Status</th>
                 <th>Current Location</th>
                 <th>Handled by Job</th>
                 <th>Home Location</th>
               </tr>';      
      
      // run through all of the rows returned by the query
      $row_count = 0;
      while ($row = mysqli_fetch_array($rs))
      {
        // check the car's on_off_rr status to see where it is
        if ($row['on_off_rr'] != 'on')
        {
          $checked = '';
        }
        else
        {
          $checked = ' checked';
        }
        
        // generate one row per car
        if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['car_id'] . '.jpg'))
        {
          $parm_string = '\'' . $row['car_id'] . '\', \'' . $row['reporting_marks'] . '\'';
        }
        else
        {
          $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
        }
        print '<tr>';
        print '  <td>' . $row['owners_name'] . '</td>
                 <td style="text-align: center;">
                   <input type="checkbox" id="check' . $row_count . '" name="check' . $row_count . '"' . $checked . '>
                 </td>
                 <td onclick="show_image(' . $parm_string . ');">' . 
                   $row['reporting_marks'] . '
                   <input type="hidden" id="car_id' . $row_count . '" name="car_id' . $row_count . '" value="' . $row['car_id'] . '"
                 </td>
                 <td style="text-align: center;">' . $row['car_code'] . '</td>
                 <td>' . 
                   $row['status'] . '
                   <input type="hidden" id="status' . $row_count . '" name="status' . $row_count . '" value="' . $row['status'] . '">
                   <input type="hidden" id="on_off_rr' . $row_count . '" name="on_off_rr' . $row_count . '" value = "' . $row['on_off_rr'] . '">
                 </td>
                 <td>' . $row['current_location'] . '</td>
                 <td>' . $row['job_name'] . '</td>
                 <td>' . $row['home_location'] . '</td>
               </tr>';
        $row_count++;
      }
               
      print '</table>';
      
      // put row count into a hidden field so we can use it the next time we hit this form
      print '<input type="hidden" id="row_count" name="row_count" value="' . $row_count . '">';

      // put a Submit button on both the top and bottom of the table
      print '<br /><input type="submit" id="update" name="update" value="UPDATE">';
      print '</form>';      
    ?>
  <!-- <a href="index.html"><img src="../sts/ImageStore/Menu/Club-OPS-operations-sm.jpg" width="109" height="30" border="0"></a>  -->
</h2>
    </body>
</html>