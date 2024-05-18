<html>
  <head>
    <title>STS - Fix Orphaned Cars</title>
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
        var row_count = document.getElementById('car_table').rows.length-1;
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
    <h3 >Fix Orphaned Cars</h3>
    <div id="instructions">
    The following cars do not have valid unloading locations, even though they are attached to car orders<br />
    or they have a status code other than "Ordered."<br /><br />
    When the FIX CARS button is clicked, all selected cars will be set to "Empty" and the car order and <br />
  the waybill to which they are attached will be cancelled and removed.<br />
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
          print '<br />Fixing ' . $_GET['rm' . $i] . '...';
          
          // set the orphan car's status to empty
          $sql = 'update cars set status = "Empty" where id = "' . $_GET['car' . $i] . '"';
//print 'SQL: ' . $sql . '<br />';
          if (!mysqli_query($dbc, $sql))
          {
            print 'Unable to update cars table<br />';
            die();
          }
         
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

      // display the list of orphans
      $sql = 'select distinct cars.id,
                     cars.reporting_marks,
                     cars.status,
                     car_orders.waybill_number, 
                     car_orders.shipment
                from cars, car_orders
               where cars.id = car_orders.car
                 and car_orders.waybill_number like "___-E__"
                 and (car_orders.shipment not in (select locations.id from locations)
                      or cars.status != "Ordered")
               order by cars.reporting_marks';
              
      $rs = mysqli_query($dbc, $sql);
      
      // if we found some orphans, ask the user to fix them
      if (mysqli_num_rows($rs) > 0)
      {
        print '<form action="fix_orphans.php" method="get">';
        print '<input type="submit" id="fix_it_btn" name="fix_it_btn" value="FIX CARS">&nbsp;';
    
        print 'Check/Uncheck all cars: <input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
        print '<table id="car_table" name="car_table">';
        print '<th>Fix?</th>
               <th>Reporting Marks</th>
               <th>Waybill Number</th>
               <th>Problem</th>';
        $row_counter = 0;
        while ($row = mysqli_fetch_array($rs))
        {
          print '<tr>
                   <td style="text-align: center;">
                     <input type="checkbox" id="check' . $row_counter . '" name="check' . $row_counter . '">
                   </td>
                   <td>' . $row['reporting_marks'] . '
                     <input type="hidden" id="car' . $row_counter . '" name="car' . $row_counter . '" value="' . $row['id'] . '">
                     <input type="hidden" id="rm' . $row_counter . '" name="rm' . $row_counter . '" value="' . $row['reporting_marks'] . '">
                   </td>
                   <td>' . $row['waybill_number'] . '
                     <input type="hidden" id="wb' . $row_counter . '" name="wb' . $row_counter . '" value="' . $row['waybill_number'] . '">
                   </td>
                   <td>';
          if ($row['status'] != 'Ordered')
          {
            print 'Invalid status of ' . $row['status'];
          }
          else
          {
            print 'Missing Unloading Location';
          }            
          print '  </td>
                 </tr>';
          $row_counter++;
        }
        print '</table>';
        print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
      
        print '</form>';
      }
      else
      {
        print '<br />No orphaned cars found.';
      }
    ?>

</body>
</html>


