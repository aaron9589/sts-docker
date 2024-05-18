<html>
  <head>
    <title>STS - Fix Incomplete Waybills</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      // this javascript function is triggered by the user changing the "All" checkbox
      function checkall()
      {
        var row_count = document.getElementById('wb_table').rows.length-1;
        if (document.getElementById('check_all').checked == true)
        {
          for (var i=0; i < row_count; i++)
          {
            var checkbox_name = "check" + i.toString();
            document.getElementById(checkbox_name).checked = true;
          }
        }
        else
        {
          for (var i=0; i < row_count; i++)
          {
            var checkbox_name = "check" + i.toString();
            document.getElementById(checkbox_name).checked = false;
          }
        }
      }
    </script>
  </head>
  <body>
<p> <img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
  <map name="Map3">
    <area shape="rect" coords="567,5,710,47" href="index.html">
    <area shape="rect" coords="568,98,708,142" href="index-t.html">
    <area shape="rect" coords="567,54,711,92" href="db-maint.html">
  </map>
</p>
<h2><a href="validate_db.php"><img src="ImageStore/GUI/Menu/validate.png" width="166" height="40" border="0"></a></h2>
<h2>Database Maintenance</h2>
    <h3 >Fix Incomplete Waybills</h3>
    <div id="instructions">
    These waybills were not closed out when the cars attached to them were unloaded at their final destination.<br /><br />
    When the FIX WAYBILLS button is clicked, all selected waybills will be closed out and the empty cars attached<br />
    to them will be released for use.<br />
  <br />
    </div>

    <?php
      // pull in the utility files
      require 'open_db.php';

      // get a database connection
      $dbc = open_db();

      // was the fix it button clicked?
      if (isset($_GET['fix_it_btn']))
      {
        // go through the incoming rows and fix the selected cars
        for ($i=0; $i<$_GET['row_counter']; $i++)
        {
          print '<br />Fixing ' . $_GET['wb' . $i] . '...';
          
          // remove the car order
          $sql = 'delete from car_orders where waybill_number = "' . $_GET['wb' . $i] . '"';
//print 'SQL: ' . $sql . '<br />';
          if (!mysqli_query($dbc, $sql))
          {
            print 'Unable to remove car order<br />';
            die();
          }
          
        }
        print '<br /><br />';
      }

      // display the list of unfinished waybills
      $sql = 'select car_orders.waybill_number as waybill_number, 
              shipments.code as shipment, 
              routing.station as station,
              locations.code as location, 
              cars.reporting_marks as reporting_marks, 
              cars.status as status
         from car_orders, cars, shipments, locations, routing
        where cars.id = car_orders.car
          and car_orders.shipment = shipments.Id
          and cars.status = "Empty"
          and cars.current_location_id = shipments.unloading_location
          and locations.id = cars.current_location_id
          and routing.id = locations.station
     order by waybill_number';
              
      $rs = mysqli_query($dbc, $sql);
      
      // if we found some open waybills, ask the user to fix them
      if (mysqli_num_rows($rs) > 0)
      {
        print '<form action="fix_waybills.php" method="get">';
        print '<input type="submit" id="fix_it_btn" name="fix_it_btn" value="FIX WAYBILLS">&nbsp;';
    
        print 'Check/Uncheck all waybills: <input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
        print '<table id="wb_table" name="wb_table">';
        print '<tr>
                 <th>Fix?</th>
                 <th>Waybill Number</th>
                 <th>Shipment</th>
                 <th><u>Unloading Station</u><br />Location</th>
                 <th>Reporting<br />Marks</th>
                 <th>Status</th>
               </tr>';
        $row_counter = 0;
        while ($row = mysqli_fetch_array($rs))
        {
          print '<tr>
                   <td style="text-align: center;">
                     <input type="checkbox" id="check' . $row_counter . '" name="check' . $row_counter . '">
                   </td>
                   <td>' . $row['waybill_number'] . '
                     <input type="hidden" id="wb' . $row_counter . '" name="wb' . $row_counter . '" value="' . $row['waybill_number'] . '">
                   </td>
                   <td>' . $row['shipment'] . '</td>
                   <td><u>' . $row['station'] . '</u><br />' . $row['location'] . '</td>
                   <td>' . $row['reporting_marks'] . '</td>
                   <td>' . $row['status'] . '</td>
                 </tr>';
          $row_counter++;
        }
        print '</table>';
        print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
      
        print '</form>';
      }
      else
      {
        print '<br />No Incomplete Waybills found.';
      }
    ?>

</body>
</html>


