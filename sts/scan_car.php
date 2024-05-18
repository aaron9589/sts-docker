<html>
  <head>
    <title>STS - Scan Cars</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
  </head>
  <body onload='document.getElementById("reporting_marks").focus();'>
<p><img src="ImageStore/GUI/Menu/manage.jpg" width="718" height="146" border="0" usemap="#Map5">
  <map name="Map5">
    <area shape="rect" coords="569,4,708,48" href="index.html">
    <area shape="rect" coords="569,97,711,140" href="index-t.html">
    <area shape="rect" coords="569,52,709,91" href="database.html">
  </map>
</p>
<h2>Database Management</h2>
    <h3 >Scan Cars</h3>
    <div id="instructions">
    If the scanner is set to send a "Carriage Return" at the end of the scan,<br />
    it will automatically trigger the Scan button.<br /><br />
    To manually search for car information, enter the car's reporting marks<br />
    into the Reporting Marks input box and then click the <b>SCAN</b> button.<br /><br />
    </div>
    <form action="scan_car.php" method="get">
    Scan QR code, Bar code, or RFID tag:
      <input id="scan_code" name="scan_code" type="text">
      <input name="scan_btn" value="SCAN" type="submit">
      <br /><br />
    <?php
      // display everything we know about the selected car

      // was the scan button clicked?
      if (isset($_GET['scan_btn']))
      {
        // pull in the utility files
        require 'open_db.php';

        // get a database connection
        $dbc = open_db();
        
        // check to see if the incoming code is a car ID number delimited by "-"
        if ((substr($_GET['scan_code'], 0, 1) == '-') && (substr($_GET['scan_code'], -1, 1)))
        {
          // if it is, get the car's reporting marks
          $car_id = substr($_GET['scan_code'], 1, strlen($_GET['scan_code'])-2);
          $sql = 'select reporting_marks from cars where id = ' . $car_id;
          $rs = mysqli_query($dbc, $sql);
          $row = mysqli_fetch_array($rs);
          $scan_code = $row['reporting_marks'];
        }
        else
        {
          // if not, just pass the scanned code on to the query
          $scan_code = $_GET['scan_code'];
        }

        // build a query to bring in the info
        $sql = 'select cars.id as id,
                       cars.reporting_marks as reporting_marks,
                       cars.car_code_id as car_code_id,
                       cars.current_location_id as current_location_id,
                       cars.status as status,
                       cars.handled_by_job_id,
                       cars.RFID_code as RFID_code,
                       car_orders.waybill_number as waybill_number,
                       shipments.id as shipment_id,
                       shipments.code as shipment,
                       shipments.consignment as consignment_id,
                       shipments.loading_location as loading_location_id,
                       shipments.unloading_location as unloading_location_id,
                       cars.remarks as remarks,
                       cars.load_count as load_count,
                       car_codes.code as car_code,
                       commodities.code as consignment,
                       loc01.code as current_location,
                       loc02.code as loading_location,
                       loc03.code as unloading_location,
                       sta01.station as current_station,
                       sta02.station as loading_station,
                       sta03.station as unloading_station,
                       jobs.name as job_name
                  from cars
                  left join car_orders on cars.id = car_orders.car
                  left join shipments on shipments.id = car_orders.shipment
                  left join jobs on cars.handled_by_job_id = jobs.id
                  left join car_codes on cars.car_code_id = car_codes.id
                  left join commodities on shipments.consignment = commodities.id
                  left join locations loc01 on cars.current_location_id = loc01.id
                  left join locations loc02 on shipments.loading_location = loc02.id
                  left join locations loc03 on shipments.unloading_location = loc03.id
                  left join routing sta01 on sta01.id = loc01.station
                  left join routing sta02 on sta02.id = loc02.station
                  left join routing sta03 on sta03.id = loc03.station
                  where (cars.reporting_marks = "' . strtoupper($scan_code) . '" or
                         cars.RFID_code = "' . $_GET['scan_code'] . '")';
// print $sql;
        $rs = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($rs) > 0)
        {
          $row = mysqli_fetch_array($rs);

          // build a table showing the information
          // add links to db_edit_car.php, db_edit_car_code.php, db_edit_location.php, db_edit_job.php, and db_edit_shipment.php programs
          $car_link = '<a href="db_edit.php?tbl_name=cars&obj_name=' . 
                       $row['reporting_marks'] . '">' . 
                       $row['reporting_marks'] . '</a>';
                       
          $car_code_link = '<a href="db_edit.php?tbl_name=car_codes&obj_id=' . 
                            $row['car_code_id'] . '&obj_name=' .
                            $row['car_code'] . '">' .
                            $row['car_code'] . '</a>';
          
          if ($row['current_location_id'] > 0)
          {            
            $location_link = '<a href="scan_location.php?location=' .
                              $row['current_location'] . '&scan_btn=Scan">' .
                              $row['current_location'] . '</a>';
          }
          else
          {
            $location_link = 'In train';
          }
                           
          $job_link = '<a href="db_edit.php?tbl_name=jobs&obj_id=' .
                       $row['handled_by_job_id'] . '&obj_name=' .
                       $row['job_name'] . '">' .
                       $row['job_name'] . '</a>';
                       
          $shipment_link = '<a href="db_edit.php?tbl_name=shipments&obj_id=' .
                            $row['shipment_id'] . '&obj_name=' .
                            $row['shipment'] . '">' .
                            $row['shipment'] . '</a>';

          print '<table>
                   <thead>
                     <tr>
                       <th>Property</th>
                       <th>Value</th>
                     </tr>
                   </thead>
                   <tr>
                     <td>Reporting Marks:</td>
                     <td>' . $car_link . '</td>
                   </tr>
                   <tr>
                     <td>Car Code:</td>
                     <td>' . $car_code_link . '</td>
                   </tr>
                   <tr>
                     <td>Current Station:</td>
                     <td>' . $row['current_station'] . '</td>
                   </tr>
                   <tr>
                     <td>Current Location:</td>
                     <td>' . $location_link . '</td>
                   </tr>
                   <tr>
                     <td>Status:</td
                     ><td>' . $row['status'] . '</td>
                   </tr>
                   <tr>
                     <td>Handled By:</td>
                     <td>' . $job_link . '</td>
                   </tr>
                   <tr>
                     <td>Waybill Number:</td>
                     <td>' . $row['waybill_number'] . '</td>
                   </tr>
                   <tr>
                     <td>Shipment Code:</td>
                     <td>' . $shipment_link . '</td>
                   </tr>
                   <tr>
                     <td>Consignment:</td>
                     <td>' . $row['consignment'] . '</td>
                   </tr>
                   <tr>
                     <td>Loading Station:</td>
                     <td>' . $row['loading_station'] . '</td>
                   </tr>
                   <tr>
                     <td>Loading Location:</td>
                     <td>' . $row['loading_location'] . '</td>
                   </tr>
                   <tr>
                     <td>Unloading Station:</td>
                     <td>' . $row['unloading_station'] . '</td>
                   </tr>
                   <tr>
                     <td>Unloading Location:</td>
                     <td>' . $row['unloading_location'] . '</td>
                     </tr>
                   <tr>
                     <td>Remarks:</td>
                     <td>' . $row['remarks'] . '</td>
                   </tr>
                   <tr>
                     <td>Load Count:</td>
                     <td>' . $row['load_count'] . '</td>
                   </tr>
                   <tr>
                     <td>RFID code:</td>
                     <td>' . $row['RFID_code'] . '</td>
                   </tr>
                 </table>';
        }
        else
        {
          print "Car with ID " . $_GET['scan_code'] . " not found. Check spelling.";
        }
      }
    ?>
    </form>
  
</body>
</html>


