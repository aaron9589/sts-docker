<html>
  <head>
    <title>STS - Print Car QR/Barcode</title>
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
      require '../phpqrcode/qrlib.php';
      require '../php-barcode/php-barcode.php';

      // set the parameters for the bar code
      $image_height = 30;
      $image_width = 400;
      $x = $image_width/2;  // barcode center
      $y = $image_height/2; // barcode center
      $bar_width = 2;       // barcode height in 1D ; not use in 2D
      $bar_height = 20;     // barcode height in 1D ; module size in 2D
      $angle = 0;           // rotation in degrees 
      $type = 'code39';     // bar code type

      // has the display button be clicked?
      if (isset($_GET["display_btn"]))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired job name
        $station_id = $_GET['station_name'];
        
        // get the actual station name for this station ID
        if ($station_id > 0)
        {
          $sql = 'select station from routing where id = "' . $station_id . '"';
          $rs = mysqli_query($dbc, $sql);
          $row = mysqli_fetch_row($rs);
          $station_name = $row[0];
        }
        else
        {
          $station_name = 'All';
        }

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

        if ($station_name != "All")
        {
          // if the selection is not "All", build a query to pull in the information about the cars at the selected station

          $sql = 'select locations.code as current_location,
                         cars.reporting_marks as reporting_marks,
                         cars.id as car_id
                    from cars
                    left join locations on locations.id = cars.current_location_id
                   where cars.current_location_id in 
                         (select locations.id
                            from locations, routing
                           where locations.station = routing.id and routing.station = "' . $station_name . '")
                   order by current_location, reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's car report
            print '<div class="noprint">';
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Car QR/Bar Code Report</h2>';
            print '<h3>Station: ' . $station_name . '</h3>';
            print 'Car QR/Bar Codes<br /><br />';
            print '<button onclick="window.print();">PRINT</button>&nbsp;&nbsp;<a href="display_car_qr_report.php">Return to Display Car QR/Bar Code Report page</a><br /><br />';
            print '</div>';

            print '<table style="font: normal 10px Verdana, Arial, sans-serif;">';
            print '<thead>';
            print '<tr>';
            if ($_GET['code_type'] == 'qr')
            {
              print '<th>Location</th><th>QR Code</th>';
            }
            else
            {
              print '<th>Location</th><th>Barcode</th>';
            }
            print '</tr>';
            print '</thead>';
            
            while ($row = mysqli_fetch_array($rs))
            {
              // check for the desired code type
              if ($_GET['code_type'] == 'qr')
              {
                $qr_file_name = './ImageStore/DB_Images/qrcodes/' . str_replace(" ", "", $row['reporting_marks']) . '.png';

                // if the file already exists, delete it
                if (is_file($qr_file_name))
                {
                  unlink($qr_file_name);
                }

                // generate this car's qr code
                QRcode::png($row['reporting_marks'], $qr_file_name, 'M', 1.5, 1);

                // generate the table row
                print '<tr>
                         <td>' . 
                           $row['current_location'] . '
                         </td>
                         <td>
                           <img src="' . $qr_file_name . '" style="vertical-align: middle">&nbsp;&nbsp;' . $row[1] . '
                         </td>
                       </tr>';
              }
              else
              {
                // create the barcode based on the car's internal ID instead of it's reporting marks in order to avoid illegal characters
                $im = imagecreatetruecolor($image_width, $image_height);
                $black = ImageColorAllocate($im,0x00,0x00,0x00);
                $white = ImageColorAllocate($im,0xff,0xff,0xff);
                imagefilledrectangle($im, 0, 0, $image_width, $image_height, $white);

                // add the bar code to the basic image
                $bar_code = '-' . $row['car_id'] . '-';
                $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array("code"=>$bar_code), $bar_width, $bar_height);
/*
                // add a black rectangle around the bar code based on the size of the code that was generated
                $code_width = $data["width"];
                $code_height = $data["height"];
                $left_margin = ($image_width - $code_width)/2;
                $top_margin = ($image_height - $code_height)/2;
                $right_margin = $image_width - $left_margin;
                $bottom_margin = $image_height - $top_margin;
                imagerectangle($im, $left_margin-2, $top_margin-2, $right_margin+2, $bottom_margin+2, $black);
*/
                $bar_file_name = './ImageStore/DB_Images/barcodes/' . str_replace(" ", "", $bar_code) . 'ID.png';

                // if the file already exists, delete it
                if (is_file($bar_file_name))
                {
                  unlink($bar_file_name);
                }

                imagepng($im, $bar_file_name);
                imagedestroy($im);
                
                // display both bar codes
                print '<tr>
                         <td>' . 
                           $row['current_location'] . '
                         </td>
                         <td style="text-align:center">
                           <img src="' . $bar_file_name . '" style="vertical-align: middle"><br />' . 
                           $row['reporting_marks'] . '
                         </td>
                       </tr>';
              }
            }
            print '</table>';
          }
          else
          {
            print "No cars found at " . $station_name . "<br />";
          }     
        }
        else
        {
          // generate a list of all cars at all stations, sorted by station

          $sql = 'select routing.station as current_station,
                         locations.code as current_location,
                         cars.reporting_marks as reporting_marks,
                         cars.id as car_id
                  from cars
                  left join locations on locations.id = cars.current_location_id
                  left join routing on routing.id = locations.station
                  order by current_location, reporting_marks';
// print 'SQL: ' . $sql . '<br /><br />';
          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's car report
            print '<div class="noprint">';
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Car QR/Bar Code Report</h2>';
            print '<h3>Station: ' . $station_name . '</h3>';
            print 'Car QR/Bar Codes<br /><br />';
            print '<button onclick="window.print();">PRINT</button>&nbsp;&nbsp;<a href="display_car_qr_report.php">Return to Display Car QR/Bar Code Report page</a><br /><br />';
            print '</div>';

            print '<table style="font: normal 10px Verdana, Arial, sans-serif;">';
            print '<tr>
                     <th>Station</th><th>Location</th><th>Code</th>
                   </tr>';

            $prev_row = '';
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // if the location for this row is different than the previous row (and it's not the first row)
              // generate a blank row to separate the locations
              if (($row['current_location'] != $prev_row) && (!$first_row))
              {
                print '<tr><td colspan="10"></td></tr>';
              }
              $prev_row = $row['current_location'];
              $first_row = false;

              // check for the desired code type
              if ($_GET["code_type"] == "qr")
              {
                $qr_file_name = "./ImageStore/DB_Images/qrcodes/" . str_replace(" ", "", $row['reporting_marks']) . ".png";

                // if the file already exists, delete it
                if (is_file($qr_file_name))
                {
                  unlink($qr_file_name);
                }

                // generate this car's qr code
                QRcode::png($row['reporting_marks'], $qr_file_name, 'M', 1.5, 1);

                // generate the table row
                print '<tr>
                         <td style="vertical-align: middle;">' . $row['current_station'] . '</td>
                         <td style="vertical-align: middle;">' . $row['current_location'] . '</td>
                         <td><img src="' . $qr_file_name . '" style="vertical-align: middle">&nbsp;&nbsp;' . $row['reporting_marks'] . '</td>
                       </tr>';
              }
              else
              {
                // create the basic image and fill it with white
                $im = imagecreatetruecolor($image_width, $image_height);
                $black = ImageColorAllocate($im,0x00,0x00,0x00);
                $white = ImageColorAllocate($im,0xff,0xff,0xff);
                imagefilledrectangle($im, 0, 0, $image_width, $image_height, $white);

                // add the bar code to the basic image
                $bar_code = '-' . $row['car_id'] . '-';
                $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array("code"=>$bar_code), $bar_width, $bar_height);
/*
                // add a black rectangle around the bar code based on the size of the code that was generated
                $code_width = $data["width"];
                $code_height = $data["height"];
                $left_margin = ($image_width - $code_width)/2;
                $top_margin = ($image_height - $code_height)/2;
                $right_margin = $image_width - $left_margin;
                $bottom_margin = $image_height - $top_margin;
                imagerectangle($im, $left_margin-2, $top_margin-2, $right_margin+2, $bottom_margin+2, $black);
*/
                $bar_file_name = "./ImageStore/DB_Images/barcodes/" . str_replace(" ", "", $row['reporting_marks']) . ".png";

                // if the file already exists, delete it
                if (is_file($bar_file_name))
                {
                  unlink($bar_file_name);
                }

                imagepng($im, $bar_file_name);
                imagedestroy($im);
                print '<tr>
                         <td style="vertical-align: middle;">' . $row['current_station'] . '</td> 
                         <td style="vertical-align: middle;">' . $row['current_location'] . '</td>
                         <td style="text-align:center">
                           <img src="' . $bar_file_name . '" style="vertical-align: middle"><br />' . $row['reporting_marks'] . '</td>
                       </tr>';
              }
            }
            print '</table>';
          }
          else
          {
            print 'No cars found on the system.<br />';
          }     
        }
      }
    ?>
    <div class="noprint">
    <br /><a href="display_car_qr_report.php">Return to Display Car QR/Bar Code Report page</a>
    </div>
  </body>
</html>
