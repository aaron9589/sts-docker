<?php
  // this routine returns a list of cars at a specified station to the calling HttpRequest

  // get a database connection
  require 'open_db.php';
  $dbc = open_db();

  // pull in the style color function
  require 'set_colors.php';
  
  // get the incoming parameter
  $station = urldecode($_REQUEST['station']);

  // build a query to get the routing instructions for this station
  $sql = 'select instructions from routing where id = "' . $station . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_row($rs);
  if (strlen($row[0]) > 0)
  {
    $instructions = 'Routing Instructions:<br /><br /> ' . $row[0];
  }
  else
  {
    $instructions = 'Routing Instruction: None';
  }

  // build a query to find all cars currently at the designated station
  $sql = 'select cars.id as id,
                 cars.reporting_marks as reporting_marks,
                 cars.status as status,
                 car_orders.waybill_number as waybill_number,
                 car_orders.shipment as shipment_id,
                 sta01.station as current_station,
                 loc01.code as current_location,
                 sta02.station as loading_station,
                 loc02.code as loading_location,
                 sta03.station as unloading_station,
                 loc03.code as unloading_location,
                 commodities.code as consignment,
                 car_codes.code as car_code
          from cars
          left join car_orders on car_orders.car = cars.id
          left join shipments on shipments.id = car_orders.shipment
          left join locations loc01 on loc01.id = cars.current_location_id
          left join locations loc02 on loc02.id = shipments.loading_location
          left join locations loc03 on loc03.id = shipments.unloading_location
          left join routing sta01 on sta01.id = loc01.station
          left join routing sta02 on sta02.id = loc02.station
          left join routing sta03 on sta03.id = loc03.station
          left join commodities on commodities.id = shipments.consignment
          left join car_codes on car_codes.id = cars.car_code_id
          where cars.current_location_id in (select id from locations where station = "' . $station . '")
            and cars.status in ("Ordered", "Loaded")
            and (cars.handled_by_job_id = 0)
          order by current_location';
//print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // build a table (less the <table> and </table> tags) and return it as a string
  $row_count = 0;
  if (mysqli_num_rows($rs) > 0)
  {
    $data_table = '<table id="car_table" style="white-space: nowrap;">';
    $data_table .= '<tr><td colspan="8">' . nl2br($instructions) . '</td></tr>';
    $data_table .= '<tr style="position: sticky; top: 0; background-color: #F5F5F5">
                      <th>Select Job</th>
                      <th>Reporting Marks</th>
                      <th>Car<br />Code</th>
                      <th>Current<br />Location</th>
                      <th>Loading<br /><u>Station</u><br />Location</th>
                      <th>Status</th>
                      <th>Unloading<br /><u>Station</u><br />Location</th>
                      <th>Consignment</th>
                    </tr>';

    while ($row = mysqli_fetch_array($rs))
    {
      // generate the table rows
      $data_table .= '<tr>';
      
      // column 1 - list of eligible jobs
      $data_table .= '<td>' . get_jobs_at_station($dbc, $station, $row_count) . '</td>';
      
      // column 2 - reporting marks
      if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
      {
        $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
      }
      else
      {
        $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
      }
      
      $data_table .= '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '<input name="car' . $row_count . '"';
      $data_table = $data_table . ' value="' . $row['id'] . '" type="hidden"></td>';
     
      // column 3 - car code
      $data_table .= '<td style="text-align: center;">' . $row['car_code'] . '</td>';
      
      // column 4 - current location
      $data_table .= '<td><u>'. $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>';
      
      // column 5 - loading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>N/A</td>';
      }
      else
      {
        // if this car is ordered, bold the the loading location
        if ($row['status'] == 'Ordered')
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
        }
      }
      
      // column 6 - status
      $data_table .= '<td>' . $row['status'] . '</td>';
      
      // column 7 - unloading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        // run a couple quick queries to find this car's destination since it isn't linked to a shipment
        // the destination is stored in the car order's shipment field
        $sql2 = 'select code from locations where locations.id = "' . $row['shipment_id'] . '"';
        $rs2 = mysqli_query($dbc, $sql2);
        $row2 = mysqli_fetch_array($rs2);
        
        $sql3 = 'select routing.station from routing, locations where (locations.id = ' . $row['shipment_id'] . ') and (routing.id = locations.station)';
        $rs3 = mysqli_query($dbc, $sql3);
        $row3 = mysqli_fetch_array($rs3);

        $data_table .= '<td style="' . set_colors($dbc, $row2['code']) . '"><b><u>' . $row3['station'] . '</u><br />' . $row2['code'] . '</b></td>';
      }
      else
      {
        // if this car is loaded, bold the the final destination
        if ($row['status'] == 'Loaded')
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>';
        }
      }
      
      // column 8 - consignment -  if this is a non-revenue move, display "Non-Revenue", otherwise display the consignment
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>Non-Revenue</td>';
      }
      else
      {
        $data_table .= '<td>' . $row['consignment'] . '</td>';
      }
      
      $data_table .= '</tr>';
      $row_count++;
    }
    $data_table .= '</table>';
    // add a hidden field to the end of the table containing the number of rows
    $data_table .= '<input name="row_count" value="' . $row_count . '" type="hidden">';
  }
  else
  {
    $data_table = 'None';
  }
  print $data_table;

  // this function returns a drop-down list of jobs that pick up at this station
  function get_jobs_at_station($dbc, $station, $row_count)
  {
    // build a query to get the names of all of the jobs
    $sql = 'select id, name from jobs';
    $rs = mysqli_query($dbc, $sql);

    // build a drop-down list from the jobs that are set to pick up at this station
    if (mysqli_num_rows($rs))
    {
      $job_list = '<select name="job_list' . $row_count . '">';
      $job_list .= '<option value=""></option>';
      while ($row = mysqli_fetch_array($rs))
      {
        // build a query to see if this job is set to pick up at this station
        // if so, add it to the list of options
        $sql = 'select count(*) from `' . $row['name'] . '` where station = "' . $station . '" and pickup = "T"';
        $rs_steps = mysqli_query($dbc, $sql);
        $row_steps = mysqli_fetch_row($rs_steps);
        if ($row_steps[0] > 0)
        {
          $job_list .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
      }
      $job_list .= '</select>';
    }
    else
    {
      $job_list = 'No job picks up here';
    }
    return $job_list;
  }
?>
