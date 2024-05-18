<html>
  <head>
    
<title>STS - Print Station QR Report</title>
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
      $type = 'code39';     // type of bar code

      // has the display button be clicked?
      if (isset($_GET["display_btn"]))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired job name
        $station_id = $_GET['station_name'];

        // get the actual name for this station ID
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

        // get the railroad name from the settings
        $sql = 'select setting_value from settings where setting_name = "railroad_name"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $rr_name = $row[0];

        if ($station_name != 'All')
        {
          // if the selection is not "All", build a query to pull in the information about the location at the
          // selected station, leaving out any locations with an * in their location code

          $sql = 'select locations.code as code,
                         locations.id as location_id,
                         routing.station as station,
                         locations.track as track,
                         locations.spot as spot,
                         locations.remarks as remarks
                    from locations, routing
                  where locations.station = routing.id
                    and routing.station = "' . $station_name . '"
                  and (INSTR(locations.code, "*") = 0)
                  order by station, code';

          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's location report
            print '<div class="noprint">';
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Location QR/Bar Code Report</h2>';
            print '<h3>Station: ' . $station_name . '</h3>';
            print 'QR/Bar codes for locations at this station<br /><br />';
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;<a href="display_station_qr_code_report.php">Return to Display Station Car Report page</a><br /><br />';
            print '</div>';
            
            print '<table>';
            print '<thead>';
            print '<tr>
                     <th>Location</th>
                     <th>Code</th>
                   </tr>';
            print '</thead>';
            
            while ($row = mysqli_fetch_array($rs))
            {
              // check for the desired code type
              if ($_GET['code_type'] == 'qr')
              {
                // generate this car's qr code
                $qr_file_name = './ImageStore/DB_Images/qrcodes/' . str_replace(" ", "", $row['code']) . '.png';

                // if the file already exists, delete it
                if (is_file($qr_file_name))
                {
                  unlink($qr_file_name);
                }

                // generate the image
                QRcode::png($row[0], $qr_file_name, 'M', 2, 1);

                print '<tr>
                         <td>' .
                           $row['code'] . '<br />
                           Station: ' . $row['station'] . '<br />Track: ' . $row['track'] . '<br />Spot: ' . $row['spot'] . '<br />
                           Remarks: ' . $row['remarks'] . '
                         </td>
                         <td style="vertical-align: middle; width:100px; text-align: center;">
                           <img src="' . $qr_file_name. '">
                         </td>
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
                $bar_code = '%' . $row['location_id'] . '%';
                $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$bar_code), $bar_width, $bar_height);

                $bar_file_name = "./ImageStore/DB_Images/barcodes/" . str_replace(" ", "", $bar_code) . ".png";

                // if the file already exists, delete it
                if (is_file($bar_file_name))
                {
                  unlink($bar_file_name);
                }

                imagepng($im, $bar_file_name);
                imagedestroy($im);
                print '<tr>
                         <td>' .
                           $row['code'] . '<br />' .
                           'Station: ' . $row['station'] . '<br />Track: ' . $row['track'] . '<br />Spot: ' . $row['spot'] . '<br />' .
                           'Remarks: ' . $row['remarks'] . '
                         </td>
                         <td>
                           <img src="' . $bar_file_name . '" style="text-align:right;">
                         </td>
                       </tr>';
              }
            }
            print '</table>';
          }
          else
          {
            print "No locations found at " . $station_name . "<br />";
          }     
        }
        else
        {
          // generate a list of all cars at all stations, sorted by station

          $sql = 'select locations.code as code,
                         locations.id as location_id,
                         routing.station as station,
                         locations.track as track,
                         locations.spot as spot,
                         locations.remarks as remarks
                    from locations
                    left join routing on routing.id = locations.station
                  where (INSTR(locations.code, "*") = 0)
                  order by station, code';

          $rs = mysqli_query($dbc, $sql);
          if (mysqli_num_rows($rs) > 0)
          {
            // build a table for the selected station's car report
            print '<div class="noprint">';
            print '<h1>' . $rr_name . '</h1>';
            print '<h2 style="display:inline;">Location QR/Bar Code Report</h2>';
            print '<h3>Station: ' . $station_name . '</h3>';
            print 'QR/Bar codes for locations at this station<br /><br />';
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;<a href="display_station_qr_code_report.php">Return to Display Station Car Report page</a><br /><br />';
            print '</div>';
            
            print '<table>';
            print '<thead>';
            print '<tr>
                     <th>Location</th>
                     <th>Code</th>
                   </tr>';
            print '</thead>';

            $prev_row = '';
            $first_row = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // if the station for this row is different than the previous row (and it's not the first row)
              // generate a blank row to separate the locations
              if (($row['station'] != $prev_row) && (!$first_row))
              {
                print '<tr></tr>';
              }
              $prev_row = $row[1];
              $first_row = false;

              // check for the desired code type
              if ($_GET['code_type'] == 'qr')
              {
                // generate this car's qr code
                $qr_file_name = './ImageStore/DB_Images/qrcodes/' . str_replace(" ", "", $row['code']) . '.png';

                // if the file already exists, delete it
                if (is_file($qr_file_name))
                {
                  unlink($qr_file_name);
                }

                // generate the image
                QRcode::png($row[0], $qr_file_name, 'M', 2, 1);

                print '<tr>
                         <td>' .
                           $row['code'] . '<br />
                           Station: ' . $row['station'] . '<br />Track: ' . $row['track'] . '<br />Spot: ' . $row['spot'] . '<br />
                           Remarks: ' . $row['remarks'] . '
                         </td>
                         <td style="vertical-align: middle; width:50px; text-align: center;">
                           <img src="' . $qr_file_name. '">
                         </td>
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
                $bar_code = '%' . $row['location_id'] . '%';
                
                $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array("code"=>$bar_code), $bar_width, $bar_height);
                $bar_file_name = "./ImageStore/DB_Images/barcodes/" . str_replace(" ", "", $row[0]) . '.png';

                // if the file already exists, delete it
                if (is_file($bar_file_name))
                {
                  unlink($bar_file_name);
                }

                imagepng($im, $bar_file_name);
                imagedestroy($im);
                print '<tr>
                         <td>' .
                           $row['code'] . '<br />' .
                           'Station: ' . $row['station'] . '<br />Track: ' . $row['track'] . '<br />Spot: ' . $row['spot'] . '<br />' .
                           'Remarks: ' . $row['remarks'] . '
                         </td>
                         <td style="text-align:center;">
                           <img src="' . $bar_file_name . '"><br />' . $row['code'] . '
                         </td>
                       </tr>';
              }
            }
            print '</table>';
          }
          else
          {
            print 'No stations found on the system.<br />';
          }     
        }
      }
    ?>
    <br />
    <div class="noprint"><a href="display_station_qr_code_report.php">Return to Display Station Car Report page</a></div>
  </body>
</html>
