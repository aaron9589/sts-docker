<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<html>
  <head>
    <title>STS - Load and/or Unload Cars</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top;}
      th {border: 1px solid black; padding: 10px;}
      td {border: 1px solid black; padding: 10px;}
      td.checkbox {text-align: center;}
    </style>
    <script>
      function show_image(car_id, reporting_marks)
      {
        if (car_id.length > 0)
        {
          // find the upper right corner of the browser window
          var upper_right_x = window.screenX + window.parent.outerWidth;
          var upper_right_y = window.screenY;
          
          // calculate the upper left corner of the new window
          var upper_left_x = upper_right_x - 700;
          var upper_left_y = upper_right_y + 30;
          
          // open the window
          window.open("./ImageStore/DB_Images/RollingStock/" + car_id + ".jpg", "image_window", "width=660,height=500,left=" + upper_left_x + ",top=" + upper_left_y);
        }
        else
        {
          alert("No photo available for " + reporting_marks);
        }
      }
    </script>
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
  <body style="margin-left: 50px;">
<p><img src="ImageStore/GUI/Menu/operations.jpg" width="716" height="145" border="0" usemap="#Map2">
  <map name="Map2">
    <area shape="rect" coords="568,5,712,46" href="index.html">
    <area shape="rect" coords="570,97,710,138" href="index-t.html">
    <area shape="rect" coords="568,52,717,93" href="operations.html">
  </map>
</p>
<h2>Simulation Operations</h2>
    
<h3>Load and/or Unload Cars</h3>
    <div id="instructions">
    The cars shown on this page are in the process of being loaded or unloaded. To complete the loading or unloading process, <br />
    click on the desired car's check box and then click the UPDATE button. Cars are color-coded based on their status.<br /><br />

    </div>
    <form method="POST" action="load_unload.php">
    <?php
      // this program displays all cars that are in the process of being loaded or unloaded and
      // updates all cars that have been checked off by the user

    print '<script type="text/javascript">
             function filter_rows(tbl_col, needle)
             {
               // confirm that a non-blank option has been selected
               if (needle.length > 0)
               {
                 // convert drop-down locations (station - location) to table locations (station\nlocation)
                 var hyphen_loc = needle.search(" - ");
                 if (hyphen_loc >= 0)
                 {
                   var new_needle = needle.substr(0, hyphen_loc) + "\n" + needle.substr(hyphen_loc + 3, needle.length);
                   needle = new_needle;
                 }

                 var table = document.getElementById("car_table");
                 
                 //iterate through rows
                 for (var i = 2, row; row = table.rows[i]; i++)
                 {
                   var haystack_length = row.cells[tbl_col].innerText.length;
                   var needle_length = needle.length;
                   var match_start = haystack_length - needle_length;
                   
                   var haystack = row.cells[tbl_col].innerText.substr(match_start);
                   
                   if (haystack != needle)
                   {
                     row.style.display = "none"
                   }
                 }
               }
             }
           </script>';

      // bring in the function files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // open a database connection
      $dbc = open_db();

      // get the current operation session
      $sql = 'select setting_value from settings where setting_name = "session_nbr"';
      $rs = mysqli_query($dbc, $sql);
      $row = mysqli_fetch_array($rs);
      $current_session = $row[0];

      // check to see if the Update button was clicked
      if (isset($_POST['update_btn']))
      {
        // go through each of the rows from the incoming page and if it's checkbox was checked, update it's status
        for ($i=0; $i<$_POST['row_count']; $i++)
        {
          $checkbox_name = "check" . $i;
          if (isset($_POST[$checkbox_name]))
          {
            // determine each car's new status
            $car_name = 'car' . $i;
            $status_name = 'status' . $i;

            if ($_POST[$status_name] == 'Loading')
            {
              $new_status = 'Loaded';
            }
            else if ($_POST[$status_name] == 'Unloading')
            {
              $new_status = 'Empty';

              // for the cars with this new status, delete the car orders linked to them
              $sql = 'delete from car_orders where car = "' . $_POST[$car_name]. '"';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Delete Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
              }
            }
            else if ($_POST[$status_name] == 'Empty')
            {
              $new_status = "Empty";
              // this case is for non-revenue moves, ie: repositioning empty cars
              // for the cars with this new status, delete the car orders linked to them
              $sql = 'delete from car_orders where car = "' . $_POST[$car_name]. '"';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Delete Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
              }
            }

            // build an sql query to update each car's status
            $sql = 'update cars set status = "' . $new_status . '", last_spotted = "0" where id = "' . $_POST[$car_name] . '"';
            if (!mysqli_query($dbc, $sql))
            {
              print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
            }
          }
        }
        
      }

      // build the sql query to pull in cars that are being loaded, unloaded, or enroute home and have
      // reached their home location
      $sql = 'select cars.id as id,
                     cars.reporting_marks as reporting_marks,
                     cars.position as position,
                     cars.status as status,
                     cars.last_spotted as last_spotted,
                     car_orders.waybill_number,
                     car_orders.shipment as shipment,
                     car_codes.code as car_code,
                     sta01.station as current_station,
                     loc01.code as current_location,
                     sta02.station as loading_station,
                     loc02.code as loading_location,
                     sta03.station as unloading_station,
                     loc03.code as unloading_location,
                     commodities.code as consignment
              from cars
              left join car_orders on car_orders.car = cars.id
              left join shipments on shipments.id = car_orders.shipment
              left join car_codes on car_codes.id = cars.car_code_id
              left join locations loc01 on loc01.id = cars.current_location_id
              left join locations loc02 on loc02.id = shipments.loading_location
              left join locations loc03 on loc03.id = shipments.unloading_location
              left join routing sta01 on sta01.id = loc01.station
              left join routing sta02 on sta02.id = loc02.station
              left join routing sta03 on sta03.id = loc03.station
              left join commodities on commodities.id = shipments.consignment
              left join routing on routing.id = loc01.station
              where ((cars.status = "Loading")
                  or (cars.status = "Unloading")
                  or ((cars.status = "Empty") and (cars.current_location_id = car_orders.shipment)))
              order by (case cars.status
                          when "Loading" then 0
                          when "Unloading" then 1
                          when "Empty" then 2
                        end), routing.sort_seq, current_location, position, reporting_marks';
      $rs = mysqli_query($dbc, $sql);
