<html>
  <head>
    <title>STS - Print Car Fleet</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
      @media print
      {
        .noprint {display:none;}
        .page-break {display: block; page-break-after: always;}
      }
    </style>
  </head>
  <body>
    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';
      require '../phpqrcode/qrlib.php';

      // has the display button be clicked?
      if (isset($_GET['display_btn']))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired car code
        $car_code_id = $_GET['car_code'];
        
        if ($car_code_id > 0)
        {
          // if the car code ID is something other than 0 (zero) get the actual car code associated with this car code ID
          $sql = 'select code from car_codes where id = "' . $car_code_id . '"';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          $row = mysqli_fetch_row($rs);
          $car_code = $row[0];
        }
        else
        {
          $car_code = 'All';
        }
        
        $display_car_code = $car_code;

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

        if ($car_code != 'All')
        {
          // if the selection is not "All", build a query to pull in the information about the specified car code
          // but first substitute % (SQL wild card) for any * in the car code
          $new_car_code = '';
          for ($i=0; $i<strlen($car_code); $i++)
          {
            if (substr($car_code, $i, 1) == '*')
            {
              $new_car_code = $new_car_code . '%';
            }
            else
            {
              $new_car_code = $new_car_code . substr($car_code, $i, 1);
            }
          }
          $car_code = $new_car_code;

          $sql = 'select car_codes.code as car_code,
                         routing.station as current_station,
                         locations.code as current_location,
                         cars.reporting_marks as reporting_marks,
                         cars.status as status,
                         cars.remarks as remarks
                    from (cars, car_codes)
                    left join locations on locations.id = cars.current_location_id
                    left join routing on routing.id = locations.station
                   where car_codes.code like "' . $car_code . '" and cars.car_code_id = car_codes.id
                   order by car_code, current_location, reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the cars, sorted by car type, location, and then reporting marks
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Car Fleet Management Report</h2>';
            print '<h3>Car Code: ' . $display_car_code . '</h3>';
            print 'List of cars of this type and where they are located.<br /><br />';
            print '<div class="noprint">
                     <button onclick="window.print()">PRINT</button>&nbsp;&nbsp;
                     <a href="display_fleet_report.php">Return to Display Car Fleet Management Report page</a><br /><br />
                   </div>';
                   
            print '<table style="font: normal 10px Verdana, Arial, sans-serif; white-space: nowrap;">';
            print '<tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th>Car<br />Code</th><th><u>Station</u><br />Location</th><th>Reporting<br />Marks</th><th>Status</th><th>Remarks</th>
                   </tr>';

            $prev_row = "";
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // if the car code for this row is different than the previous row (and it's not the first row)
              // generate a blank row to separate the car codes
              if (($row['car_code'] != $prev_row) && (!$first_row))
              {
                print '<tr><td colspan="10" style="border:0px;"></td></tr>';
              }
              $prev_row = $row[0];
              $first_row = false;

              // generate the table row
              print '<tr>
                     <td style="text-align: center;">' . $row['car_code'] . '</td>
                     <td><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>
                     <td>' . $row['reporting_marks'] . '</td>
                     <td>' . $row['status'] . '</td>
                     <td>' . $row['remarks'] . '</td>
                     </tr>';
            }
            print '</table>';
          }
          else
          {
            print "No cars of this type found<br />";
          }
        }
        else
        {
          // generate a list of all cars sorted by car code, location, and reporting marks

          $sql = 'select car_codes.code as car_code,
                         routing.station as current_station,
                         locations.code as current_location,
                         cars.reporting_marks as reporting_marks,
                         cars.status as status,
                         cars.remarks as remarks
                    from cars, car_codes, locations, routing
                   where cars.car_code_id = car_codes.id
                     and locations.id = cars.current_location_id
                     and routing.id = locations.station
                   order by car_code, current_location, reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the report
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Car Fleet Management Report</h2>';
            print '<h3>Car Code: ' . $display_car_code . '</h3>';
            print 'List of all cars and where they are located<br /><br />';
            print '<div class="noprint">
                     <button onclick="window.print()">PRINT</button>&nbsp;&nbsp;
                     <a href="display_fleet_report.php">Return to Display Car Fleet Management Report page</a><br /><br />
                   </div>';

            print '<table style="font: normal 10px Verdana, Arial, sans-serif; white-space: nowrap;">';
            print '<thead>
                     <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                       <th>Car<br />Code</th><th><u>Station</u><br />Location</th><th>Reporting<br />Marks</th><th>Status</th><th>Remarks</th>
                     </tr>
                   </thead>';

            $prev_row = '';
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // if the car code for this row is different than the previous row (and it's not the first row)
              // generate a blank row to separate the car codes
              if (($row['car_code'] != $prev_row) && (!$first_row))
              {
                print '<tr><td colspan="10" style="border:0px;"></td></tr>';
              }
              $prev_row = $row[0];
              $first_row = false;

              // generate the table row
              print '<tr>
                     <td style="text-align: center;">' . $row['car_code'] . '</td>
                     <td><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>
                     <td>' . $row['reporting_marks'] . '</td>
                     <td>' . $row['status'] . '</td>
                     <td>' . $row['remarks'] . '</td>
                     </tr>';
            }
            print '</table>';
          }
          else
          {
            print "No cars found on the system.<br />";
          }
        }
      }
    ?>
    <div class="noprint"><br /><a href="display_fleet_report.php">Return to Display Car Fleet Management Report page</a></div>
  </body>
</html>
