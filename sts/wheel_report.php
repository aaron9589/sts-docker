<html>
  <head>
    <title>STS - Wheel Reports</title>
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

  </head>
  <body>
    <div class="noprint"> 
      <p><img src="ImageStore/GUI/Menu/report.jpg" width="715" height="144" border="0" usemap="#MapMap">
        <map name="MapMap">
          <area shape="rect" coords="566,7,704,47" href="index.html">
          <area shape="rect" coords="566,96,706,136" href="index-t.html">
          <area shape="rect" coords="563,51,707,91" href="reports.html">
        </map>
      </p>
    </div>
    <?php
      // bring in the utility files
      require 'open_db.php';
      require 'set_colors.php';

      // get a database connection
      $dbc = open_db();

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
      
      // get a list of all the jobs/trains
      $sql1 = 'select id, name, description from jobs order by name';
      $rs1 = mysqli_query($dbc, $sql1);
      if (mysqli_num_rows($rs1))
      {
        // print report header
        print '<div class="noprint">';
        print '<h2>Reports</h2>
               <h3>Wheel Reports</h3>';
               
        print 'These wheel reports contain a list of all cars currently assigned to a job/train. All<br />
               assigned cars are included whether they have been picked up or not. Cars that have been<br />
               picked up are shown as "In Train". Cars that were previously picked up and have since been<br />
               set out are not included. This report is only valid as of the date and time that it was generated<br />
               as cars may have been picked up and/or set out since the report was created.<br /><br />';
        print '<button onclick="window.print()">PRINT</button><br /><br />';
        print '<hr />
               </div>';
        
// print 'Time Zone: ' . date_default_timezone_get() . '<br /><br />';

        // start the report table
        print '<table>
                 <thead>
                   <tr>
                     <td style="text-align: center; border: 0px;" colspan="8">
                       <h2>' . $rr_name . '</h2>
                       <h3>Wheel Reports for All Jobs/Trains</h3>
                       Report date/time: ' . date('H:i l d M Y') . '
                     </td>
                   </tr>
                 </thead>';
        
        // loop through each job/train
        while ($row1 = mysqli_fetch_array($rs1))
        {
          // print the train header info
          print '<tr>
                   <td colspan="2"><b>Job/Train</b><br /><br />' . $row1['name'] . '</td>
                   <td colspan="6">' . nl2br($row1['description']) . '
                 </tr>
                 <tr>
                   <th>Seq</th>
                   <th>Reporting<br/>Marks</th>
                   <th>Type</th>
                   <th>L/E</th>
                   <th>Commodity</th>
                   <th>Pick Up At<br /><u>Station</u><br />Location</th>
                   <th>Destination<br /><u>Station</u><br />Location</th>
                   <th>Waybill<br />Number</th>
                 </tr>';
          
          // 
          $sql2 = 'select jobs.id as job_id,
                        jobs.name as job_name,
                        cars.id as car_id,
                        cars.position as position,
                        cars.reporting_marks as reporting_marks,
                        car_codes.code as car_code,
                        cars.status as status,
                        cars.remarks as car_remarks,
                        loc01.code as pickup_location,
                        ifnull(sta01.station, "In Train") as pickup_station,
                        loc02.code as unloading_location,
                        sta02.station as unloading_station,
                        commodities.code as commodity,
                        car_orders.shipment as shipment_id,
                        car_orders.waybill_number as wb_number,
                        `' . $row1['name'] . '`.step_number as job_step
                        
                   from jobs
                   
                   left join cars on jobs.id = cars.handled_by_job_id
                   left join car_codes on cars.car_code_id = car_codes.id
                   left join car_orders on car_orders.car = cars.id
                   left join shipments on shipments.id = car_orders.shipment
                   left join locations loc01 on cars.current_location_id = loc01.id
                   left join routing sta01 on loc01.station = sta01.id
                   left join locations loc02 on shipments.unloading_location = loc02.id
                   left join routing sta02 on loc02.station = sta02.id
                   left join commodities on commodities.id = shipments.consignment
                   left join `' . $row1['name'] . '` on `' . $row1['name'] . '`.station = sta01.id

                   where jobs.name = "' . $row1['name'] . '"
                  
                   group by job_id, job_name, car_id
                   
                   order by jobs.name, job_step, pickup_location, reporting_marks';
// print '<br />SQL2: ' . $sql2 . '<br />';               
          $rs2 = mysqli_query($dbc, $sql2);
          if (mysqli_num_rows($rs2))
          {
            // count cars
            $empties = 0;
            $loads = 0;
            
            // generate a row in the table for each car
            while ($row2 = mysqli_fetch_array($rs2))
            {
              // if this row contains a car, generate a table row
              if ($row2['car_id'] > 0)
              {
                // set up a link to this car's image if it exists
                if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row2['car_id'] . '.jpg'))
                {
                  $parm_string = '\'' . $row2['car_id'] . '\', \'' . $row2['reporting_marks'] . '\'';
                }
                else
                {
                  $parm_string = '\'\',\'' . $row2['reporting_marks'] . '\'';
                }
                
                // compress the load/empty column
                if (($row2['status'] == "Empty") || ($row2['status'] == "Ordered"))
                {
                  $car_status = "E";
                  $empties++;
                }
                else if ($row2['status'] == "Loaded")
                {
                  $car_status = "L";
                  $loads++;
                }
                else
                {
                  $car_status = "";
                }
                
                //watch for empty car moves
                if ($row2['status'] == "Ordered")
                {
                  $commodity = 'Ordered';
                  if (substr($row2['wb_number'], 4, 1) != "E")
                  {
                    $sql3 = 'select routing.station as dest_station, locations.code as dest_location
                               from routing, locations, shipments
                              where routing.id = locations.station
                                and locations.id = shipments.loading_location
                                and shipments.id = ' . $row2['shipment_id'];
// print '<br />SQL3: ' . $sql3 . '<br /><br />';
                    $rs3 = mysqli_query($dbc, $sql3);
                    $row3 = mysqli_fetch_array($rs3);
                    $destination = '<u>' . $row3['dest_station'] . '</u><br />' . $row3['dest_location'];
                  }
                  else
                  {
                    $sql3 = 'select routing.station as dest_station, locations.code as dest_location
                               from routing, locations, shipments
                              where routing.id = locations.station
                                and locations.id = ' . $row2['shipment_id'];
                    $rs3 = mysqli_query($dbc, $sql3);
                    $row3 = mysqli_fetch_array($rs3);
                    $destination = '<u>' . $row3['dest_station'] . '</u><br />' . $row3['dest_location'];
                  }
                }
                else
                {
                  $commodity = $row2['commodity'];
                  $destination = '<u>' . $row2['unloading_station'] . '</u><br />' . $row2['unloading_location'];
                }

                print '<tr>
                         <td style="text-align: center;">' . $row2['position'] . '</td>
                         <td onclick="show_image(' . $parm_string . ');" style="white-space: nowrap;">' . $row2['reporting_marks'] . '</td>
                         <td style="text-align: center;">' . $row2['car_code'] . '</td>
                         <td style="text-align: center;">' . $car_status . '</td>
                         <td>' . $commodity . '</td>
                         <td><u>' . $row2['pickup_station'] . '</u><br />' . $row2['pickup_location'] . '</td>
                         <td>' . $destination . '</td>
                         <td>' . $row2['wb_number'] . '</td>
                       </tr>';
              }
              else
              {
                // generate a "no cars" row
                print '<tr><td colspan="8">No cars assigned to this job/train</td></tr>';
              }
            }
            
            $cars = $loads + $empties;
            if ($cars > 0)
            {
              print '<tr><td colspan="8">' . $loads . ' Loaded / ' . $empties . ' Empty / ' . $cars . ' Car(s)</td></tr>';
            }
            print '<tr><td colspan="8" style="border-left: 0px; border-right: 0px;">&nbsp;</td></tr>';
          }
          else
          {
            // no cars in this job/train
            print '<tr><td colspan="8">No cars assigned to this job/train</td></tr>';
          }
        }
        // close the table
        print '</table>';
      }
      else
      {
        // no jobs/trains found
      }
    ?>
  </body>
</html>
