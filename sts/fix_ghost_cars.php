<html>
  <head>
    <title>STS - Fix Ghost Cars</title>
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
        var row_count = document.getElementById('car_order_table').rows.length-1;
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
    <h3 >Fix Ghost Cars</h3>
    <div id="instructions">
    The following car orders are linked to cars that do not exist in the database.<br /><br />
    When the FIX GHOSTS button is clicked, all selected car orders will be removed.<br />
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
          if (isset($_GET['check' . $i]))
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
        }
        print '<br /><br />';
      }

      // display the list of orphans
      $sql = 'select waybill_number from car_orders where car > 0 and car not in (select id from cars) order by waybill_number';
              
      $rs = mysqli_query($dbc, $sql);
      
      // if we found some orphans, ask the user to fix them
      if (mysqli_num_rows($rs) > 0)
      {
        print '<form action="fix_ghost_cars.php" method="get">';
        print '<input type="submit" id="fix_it_btn" name="fix_it_btn" value="FIX GHOSTS">&nbsp;';
    
        print 'Check/Uncheck all car orders: <input type="checkbox" id="check_all" name="check_all" onchange="checkall();"><br /><br />';
        print '<table id="car_order_table" name="car_table">';
        print '<th>Fix?</th>
               <th>Waybill Number</th>';
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
                 </tr>';
          $row_counter++;
        }
        print '</table>';
        print '<input type="hidden" id="row_counter" name="row_counter" value="' . $row_counter . '">';
      
        print '</form>';
      }
      else
      {
        print '<br />No Ghost Cars were found.';
      }
    ?>

</body>
</html>


