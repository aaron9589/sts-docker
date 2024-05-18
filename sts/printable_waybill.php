<html>
  <head>
    <title>STS - Print Waybill</title>
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
  </head>
  <body>
    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';

      // has the display button be clicked?
      if (isset($_POST['display_btn']))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired waybill number
        $waybill_number = $_POST['waybill_number'];

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

        // get the current operating session number from the settings table
        $sql = 'select setting_value from settings where setting_name = "session_nbr"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $os_number = $row[0];

        // check to see if this is an empty car move
        if (strpos($waybill_number, 'E') > 0)
        {
          // if so, generate a query to pull in selected items of information and fill in with empty move info
          $sql = 'select cars.reporting_marks as reporting_marks,
                         "" as shipment,
                         "" as description,
                         "" as consignment,
                         "" as commodity_code,
                         loc01.code as current_location,
                         sta01.station as current_station,
                         "" as loading_location,
                         "" as loading_station,
                         loc03.code as unloading_location,
                         sta03.station as unloading_station,
                         cars.remarks as remarks,
                         car_codes.code as car_code,
                         "" as special_instructions
                    from cars
                    left join car_orders on car_orders.car = cars.id
                    left join locations loc01 on loc01.id = cars.current_location_id
                    left join locations loc03 on loc03.id = car_orders.shipment
                    left join routing sta01 on sta01.id = loc01.station
                    left join routing sta03 on sta03.id = loc03.station
                    left join car_codes on car_codes.id = cars.car_code_id
                   where car_orders.waybill_number = "' . $waybill_number . '"
                     and car_orders.car = cars.id';
        }
        else
        {
          // if not, generate a query to pull in the normal waybill information
          $sql = 'select cars.reporting_marks as reporting_marks,
                         shipments.code as shipment,
                         shipments.description as description,
                         commodities.description as consignment,
                         commodities.code as commodity_code,
                         loc01.code as current_location,
                         sta01.station as current_station,
                         loc02.code as loading_location,
                         sta02.station as loading_station,
                         loc03.code as unloading_location,
                         sta03.station as unloading_station,
                         shipments.remarks as remarks,
                         car_codes.code as car_code,
                         shipments.special_instructions
                  from car_orders
                  left join cars on cars.id = car_orders.car
                  left join shipments on shipments.id = car_orders.shipment
                  left join commodities on commodities.id = shipments.consignment
                  left join locations loc01 on loc01.id = cars.current_location_id
                  left join locations loc02 on loc02.id = shipments.loading_location
                  left join locations loc03 on loc03.id = shipments.unloading_location
                  left join routing sta01 on sta01.id = loc01.station
                  left join routing sta02 on sta02.id = loc02.station
                  left join routing sta03 on sta03.id = loc03.station
                  left join car_codes on car_codes.id = cars.car_code_id
                  where car_orders.waybill_number = "' . $waybill_number . '"';
        }
// print 'SQL: ' . $sql . '<br /><br />';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_array($rs);
        
        $reporting_marks = $row['reporting_marks'];
        $shipment = $row['shipment'];
        $description = $row['description'];
        $consignment = $row['consignment'];
        $commodity_code = $row['commodity_code'];
        $from_loc = $row['loading_location'];
        $from_station = $row['loading_station'];
        $to_loc = $row['unloading_location'];
        $to_station = $row['unloading_station'];
        $remarks = $row['remarks'];
        $current_loc = $row['current_location'];
        $current_station = $row['current_station'];
        $car_code = $row['car_code'];
        $special_instructions = $row['special_instructions'];

        // generate a query to bring in the empty car's current station, track, and spot
        $sql = 'select routing.station,
                       locations.track,
                       locations.spot,
                       locations.rpt_station
                  from locations,
                       routing
                 where locations.code = "' . $current_loc . '"
                   and routing.id = locations.station';
// print 'SQL: ' . $sql . '<br /><br />';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $empty_station = $row[0];
        $empty_track = $row[1];
        $empty_spot = $row[2];
        $empty_rpt_station = $row[3];

        // generate a query to bring in the "from" station, track, and spot
        $sql = 'select routing.station,
                       locations.track,
                       locations.spot,
                       locations.rpt_station
                  from locations,
                       routing
                 where locations.code = "' . $from_loc . '"
                   and routing.id = locations.station';
