<html>
  <head>
    <title>STS - Print Station Car Report</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
      @media print
      {
        .noprint {display:none;}
      }
    </style>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
    ?>
  <body>
    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';
      require 'set_colors.php';

      // has the display button be clicked?
      if (isset($_GET['display_btn']))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired job name
        $station_id = $_GET['station_name'];

        // get the print width from the settings table
        $sql = 'select setting_value from settings where setting_name = "print_width"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $print_width = $row[0];

        // get the railroad name from the settings table
        $sql = 'select setting_value from settings where setting_name = "railroad_name"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $rr_name = $row[0];
        
        // find out if the user wants to hide the cars with a status of "Unavailable"
        if (isset($_GET['hide_unavail']))
        {
          $hide_unavail_clause = ' and cars.status != "Unavailable" ';
        }
        else
        {
          $hide_unavail_clause = '';
        }
        
        if ($station_id > 0)
        {
          // if the selection is not "All", build a query to pull in the information about the cars at the selected station
          // but first get the name of the station for this location
          $sql = 'select station from routing where id = "' . $station_id . '"';
          $rs = mysqli_query($dbc, $sql);
          $row = mysqli_fetch_row($rs);
          $station_name = $row[0];

          $sql = 'select cars.current_location_id as current_location_id,
                         cars.position as position,
                         cars.reporting_marks as reporting_marks,
                         cars.id as id,
                         cars.car_code_id as car_code_id,
                         cars.status as status,
                         shipments.remarks as remarks,
                         commodities.code as consignment,
                         car_orders.waybill_number as waybill_number,
                         car_orders.shipment as shipment_id,
                         car_codes.code as car_code,
                         loc01.code as current_location,
                         loc02.code as loading_location,
                         loc03.code as unloading_location,
                         sta02.station as loading_station,
                         sta03.station as unloading_station,
                         jobs.name as job_name,
                         loc04.code as home_location,
                         sta04.station as home_station
                  from cars
                  left join car_orders on car_orders.car = cars.id
                  left join shipments on shipments.id = car_orders.shipment
                  left join car_codes on car_codes.id = cars.car_code_id
                  left join commodities on commodities.id = shipments.consignment
                  left join locations loc01 on loc01.id = cars.current_location_id
                  left join locations loc02 on loc02.id = shipments.loading_location
                  left join locations loc03 on loc03.id = shipments.unloading_location
                  left join locations loc04 on loc04.id = cars.home_location
                  left join routing sta02 on sta02.id = loc02.station
                  left join routing sta03 on sta03.id = loc03.station
                  left join routing sta04 on sta04.id = loc04.station
                  left join jobs on jobs.id = cars.handled_by_job_id
                  where cars.current_location_id in (select id from locations where station = "' . $station_id . '")' . $hide_unavail_clause . '
                  order by current_location, cars.position, cars.reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's car report
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Station Car Report - Cars on Hand</h2>';
            print '<h3>Station: ' . $station_name . '</h3>';

            print '(If a car is enroute, the next destination in the route is shown in <b>Bold</b> letters.)<br /><br />';

            print '<div class="noprint"><button onclick="window.print();">PRINT</button>&nbsp;&nbsp;
                   <a href="display_station_report.php">Return to Display Station Car Report page</a><br /><br />
                   </div>';
                
            print '<table style="font: normal 10px Verdana, Arial, sans-serif; width: ' . $print_width . '; white-space: nowrap;">
                     <thead>
                       <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                         <th><u>Station</u><br />Location</th>
                         <th>Position</th>
                         <th>Reporting<br />Marks</th>
                         <th>Car<br />Code</th>
                         <th>Status</th>
                         <th>Consignment</th>
                         <th>Loading<br /><u>Station</u><br />Location</th>
                         <th>Unloading<br /><u>Station</u><br />Location</th>
                         <th>Remarks</th>
                         <th>To be<br />handled by</th>
                         <th>Home<br /><u>Station</u><br />Location</th>
                       </tr>
                     </thead>';

            $prev_row = '';
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // don't print this row if the "hide unavailable" checkbox is checked and this car has a status of "Unavailable"
              if (!(isset($_GET['hide_rows']) && ($row['status'] == "Unavailable")))
              {
                // if the location for this row is different than the previous row (and it's not the first row)
                // generate a blank row to separate the locations
                if (($row['current_location'] != $prev_row) && (!$first_row))
                {
                  print '<tr><td colspan="10" style="border:0px;"></td></tr>';
                }
                $prev_row = $row['current_location'];
                $first_row = false;

                print '<tr>';
                if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
                {
                  $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
                }
                else
                {
                  $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
                }
                
                if (substr($row['waybill_number'], 4, 1) == "E")
                {
                  // if this car is hooked to a non-revenue waybill, display only the final destination
                  
                  // run a quick query to find out where it is going
                  $sql2 = 'select locations.code as code, routing.station as station
                             from locations, routing
                            where locations.id = "' . $row['shipment_id'] . '" and locations.station = routing.id';
                  $rs2 = mysqli_query($dbc, $sql2);
                  $row2 = mysqli_fetch_array($rs2);
  // print 'SQL: ' . $sql2 . '<br /><br />'; 
                  print '<td><u>' . $station_name . '</u><br />' . $row['current_location'] . '</td>
                         <td style="text-align: center;">' . $row['position'] . '</td>
                         <td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '</td> 
                         <td style="text-align: center;">' . $row['car_code'] . '</td>
                         <td>' . $row['status'] . '</td>
                         <td>Non-Revenue</td>
                         <td>N/A</td>
                         <td style="' . set_colors($dbc, $row2['code']) . '"><b><u>' . $row2['station'] . '</u><br />' . $row2['code'] . '</b></td>
                         <td>Repositioning</td>
                         <td>' . $row['job_name'] . '</td>
                         <td><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>';
                }
                else
                {
                  // otherwise, display the information normally                
                  print '<td><u>' . $station_name . '</u><br />' . $row['current_location'] . '</td>';
                  print '<td style="text-align: center;">' . $row['position'] . '</td>';
                  print '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '</td>';
                  print '<td style="text-align: center;">' . $row['car_code'] . '</td>';
                  print '<td>' . $row['status'] . '</td>';
                  print '<td>' . $row['consignment'] . '</td>';
                  if ($row['status'] == "Ordered")
                  {
                    print '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</b></td>';                  
                  }
                  else if (($row['status'] == "Loading") || ($row['status'] == "Loaded") || ($row['status'] == "Unloading"))
                  {
                    print '<td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
                  }
                  else
                  {
                    // status = Empty or Unavailable
                    print '<td></td>';
                  }
                  
                  if (($row['status'] == "Loading") || ($row['status'] == "Loaded") || ($row['status'] == "Unloading"))
                  {
                    print '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</b></td>';                  
                  }
                  else if ($row['status'] == "Ordered")
                  {
                    print '<td><u>' . $row['unloading_location'] . '</u><br />' . $row['unloading_location'] . '</td>';
                  }
                  else
                  {
                    // status = Empty or Unavailable
                    print '<td></td>';
                  }
                  
                  print '<td>' . $row['remarks'] . '</td>';
                  print '<td>' . $row['job_name'] . '</td>';
                  print '<td><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>';
                }
                print '</tr>';
              }
            }
            print '</table>';
            print '<br />(If a car is enroute, the next destination in the route is shown in <b>Bold</b> letters.)<br />';
          }
          else
          {
            print "No cars found at that location.<br />";
          }
        }
        else
        {
          
///////////////////////////////////////////// list all cars at all stations //////////////////////////////////////////////          
          
          // generate a list of all cars at all stations, sorted by station

          $sql = 'select cars.current_location_id as current_location_id,
                         cars.position as position,
                         cars.reporting_marks as reporting_marks,
                         cars.id as id,
                         cars.car_code_id as car_code_id,
                         cars.status as status,
                         shipments.remarks as remarks,
                         commodities.code as consignment,
                         car_orders.waybill_number as waybill_number,
                         car_orders.shipment as shipment_id,
                         car_codes.code as car_code,
                         loc01.code as current_location,
                         loc02.code as loading_location,
                         loc03.code as unloading_location,
                         sta02.station as loading_station,
                         sta03.station as unloading_station,
                         jobs.name as job_name,
                         loc04.code as home_location,
                         sta04.station as home_station,
                         routing.station as station_name,
                         routing.station_nbr as station_nbr
                  from cars
                  left join car_orders on car_orders.car = cars.id
                  left join shipments on shipments.id = car_orders.shipment
                  left join car_codes on car_codes.id = cars.car_code_id
                  left join commodities on commodities.id = shipments.consignment
                  left join locations loc01 on loc01.id = cars.current_location_id
                  left join locations loc02 on loc02.id = shipments.loading_location
                  left join locations loc03 on loc03.id = shipments.unloading_location
                  left join locations loc04 on loc04.id = cars.home_location
                  left join routing sta02 on sta02.id = loc02.station
                  left join routing sta03 on sta03.id = loc03.station
                  left join routing sta04 on sta04.id = loc04.station
                  left join jobs on jobs.id = cars.handled_by_job_id
                  left join routing on routing.id = loc01.station
                  where cars.current_location_id > 0' . $hide_unavail_clause . '
                  order by routing.sort_seq, routing.station, current_location, cars.position, cars.reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';

          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's car report
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Station Car Report - Cars on Hand</h2>';
            print '<h3>All Stations</h3>';

            print '(If a car is enroute, the next destination in the route is shown in <b>Bold</b> letters.)<br /><br />';

            print '<div class="noprint"><button onclick="window.print();">PRINT</button>&nbsp;&nbsp;
                   <a href="display_station_report.php">Return to Display Station Car Report page</a><br /><br />
                   </div>';
                
            print '<table style="font: normal 10px Verdana, Arial, sans-serif; width: ' . $print_width . '; white-space: nowrap;">
                   <thead>
                     <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                       <th>Station</th>
                       <th>Location</th>
                       <th>Position</th>
                       <th>Reporting<br />Marks</th>
                       <th>Car<br />Code</th>
                       <th>Status</th>
                       <th>Consignment</th>
                       <th>Loading<br /><u>Station</u><br />Location</th>
                       <th>Unloading<br /><u>Station</u><br />Location</th>
                       <th>Remarks</th>
                       <th>To be<br />handled by</th>
                       <th>Home<br /><u>Station</u><br />Location</th>
                     </tr>
                   </thead>';

            $prev_row = '';
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // don't print this row if the "hide unavailable" checkbox is checked and this car has a status of "Unavailable"
              if (!(isset($_GET['hide_rows']) && ($row['status'] == "Unavailable")))
              {
                // if the location for this row is different than the previous row (and it's not the first row)
                // generate a blank row to separate the locations
                if (($row['current_location'] != $prev_row) && (!$first_row))
                {
                  print '<tr><td colspan="11" style="border:0px;"></td></tr>';
                }
                $prev_row = $row['current_location'];
                $first_row = false;

                print '<tr>';
                
                if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
                {
                  $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
                }
                else
                {
                  $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
                }
                
                if (substr($row['waybill_number'], 4, 1) == "E")
                {
                  // if this car is hooked to a non-revenue waybill, display only the final destination

                  // run a quick query to find out where it is going
                  $sql2 = 'select locations.code as code, routing.station as station
                             from locations, routing
                            where locations.id = "' . $row['shipment_id'] . '" and locations.station = routing.id';
                  $rs2 = mysqli_query($dbc, $sql2);
                  $row2 = mysqli_fetch_array($rs2);
  // print 'SQL: ' . $sql2 . '<br /><br />'; 
                  print '<td>' . $row['station_name'] . '</td>
                         <td>' . $row['current_location'] . '</td>
                         <td style="text-align: center;">' . $row['position'] . '</td>
                         <td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '</td> 
                         <td style="text-align: center;">' . $row['car_code'] . '</td>
                         <td>' . $row['status'] . '</td>
                         <td>Non-Revenue</td>
                         <td>N/A</td>
                         <td style="' . set_colors($dbc, $row2['code']) . '"><b><u>' . $row2['station'] . '</u><br />' . $row2['code'] . '</b></td>
                         <td>Repositioning</td>
                         <td>' . $row['job_name'] . '</td>
                         <td><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>';
                }
                else
                {
                  // otherwise, display the information normally
                  
                  print '<td>' . $row['station_name'] . '</td>';
                  print '<td>' . $row['current_location'] . '</td>';
                  print '<td style="text-align: center;">' . $row['position'] . '</td>';
                  print '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '</td>';
                  print '<td style="text-align: center;">' . $row['car_code'] . '</td>';
                  print '<td>' . $row['status'] . '</td>';
                  print '<td>' . $row['consignment'] . '</td>';
                  if ($row['status'] == "Ordered")
                  {
                    print '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</b></td>';                  
                  }
                  else if (($row['status'] == "Loading") || ($row['status'] == "Loaded") || ($row['status'] == "Unloading"))
                  {
                    print '<td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
                  }
                  else
                  {
                    // status = Empty or Unavailable
                    print '<td></td>';
                  }
                  
                  if (($row['status'] == "Loading") || ($row['status'] == "Loaded") || ($row['status'] == "Unloading"))
                  {
                    print '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</b></td>';                  
                  }
                  else if ($row['status'] == "Ordered")
                  {
                    print '<td><u>' . $row['unloading_location'] . '</u><br />' . $row['unloading_location'] . '</td>';
                  }
                  else
                  {
                    // status = Empty or Unavailable
                    print '<td></td>';
                  }
                  
                  print '<td>' . $row['remarks'] . '</td>';
                  print '<td>' . $row['job_name'] . '</td>';
                  print '<td><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>';
                }
                print '</tr>';
              }
            }
            print '</table>';
            print '<br />(If a car is enroute, the next destination in the route is shown in <b>Bold</b> letters.)<br />';
          }
          else
          {
            print "No cars found on the system.<br />";
          }
        }
      }
    ?>
<div class="noprint">
    <br /><a href="display_station_report.php">Return to Display Station Car Report page</a>
</div>
  </body>
</html>
