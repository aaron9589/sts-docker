<?php
  // this routine returns a list of cars being handled by a specified job to the calling HttpRequest

  // get a database connection
  require 'open_db.php';
  $dbc = open_db();

  // pull in the style colors function
  require 'set_colors.php';

  // get the incoming parameter
  $job = $_REQUEST['job'];

  // build a query to find all cars currently being handled by the specified job
  $sql = 'select cars.id as id,
                 cars.position as position,
                 cars.reporting_marks,
                 cars.status as status,
                 cars.position,
                 car_orders.waybill_number as waybill_number,
                 car_orders.shipment as shipment,
                 sta01.station as current_station,
                 loc01.code as current_location,
                 sta02.station as loading_station,
                 loc02.code as loading_location,
                 sta03.station as unloading_station,
                 loc03.code as unloading_location,
                 car_codes.code as car_code,
                 commodities.code as consignment
          from cars
          left join car_orders on car_orders.car = cars.id
          left join shipments on shipments.id = car_orders.shipment
          left join locations loc01 on loc01.id = cars.current_location_id
          left join locations loc02 on loc02.id = shipments.loading_location
          left join locations loc03 on loc03.id = shipments.unloading_location
          left join routing sta01 on sta01.id = loc01.station
          left join routing sta02 on sta02.id = loc02.station
          left join routing sta03 on sta03.id = loc03.station
          left join car_codes on car_codes.id = cars.car_code_id
          left join commodities on commodities.id = shipments.consignment
          where cars.handled_by_job_id = "' . $job . '"
            and cars.current_location_id > 0
            and cars.status != "Unavailable"
          order by sta01.sort_seq asc, sta01.station asc, loc01.code asc, cars.position asc, cars.reporting_marks asc';
//          order by sta01.sort_seq, loc01.code, cars.position, cars.reporting_marks';

          // added cars.status != "Unavailable" to handle cars thave have been removed from the railroad

//print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // build a table (less the <table> and </table> tags) and return it as a string
  $row_count = 0;
  $current_pickup_group = null;
  if (mysqli_num_rows($rs) > 0)
  {
    $data_table = '<div class="table-responsive"><table id="job_table" class="table table-sm table-bordered table-hover">';
    $data_table .= '<tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th>Picked Up<br /><hr />
                     Check All <input id="check_all" name="check_all" type="checkbox" onchange="checkall();"></th>
                     <th>Pickup Location</th>
                     <th>Reporting Marks</th>
                     <th>Car Code</th>
                     <th>Status</th>
                     <th>Consignment</th>
                     <th>Loading Station / Location</th>
                     <th>Unloading Station / Location</th>
                   </tr>';
    while ($row = mysqli_fetch_array($rs))
    {
        // insert a group header row when the pickup location changes
        $group_key = $row['current_station'] . ' | ' . $row['current_location'];
        if ($group_key !== $current_pickup_group)
        {
          $current_pickup_group = $group_key;
          $data_table .= '<tr class="table-dark"><td colspan="8" class="fw-semibold">'
                      . htmlspecialchars($row['current_station']) . ' &mdash; ' . htmlspecialchars($row['current_location'])
                      . '</td></tr>';
        }

      // generate the table rows
      $data_table .= '<tr>';

      // column 1 - check box to indicate that the car was picked up
      $data_table .= '<td style="text-align: center;"><input id="check' . $row_count . '" name="check' . $row_count . '" type="checkbox"></td>';

      // column 2 - where the car was picked up (current location)
      $data_table .= '<td>' . $row['current_station'] . '<br />' . $row['current_location'] . '</td>';

      // column 3 - reporting marks
      if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
      {
        $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
      }
      else
      {
        $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
      }
      $data_table .= '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '
                      <input name="car' . $row_count . '" value="' . $row['id'] . '" type="hidden">
                      </td>';

      // column 4 - car code
      $data_table .= '<td>' . $row['car_code'] . '</td>';


      // column 5 - status
      $data_table .= '<td><span class="status-' . strtolower($row['status']) . '">' . $row['status'] . '</span></td>';


      // column 6 - consignment - if this is a non-revenue move, display Non-Revenue, otherwise display the consignment
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>Non-Revenue</td>';
      }
      else
      {
        $data_table .= '<td>' . $row['consignment'] . '</td>';
      }

      // column 7 - loading location - if this is a non-revenue move, display N/A, otherwise display the loading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>N/A</td>';
      }
      else
      {
        // if the car status is Ordered, mark the loading location with bold letters
        if ($row['status'] == "Ordered")
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b>' . $row['loading_station'] . '<br />' . $row['loading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td>' . $row['loading_station'] . '<br />' . $row['loading_location'] . '</td>';
        }
      }

      // column 8 - unloading location - if this is a non-revenue move, display it's final destination which is stored in the car order's shipment column
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        // run two quick queries to get this car's final destination
        // because it isn't linked to a shipment, it's unloading location is stored in the car order's shipment field
        $sql2 = 'select code from locations where id = "' . $row['shipment'] . '"';
        $rs2 = mysqli_query($dbc, $sql2);
        $row2 = mysqli_fetch_array($rs2);

        $sql3 = 'select routing.station from routing, locations where (routing.id = locations.station) and (locations.id = "' . $row['shipment'] . '")';
        $rs3 = mysqli_query($dbc, $sql3);
        $row3 = mysqli_fetch_array($rs3);
        $data_table .= '<td style="' . set_colors($dbc, $row2['code']) . '"><b>' . $row3['station'] . '<br />' . $row2['code'] . '</b></td>';

      }
      else
      {
        // if the car status is Loaded, mark the loading location with bold letters
        if ($row['status'] == "Loaded")
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b>' . $row['unloading_station'] . '<br />' . $row['unloading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td>' . $row['unloading_station'] . '<br />' . $row['unloading_location'] . '</td>';
        }
      }

      $data_table .= '</tr>';
      $row_count++;
    }
    $data_table .= '</table></div>';
    // add a hidden field to the end of the table containing the number of rows
    $data_table .= '<input name="row_count" value="' . $row_count . '" type="hidden">';
  }
  else
  {
    $data_table = 'None';
  }
  print $data_table;

  ///////////////////////////////////////// return to calling HttpRequest //////////////////////////////////////

?>