// print 'SQL: ' . $sql . '<br /><br />';                   
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $from_station = $row[0];
        $from_track = $row[1];
        $from_spot = $row[2];
        $from_rpt_station = $row[3];

        // generate a query to bring in the "to" station, track, and spot
        $sql = 'select routing.station,
                       locations.track,
                       locations.spot,
                       locations.rpt_station
                  from locations,
                       routing
                 where locations.code = "' . $to_loc . '"
                   and routing.id = locations.station';
// print 'SQL: ' . $sql . '<br /><br />';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $to_station = $row[0];
        $to_track = $row[1];
        $to_spot = $row[2];
        $to_rpt_station = $row[3];

          print '<div class="noprint">
                   <button onclick="window.print();">PRINT</button>&nbsp;&nbsp;
                   <a href="display_waybill.php">Return to Display Waybill page</a><br /><br />
                 </div>';

        // if the empty car station and the from station aren't the same, build an empty car waybill
        if ($empty_station != $from_station)
        {
          // substitute the empty station's report name if there is one
          if (strlen($empty_rpt_station) > 0)
          {
            $empty_station = $empty_rpt_station;
          }
          
          // substitute the from station's report name if there is one
          if (strlen($from_rpt_station) > 0)
          {
            $from_station = $from_rpt_station;
          }
          
          // substitute the to station's report name if there is one        
          if (strlen($to_rpt_station) > 0)
          {
            $to_station = $to_rpt_station;
          }

          // build the printable waybill for the empty move
          print '<table style="width: ' . $print_width . ';">
                 <tr style="font: normal 15px Verdana, Arial, sans-serif;">
                   <td style="text-align: center;" colspan="2">
                     <h2 style="font-family: Times New Roman", Times, serif;">' . $rr_name . '</h2>
                     <h3>FREIGHT WAYBILL</h3>
                     <div style="font: normal 10px Verdana, Arial, sans-serif;">
                       TO BE USED FOR SINGLE CONSIGNMENTS, CARLOAD AND LESS THAN CARLOAD
                     </div>
                   </td>
                 </tr>
                 <tr>
                   <td style="width: 50%;">
                     <table>
                       <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                         <td style="width: 50%; text-align: center;">
                           CAR INITIALS AND NUMBER<br /><br />' . $reporting_marks . '
                         </td>
                         <td style="width: 50%; text-align: center;">
                           KIND<br /><br />' . $car_code . '
                         </td>
                       </tr>
                     </table>
                   </td>
                   <td style="width: 50%;">
                     <table>
                       <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                         <td style="width: 50%; text-align: center;">
                           OPERATING SESSION No. <br /><br />' . $os_number . '
                         </td>
                         <td style="width: 50%; text-align: center;">
                           WAYBILL No. <br /><br />' . $waybill_number . '
                         </td>
                       </tr>
                     </table>
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td>';
            if (strpos($waybill_number, 'E') > 0)
            {
              print '<b>TO</b> ' . $to_loc . '<br />
                     <b>STATION</b> ' . $to_station . '<br />
                     <b>TRACK</b> ' . $to_track . '<br />
                     <b>SPOT</b> ' . $to_spot . '<br />';
            }
            else
            {
              print '<b>TO</b> ' . $from_loc . '<br />
                     <b>STATION</b> ' . $from_station . '<br />
                     <b>TRACK</b> ' . $from_track . '<br />
                     <b>SPOT</b> ' . $from_spot . '<br />';
            }
            print '</td>
                   <td>
                     <b>FROM</b> ' . $current_loc . '<br />
                     <b>STATION</b> ' . $empty_station . '<br />
                     <b>TRACK</b> ' . $empty_track . '<br />
                     <b>SPOT</b> ' . $empty_spot . '<br />
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td style="height: 100px">
                     SPECIAL INSTRUCTIONS (Regarding Icing, Weighing, Etc.)
                   </td>
                   <td>
                     SHIPMENT
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td colspan="2">
                     DESCRIPTION OF ARTICLES<br /><br />Empty Car Assignment
                   </td>
                 </tr>
                 </table>
                 <br />';
        }
        else if (($empty_station == $from_station) && ($current_loc != $from_loc))
        {
          // otherwise just print a note saying that the empty car should be moved from one location to another
          // at the same station
          
          // substitute the from station's report name if there is one
          if (strlen($from_rpt_station) > 0)
          {
            $from_station = $from_rpt_station;
          }
          print '<table style="width: ' . $print_width . ';">
                 <tr style="font: normal 15px Verdana, Arial, sans-serif;">
                   <td style="text-align: center;" colspan="2">
                     <h1 style="font-family: Times New Roman", Times, serif;">' . $rr_name . '</h1>
                     <h2>COMPANY MEMO</h2>
                   </td>
                 </tr>
                 <tr>
                   <td>
                     FROM:<br /><br /><hr />
                     TO C&E No.<br /><br /><hr />
                     OPERATING SESSION: ' . $os_number . '
                   </td>
                 </tr>
                   <td>
                     REPOSITION THE FOLLOWING EMPTY CAR<br /><br />
                     FOR LOADING AT ' . $from_station . ' / ' . $from_loc . '<br /><br />
                     CAR INITIALS AND NUMBER: ' . $reporting_marks . '&nbsp;
                     KIND: ' . $car_code . '<br /><br />
                     LOCATED AT: ' . $current_station . ' / ' . $current_loc . '
                   </td>
                 <tr>
                 </tr>
                 </table>
                 <br />';
        }

        // if this is not a reposition empty car move, build the printable waybill for the loaded move
        if (strpos($waybill_number, "E") == 0)
        {
          // substitute the from station's report name if there is one
          if (strlen($from_rpt_station) > 0)
          {
            $from_station = $from_rpt_station;
          }
          
          // substitute the to station's report name if there is one        
          if (strlen($to_rpt_station) > 0)
          {
            $to_station = $to_rpt_station;
          }

          print '<table style="width: ' . $print_width . ';">
                 <tr style="font: normal 15px Verdana, Arial, sans-serif;">
                   <td style="text-align: center;" colspan="2">
                     <h2 style="font-family: Times New Roman", Times, serif;">' . $rr_name . '</h2>
                     <h3>FREIGHT WAYBILL</h3>
                     <div style="font: normal 10px Verdana, Arial, sans-serif;">
                       TO BE USED FOR SINGLE CONSIGNMENTS, CARLOAD AND LESS THAN CARLOAD
                     </div>
                   </td>
                 </tr>
                 <tr>
                   <td style="width: 50%;">
                     <table>
                       <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                         <td style="width: 50%; text-align: center;">
                           CAR INITIALS AND NUMBER<br /><br />' . $reporting_marks . '
                         </td>
                         <td style="width: 50%; text-align: center;">
                           KIND<br /><br />' . $car_code . '
                         </td>
                       </tr>
                     </table>
                   </td>
                   <td style="width: 50%;">
                      <table>
                       <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                         <td style="width: 50%; text-align: center;">
                           OPERATING SESSION No. <br /><br />' . $os_number . '
                         </td>
                         <td style="width: 50%; text-align: center;">
                           WAYBILL No. <br /><br />' . $waybill_number . '
                         </td>
                       </tr>
                     </table>
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td>
                     <b>TO</b> ' . $to_loc . '<br />
                     <b>STATION</b> ' . $to_station . '<br />
                     <b>TRACK</b> ' . $to_track . '<br />
                     <b>SPOT</b> ' . $to_spot . '<br />
                   </td>
                   <td>
                     <b>FROM</b> ' . $from_loc . '<br />
                     <b>STATION</b> ' . $from_station . '<br />
                     <b>TRACK</b> ' . $from_track . '<br />
                     <b>SPOT</b> ' . $from_spot . '<br />
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td style="height: 100px;">
                     SPECIAL INSTRUCTIONS (Regarding Icing, Weighing, Etc.)<br /><br />' . $special_instructions . '
                   </td>
                   <td>
                     SHIPMENT<br /><br />' . $description . '<br /><br />(' . $shipment . ')
                   </td>
                 </tr>
                 <tr style="font: normal 10px Verdana, Arial, sans-serif;">
                   <td>
                     DESCRIPTION OF ARTICLES<br /><br />' . $consignment . '
                   </td>
                   <td>
                     COMMODITY CODE: ' . $commodity_code . '
                   </td>
                 </tr>
                 </table>';
        }
      }
    ?>
    <div class="noprint">
      <br /><a href="display_waybill.php">Return to Display Waybill page</a>
    </div>
  </body>
</html>
