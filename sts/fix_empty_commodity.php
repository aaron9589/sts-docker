<html>
  <head>
    <title>STS - Fix Empty Cars That May Still Be Loaded</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
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
    <h3 >Fix Empty Cars That May Still Be Loaded</h3>
    <div id="instructions">
    Cars having a status of Empty should not be shown to have a Commodity<br />
    assigned. Either their status should be changed to Loaded or their waybill<br />
    should be cancelled.<br /><br />
    When the FIX STATUS button is clicked, the desired action will be taken.<br />
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
          if (isset($_GET['load_or_cancel' . $i]))
          {
            if ($_GET['load_or_cancel' . $i] == 'load')
            {
              print '<br />Fixing ' . $_GET['car_name' . $i] . '... Setting status to Loaded...';
              // set the car's current status to loaded
              $sql = 'update cars set status = "Loaded" where id = ' . $_GET['car_id' . $i];
//print 'SQL: ' . $sql . '<br />';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Unable to update car current location<br />';
                die();
              }
            }
            else
            {
              print '<br />Fixing ' . $_GET['car_name' . $i] . '... Cancelling waybill ' . $_GET['waybill_number' . $i] . '...';
              // Cancel the waybill associated with this car
              $sql = 'delete from car_orders where waybill_number = "' . $_GET['waybill_number' . $i] . '"';
//print 'SQL: ' . $sql . '<br />';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Unable to update car current location<br />';
                die();
              }
            }
          }          
        }
        print '<br /><br />';
      }

      // display the list of cars in trains that still have current locations
      $sql = 'select cars.id as id,
                     cars.reporting_marks as reporting_marks,
                     cars.status as status,
                     car_orders.waybill_number,
                     commodities.code as commodity
                from cars, commodities, car_orders, shipments
               where cars.status = "Empty"
                 and cars.id = car_orders.car
                 and car_orders.shipment = shipments.id
                 and shipments.consignment = commodities.id
               order by cars.reporting_marks';

      $rs = mysqli_query($dbc, $sql);
      
      // if we found some empty cars that have loades, ask the user what to document
      if (mysqli_num_rows($rs) > 0)
      {
        print '<form action="fix_empty_commodity.php" method="get">';
        print '<input type="submit" id="fix_it_btn" name="fix_it_btn" value="FIX STATUS">&nbsp;<input type="reset" value="RESET"><br /><br />';
    
        print '<table id="car_table" name="car_table">';
        print '<thead>';
        print '<tr>';
        print '<th>Change Status<br />To Loaded?</th>
               <th>Cancel<br />Waybill?</th>
               <th>Reporting Marks</th>';
        print '</tr>';
        print '</thead>';
        $row_counter = 0;
        while ($row = mysqli_fetch_array($rs))
        {
          print '<tr>
                   <td style="text-align: center;">
                     <input type="radio" id="load_or_cancel' . $row_counter . '" name="load_or_cancel' . $row_counter . '" value="load">
                   </td>
                   <td style="text-align: center;">
                     <input type="radio" id="load_or_cancel' . $row_counter . '" name="load_or_cancel' . $row_counter . '" value="cancel">
                   </td>
                   <td>' . $row['reporting_marks'] . ' (' . $row['status'] . ') is assigned to Waybill ' . $row['waybill_number'] . ' and may be loaded with ' . $row['commodity'] . '
                     <input type="hidden" id="car_id' . $row_counter . '" name="car_id' . $row_counter . '" value="' . $row['id'] . '"> .
                     <input type="hidden" id="car_name' . $row_counter . '" name="car_name' . $row_counter . '" value="' . $row['reporting_marks'] . '">
                     <input type="hidden" id="waybill_number' . $row_counter . '" name="waybill_number' . $row_counter . '" value="' . $row['waybill_number'] . '">
                   </td>
                 </tr>';
          $row_counter++;
        }
        print '</table>';
        print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
      
        print '</form>';
      }
      else
      {
        print '<br />No Empty Cars were found that still have commodities assigned to them.';
      }
    ?>

</body>
</html>