// print 'SQL: ' . $sql . '<br /><br />';

      // initialize a car counter
      $row_count = 0;

      // build the table of cars to be loaded or unloaded
      if (mysqli_num_rows($rs) > 0)
      {
        // generate the update button
        print '<br /><input name="update_btn" value="UPDATE" type="submit" style="background-color: #80ff00; font-size: 24px;"><br /><br />';
        
        // generate table and column heading tags
        print '<table class="sortable" id="car_table" name="car_table" style="white-space: nowrap;">
                 <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Row Filters</caption>
                 <thead>
                   <tr>
                     <th style="border-bottom:0px; border-right:0px;">
                       <button tabindex="4" id="clear_filters_btn" name="clear_filters_btn" onclick="location.reload();"
                        style="font: bold 10px Verdana, Arial, sans-serif; text-align: center; background-color: ffff00; font-size: 12px;">
                        CLEAR<br />FILTERS
                       </button>
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(1, document.getElementById(\'current_loc_filter\').options[document.getElementById(\'current_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'current_loc_filter\').disabled=true;">' .
                                   drop_down_locations('current_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(4, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                                   document.getElementById(\'car_code_filter\').disabled=true;">' .
                                   drop_down_car_codes('car_code_filter', '', 'no_wild') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(5, document.getElementById(\'status_filter\').options[document.getElementById(\'status_filter\').selectedIndex].text);
                                   document.getElementById(\'status_filter\').disabled=true;">' .
                                   drop_down_status('status_filter', '', 'no_wild') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(6, document.getElementById(\'commodity_filter\').options[document.getElementById(\'commodity_filter\').selectedIndex].text);
                                   document.getElementById(\'commodity_filter\').disabled=true;">' .
                                   drop_down_commodities('commodity_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(7, document.getElementById(\'loading_loc_filter\').options[document.getElementById(\'loading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'loading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('loading_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px;"
                         onchange="filter_rows(8, document.getElementById(\'unloading_loc_filter\').options[document.getElementById(\'unloading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'unloading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('unloading_loc_filter', '', '') . '
                     </th>
                   </tr>
                   <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th class="sorttable_nosort">
                       Load/Unload Cars<hr />
                       Check/Uncheck All <input id="check_all" name="check_all" type="checkbox" onchange="checkall();">
                     </th>
                     <th><i>Current<br /><u>Station</u><br />Location</i></th>
                     <th><i>Position</i></th>
                     <th><i>Reporting<br />Marks</i></th>
                     <th><i>Car Code</i></th>
                     <th><i>Status</i></th>
                     <th><i>Consignment</i></th>
                     <th><i>Loading<br /><u>Station</u><br />Location</i></th>
                     <th><i>Unloading<br /><u>Station</u><br />Location</i></th>
                   </tr>
                 </thead>';
 
        while ($row = mysqli_fetch_array($rs))
        {
          // look for non-revenue waybills
          if (substr($row['waybill_number'], 4, 1) == 'E')
          {
            $consignment = 'Non-Revenue';
            $load_loc = 'N/A';
            $unload_loc = '<u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'];
          }
          else
          {
            $consignment = $row['consignment'];
            $load_loc = '<u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'];
            $unload_loc = '<u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'];
          }

          $checkbox_name = 'check' . $row_count;
          $car_name = 'car' . $row_count;
          $status_name = 'status' . $row_count;
          $unload_loc_name = 'unload' . $row_count;
          if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
          {
            $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
          }
          else
          {
            $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
          }
          
          if ($row['status'] ==  'Loading')
          {
            $row_style = 'background-color:DarkGray;';
          }
          else if ($row['status'] == 'Unloading')
          {
            $row_style = 'background-color:White;';
          }
          else if ($row['status'] == 'Empty')
          {
            $row_style = 'background-color:LightGray;';
          }
          
          // get the min & max load and unload times from the current shipment
          // if older shipments have empty or blank values in the min and max fields, convert them to a value of 1
          $sql2 = 'select min_load_time, max_load_time, min_unload_time, max_unload_time from shipments where id = "' . $row['shipment'] . '"';
          $rs2 = mysqli_query($dbc, $sql2);
          $row2 = mysqli_fetch_array($rs2);
 
          if (strlen(trim($row2[0]))<1)
          {
            $min_load_time = 0;
          }
          else
          {
            $min_load_time = (int)$row2[0];
          }
          
          if (strlen(trim($row2[1]))<1)
          {
            $max_load_time = 0;
          }
          else
          {
            $max_load_time = (int)$row2[1];
          }
          
          if (strlen(trim($row2[2]))<1)
          {
            $min_unload_time = 0;
          }
          else
          {
            $min_unload_time = (int)$row2[2];
          }
          
          if (strlen(trim($row2[3]))<1)
          {
            $max_unload_time = 0;
          }
          else
          {
            $max_unload_time = (int)$row2[3];
          }

          // determine if each car's checkbox should be checked or not
          $chk_box_val = '';
          if ($row['status'] == 'Loading')
          {
            // get a random number between the min and max load times
            $random_load_time = rand($min_load_time, $max_load_time);
//print 'current session: ' . $current_session . ' last spotted: ' . $row['last_spotted'] . ' random load time: ' . $random_load_time . '<br />';       
            // add the random load time to the time spotted and if the result is less than the current session, check the box
            if (($row['last_spotted'] + $random_load_time) <= $current_session)
            {
              $chk_box_val = 'checked';
            }
           }
          else if ($row['status'] == 'Unloading')
          {
            // get a random number between the min and max unload times
            $random_unload_time = rand($min_unload_time, $max_unload_time);
//print 'current session: ' . $current_session . ' last spotted: ' . $row['last_spotted'] . ' random unload time: ' . $random_unload_time . '<br />';       
            // add the random unload time to the time spotted and if the result is less than the current session, check the box
            if (($row['last_spotted'] + $random_unload_time) <= $current_session)
            {
              $chk_box_val = 'checked';
            }
          }       

          print '<tr>
                   <td class="checkbox" style="' . $row_style . '">
                     <input id="' . $checkbox_name . '" name="' . $checkbox_name . '" value="' . $row['id'] . '" type="checkbox" ' . $chk_box_val . '>
                   </td>
                   <td style="' . $row_style . '">
                     <u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '
                   </td>
                   <td style="' . $row_style . ' text-align:center">' . 
                     $row['position'] . '
                   </td>
                   <td style="' . $row_style . '" onclick="show_image(' . $parm_string . ');">' . 
                     $row['reporting_marks'] . '<input name="' . $car_name . '" value="' . $row[0] . '" type="hidden">
                   </td>
                   <td style="' . $row_style . ' text-align:center">' .
                     $row['car_code'] . '
                   </td>
                   <td style="' . $row_style . '">' . 
                     $row['status'] . '<input name="' . $status_name . '" value="' . $row['status'] . '" type="hidden">
                   </td>
                   <td style="' . $row_style . '">' . 
                     $consignment . '
                   </td>
                   <td style="' . $row_style . '">' . 
                     $load_loc . '
                   </td>
                   <td style="' . $row_style . '">' . 
                     $unload_loc . '<input name="' . $unload_loc_name . '" value="' . $unload_loc . '" type="hidden">
                   </td>
                 </tr>';
          $row_count++;
          $previous_status = $row['status'];
        }
        print '</table>';
        // put the row count into a hidden field for when this program calls itself
        print '<input name="row_count" value="' . $row_count . '" type="hidden">';
      }
      else
      {
        print '<script>document.getElementById("instructions").innerHTML = "";</script>';
        print "<br />There are no cars currently in the process of  being loaded or unloaded.";
      }
      // add some extra lines to the instructions div
      print '<script>
               document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + "Filters can be used to hide rows. ";
               document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + "Click on column titles shown in <i>italics</i> to sort the table<br />";
             </script>';
  
    ?>
    </form>
  </body>
</html>
