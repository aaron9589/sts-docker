<?php
  // this routine returns a list of cars being handled by a specified job to the calling HttpRequest

  // get a database connection
  require 'open_db.php';
  $dbc = open_db();

  // pull in the style colors function
  require 'set_colors.php';
  
  // get the incoming parameter
  $job = $_REQUEST['job'];
  
  // run a quick query to get the name of this job
  $sql = 'select name from jobs where id = "' . $job . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);
  $job_name = $row['name'];

  // build a query to find all cars currently being handled by the specified job
  $sql = 'select cars.id as id,
                 cars.position as position,
                 cars.reporting_marks as reporting_marks,
                 cars.status as status,
                 cars.handled_by_job_id as handled_by_job_id,
                 cars.current_location_id as current_location_id,
                 car_orders.waybill_number as waybill_number,
                 car_orders.shipment as shipment_id,
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
            and cars.status != "Unavailable"
          
          group by reporting_marks
          order by cars.position, step_number, current_station, current_location, cars.reporting_marks';

//          order by cars.position, cars.reporting_marks';
//            and cars.current_location_id = 0
          
          // added cars.status != "Unavailable" to deal with cars that were removed from the railroad
          // added cars.current_location_id = 0 so only cars that have been picked up are shown on the organize page
          
//print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // build a table (less the <table> and </table> tags) and return it as a string
  $row_count = 0;
  if (mysqli_num_rows($rs) > 0)
  {
    $data_table = '<table id="car_table">';
    $data_table .= '<tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th>Move</th>
                     <th>Position</th>
                     <th>Reporting<br />Marks</th>
                     <th>Car<br />Code</th>
                     <th>Status</th>
                     <th>Consignment</th>
                     <th>Current<br /><u>Station</u><br />Location</th>
                     <th>Loading<br /><u>Station</u><br />Location</th>
                     <th>Unloading<br /><u>Station</u><br/>Location</th>
                   </tr>';
    while ($row = mysqli_fetch_array($rs))
    {
      // generate the table rows
      $data_table .= '<tr draggable="true" ondragstart="start()"  ondragover="dragover()">';
      
      // column 1 - up and down graphics
      $data_table .= '<td style="text-align: center;">
                        <img src="./ImageStore/DB_Images/graphics/up_arrow.png" onclick="move_row(this.parentElement, -1);"><br /><br />
                        <img src="./ImageStore/DB_Images/graphics/dn_arrow.png" onclick="move_row(this.parentElement, 1)">
                      </td>';
      
      // column 2 - position at this location
      $data_table .= '<td style="text-align: center; vertical-align: middle;">' . $row['position'] . '</td>';
      
      // column 3 - reporting marks
      if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
      {
        $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
      }
      else
      {
        $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
      }
      $data_table .= '<td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '</td>';
      $data_table .= '<input name="car' . $row_count . '" value="' . $row['id'] . '" type="hidden"></td>';
      
      // column 4 - car code
      $data_table .= '<td style="text-align: center;">' . $row['car_code'] . '</td>';
     
      // column 5 - status
      $data_table .= '<td>' . $row['status'] . '</td>';
      
      // column 6 - consignment -  if this is a non-revenue move, display "Non-Revenue", otherwise display the consignment
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>Non-Revenue</td>';
      }
      else
      {
        $data_table .= '<td>' . $row['consignment'] . '</td>';
      }
      
      // column 7 - current location
      if ($row['current_location_id'] > 0)
      {
        $data_table .= '<td>' . $row['current_station'] . '<br />' . $row['current_location'] . '</td>';
      }
      else
      {
        $data_table .= '<td>In Train</td>';
      }
      
      // column 8 - loading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        $data_table .= '<td>N/A</td>';
      }
      else
      {
        // if this car is ordered, bold the the loading location
        if ($row['status'] == 'Ordered')
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['loading_location']) . '"><b><u>'  . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td><u>' .  $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
        }
      }
      
      // column 9 - unloading location
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        // run two quick queries to find this car's destination since it isn't linked to a shipment
        // the destination is stored in the car order's shipment field
        $sql2 = 'select code from locations where locations.id = "' . $row['shipment_id'] . '"';
        $rs2 = mysqli_query($dbc, $sql2);
        $row2 = mysqli_fetch_array($rs2);
        
        $sql3 = 'select routing.station from routing, locations where locations.id = "' . $row['shipment_id'] . '" and routing.id = locations.station';
        $rs3 = mysqli_query($dbc, $sql3);
        $row3 = mysqli_fetch_array($rs3);
        $data_table .= '<td style="' . set_colors($dbc, $row2['code']) . '"><b><u>' . $row3['station'] . '</u><br />' . $row2['code'] . '</b></td>';
      }
      else
      {
        // if this car is loaded, bold the the final destination
        if ($row['status'] == 'Loaded')
        {
          $data_table .= '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><b><u>' .  $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</b></td>';
        }
        else
        {
          $data_table .= '<td><u>' .  $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>';
        }
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
    $data_table = 'No cars in ' . $job_name;
  }
  print $data_table;

?>
