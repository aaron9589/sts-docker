<?php
  // this routine returns a list of cars being handled by a specified job  to the calling HttpRequest

  // get a database connection
  require 'open_db.php';
  $dbc = open_db();

  // pull in the style color function
  require 'set_colors.php';
  
  // get the incoming parameter
  $job = $_REQUEST['job'];
  $default_loc = $_REQUEST['default_loc'];

  // get this job's instructions
  $sql = 'select name, description from jobs where id = "' . $job . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);
  $job_name = $row['name'];
  
  /* Job instructions now come from "get_cars_in_job.php"
  if (strlen($row['description']) > 0)
  {
    $job_instructions = nl2br($row['description']);
  }
  else
  {
    $job_instructions = "None";
  }
  */
  
  // build a query to find all cars currently being handled by the specified job
  $sql = 'select cars.id as id,
                 cars.reporting_marks as reporting_marks,
                 cars.status as status,
                 cars.position as position,
                 car_orders.waybill_number as waybill_number,
                 car_orders.shipment as shipment,
                 sta01.station as current_station,
                 loc01.code as current_location,
                 sta02.station as loading_station,
                 loc02.code as loading_location,
                 sta03.station as unloading_station,
                 loc03.code as unloading_location,
                 car_codes.code as car_code,
                 commodities.code as consignment,
                 `' . $job_name . '`.step_number as step_number
                 
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
          left join `' . $job_name . '` on `' . $job_name . '`.station = sta01.id
          
          where cars.handled_by_job_id = "' . $job . '"
            and cars.current_location_id = 0

          group by reporting_marks            
          order by position, current_location, reporting_marks';
//print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // build a table and return it as a string
  $row_count = 0;
  if (mysqli_num_rows($rs) > 0)
  {
    $data_table = '<table id="job_table" style="white-space: nowrap;">';
    $data_table .= '<tr>
                     <td colspan="8">';
//    $data_table .= 'JOB INSTRUCTIONS FOR '. $job_name . '<hr />' . $job_instructions; // replaced by a link to show_job_description.php
    $data_table .= 'Click <a href="show_job_description.php?job_id=' . $job . '" target="_blank">HERE</a> for Job Instructions<hr />';                 
    $data_table .= '  </td>
                   </tr>';
    $data_table .= '<tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th>Select Set-out<br />Station - Location<br /></th>
                     <th>Position</th>
                     <th>Reporting<br />Marks</th>
                     <th>Car<br/>Code</th>
                     <th>Loading<br /><u>Station</u><br />Location</th>
                     <th>Status</th>
                     <th>Unloading<br /><u>Station</u><br />Location</th>
                     <th>Consignment</th>
                   </tr>';
    while ($row = mysqli_fetch_array($rs))
    {
      // generate the table rows
      $data_table .= '<tr>';
      
      // column 1 - list of locations where the selected job can set cars out
      $data_table .= '<td>' . get_job_setout_locations($dbc, $job, $row_count, $default_loc) . '</td>';

      // column 2 - position of the car in the train
      $data_table .= '<td style="text-align: center;">' . $row['position'] . '</td>';
      
      // column 3 - reporting marks
      if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
      {
        $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
      }
      else
      {
        $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
      }
      $data_table .= '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '<input name="car' . $row_count . '" value="' . $row['id'] . '" type="hidden"></td>';      

      // column 4 - car code
      $data_table .= '<td style="text-align: center;">' . $row['car_code'] . '</td>';      
      
      // column 5 - loading location - if this is a non-revenue move, display N/A, otherwise display the loading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>N/A</td>';
      }
      else
      {
        // if the car status is Ordered, mark the loading location with bold letters
        if ($row['status'] == "Ordered")
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</b></td>';
        }
        else if ($row['status'] == "Empty")
        {
          $data_table .= '<td></td>';
        }
        else
        {
          $data_table .= '<td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
        }
      }

      // column 6 - status
      $data_table .= '<td>' . $row['status'] . '</td>';      
      
      // column 7 - unloading location - if this is a non-revenue move, display it's final destination which is stored in the car order's shipment column
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        // run two quick queries to get this car's final destination
        // because it isn't linked to a shipment, it's unloading location is stored in the car order's shipment field
        $sql2 = 'select code from locations where id = "' . $row['shipment'] . '"';
        $rs2 = mysqli_query($dbc, $sql2);
        $row2 = mysqli_fetch_array($rs2);
        
        $sql3 = 'select routing.station from routing, locations where locations.id = "' . $row['shipment'] . '" and routing.id = locations.station';
        $rs3 = mysqli_query($dbc, $sql3);
        $row3 = mysqli_fetch_array($rs3);
        $data_table .= '<td style="' . set_colors($dbc, $row2['code']) . '"><b><u>' . $row3['station'] . '</u><br />' . $row2['code'] . '</b></td>';
      }
      else
      {
        // if the car status is Loaded, mark the loading location with bold letters
        if ($row['status'] == "Loaded")
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</b></td>';
        }
        else if ($row['status'] == "Empty")
        {
          $data_table .= '<td></td>';
        }
        else
        {
          $data_table .= '<td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>';
        }
      }
      
      // column 8 - consignment - if this is a non-revenue move, display Non-Revenue, otherwise display the consignment
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
    print '</table>';
    // add a hidden field to the end of the table containing the number of rows
    $data_table .= '<input name="row_count" value="' . $row_count . '" type="hidden">';
  }
  else
  {
    $data_table = 'None';
  }
  print $data_table;

  ///////////////////////////////////////// return to calling HttpRequest //////////////////////////////////////

  // this function returns a drop-down list of locations where a car could be set out
  function get_job_setout_locations($dbc, $job, $row_count, $default_loc)
  {
    // build a query to get the name linked to this job id
    $sql = 'select name from jobs where id = "' . $job . '"';
    $rs = mysqli_query($dbc, $sql);
    $row = mysqli_fetch_array($rs);
    $job_name = $row['name'];
    
    // build a query to get the stations where this job can set out cars and find out it's default set-out location if there is one
    $sql = 'select distinct locations.id,
                   locations.code,
                   routing.sort_seq,
                   routing.station,
                   if (locations.id = routing.station_nbr, "Y", "N") as default_loc
              from locations, routing, `' . $job_name . '`
             where locations.station = `' . $job_name .'`.station
               and routing.id = locations.station
               and `' . $job_name . '`.setout = "T"
             order by routing.sort_seq, routing.station, locations.code';

    $rs = mysqli_query($dbc, $sql);

    if (mysqli_num_rows($rs) > 0)
    {
      $station_list = '<select name="station_list' . $row_count . '">';
      if ($default_loc == 'N')
      {
        $station_list .= '<option value="">KEEP IN TRAIN</option>';
      }
      while ($row = mysqli_fetch_array($rs))
      {
        if ($default_loc == 'Y')
        {
          if ($row['default_loc'] == "Y")
          {
            $station_list .= '<option value="' . $row['id'] . '">' . $row['station'] . ' - ' . $row['code'] . '</option>';
          }
        }
        else
        {
          $station_list .= '<option value="' . $row['id'] . '">' . $row['station'] . ' - ' . $row['code'] . '</option>';
        }
      }
      if ($default_loc == 'Y')
      {
        $station_list .= '<option value="">KEEP IN TRAIN</option>';
      }
      $station_list .= "</select>";
    }
    else
    {
      $station_list = '<select name="station_list"><option value="">This job has no set-out locations</option></select>';
    }
    return $station_list;
  }
?>
