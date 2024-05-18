<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<html>
  <head>
    <title>STS-Generate Car Orders</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
  </head>
  <body style="margin-left: 50px;">
<img src="ImageStore/GUI/Menu/operations.jpg" width="716" height="145" border="0" usemap="#Map2">
<map name="Map2">
  <area shape="rect" coords="568,5,712,46" href="index.html">
  <area shape="rect" coords="570,97,710,138" href="index-t.html">
  <area shape="rect" coords="568,52,717,93" href="operations.html">
</map>
<h2>Simulation Operations</h2>
<h3>Generate Car Orders</h3>
    Select "Automatic" and then click on the AUTOMATIC button to increment the current operating session number and automatically generate car orders.<br /><br />
    Select "Manual", choose shipments to order cars, and click on the MANUAL button to order cars for those shipments.<br /><br />
    Automatic <input type="radio" name="gen_type" id="gen_auto" value="Auto" onchange="show_auto();">&nbsp;&nbsp;
    Manual <input type="radio" name="gen_type" id="gen_manual" value="Manual" onchange="show_manual();"><br /><br />

<script type="text/javascript">
  function show_auto()
  {
    document.getElementById("automatic").style.display = "block";
    document.getElementById("manual").style.display = "none";
  }
 
  function show_manual()
  {
    document.getElementById("manual").style.display = "block";
    document.getElementById("automatic").style.display = "none";
  }
  
  function confirm_manual_order()
  {
    alert('Click "OK" to order these cars. Otherwise click the browser back button to cancel.');
  }

  // generate some javascript that will hide rows
  // - filter_rows() is called to hide cars that don't have the selected property
  function filter_rows(tbl_col, needle)
  {
    // confirm that a non-blank option has been selected
    if (needle.length > 0)
    {
      // convert drop-down locations (station - location) to table locations (station\nlocation)
      var hyphen_loc = needle.search(" - ");
      if (hyphen_loc >= 0)
      {
        var new_needle = needle.substr(0, hyphen_loc) + "\n" + needle.substr(hyphen_loc + 3, needle.length);
        needle = new_needle;
      }

      var table = document.getElementById("ship_tbl");
             
      //iterate through rows
      for (var i = 2, row; row = table.rows[i]; i++)
      {
        var haystack_length = row.cells[tbl_col].innerText.length;
        var needle_length = needle.length;
        var match_start = haystack_length - needle_length;
             
        var haystack = row.cells[tbl_col].innerText.substr(match_start);
                 
        if (haystack != needle)
        {
          row.style.display = "none"
        }
      }
    }
  }
  
  function clear_filters()
  {
    document.location.reload();
  }
</script>

<?php
      // this program generates car orders

      // bring in the function files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // get a database connection
      $dbc = open_db();

      // bring in and display the current operating session number
      $sql = 'select setting_value from settings where setting_name = "session_nbr"';
      $rs = mysqli_query($dbc, $sql);
      if (mysqli_num_rows($rs) < 1)
      {
        print 'Setting not found - Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
      }
      $row = mysqli_fetch_array($rs);
      $session_number = $row['setting_value'];

//-------------------------------------------- process the request -------------------------------------------

      if (isset($_POST['autogenerate_btn']))      // --------------------------------------------- was the "Auto Generate" button clicked?
      {
        // increment the operating session number and store it in the settings table
        $session_number++;

        // display the new operating session number
        print 'Auto-generating car orders for  Operating Session ' . $session_number . '...</br><br >';

        $sql = 'update settings set setting_value = ' . $session_number . ' where setting_name = "session_nbr"';
        if (!mysqli_query($dbc, $sql))
        {
          print 'Setting not found - Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
        }

        // initialize a counter for the number of waybills generated this session
        $waybill_counter = 0;

        // go through the shippers and generate car orders as appropriate
        $sql = 'select id as id,
                       shipments.code as code,
                       shipments.last_ship_date as last_ship_date,
                       shipments.min_interval as min_interval,
                       shipments.max_interval as max_interval,
                       shipments.min_amount as min_amount,
                       shipments.max_amount as max_amount
                  from shipments';
        $rs_shipments = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($rs_shipments) > 0)
        {
          while ($row = mysqli_fetch_array($rs_shipments))
          {
            // do the math
            $last_ship_date = $row['last_ship_date'];
            $min_interval = $row['min_interval'];
            $max_interval = $row['max_interval'];
            $min_amount = $row['min_amount'];
            $max_amount = $row['max_amount'];

            // find a random number between the min and max intervals and round any fraction either up or down
            $interval = round(mt_rand($min_interval * 100, $max_interval * 100)/100);

            // add the random number to the last ship date
            $ship_date = $last_ship_date + $interval;

            // is it time to ship?
            if ($ship_date <= $session_number)
            {
              // store this session number as the new last ship date
              $sql = 'update shipments set last_ship_date = ' . $session_number . ' where id = "' . $row['id'] . '"';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Update Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
              }

              // determine the number of cars to order and round either up or down
              $num_cars = round(mt_rand($min_amount * 100, $max_amount * 100)/100);

              for ($i=0; $i<$num_cars; $i++)
              {
                // increment the waybill counter
                $waybill_counter++;

                // build the waybill number
                $wb_nbr = str_pad($session_number, 3, '0', STR_PAD_LEFT) . "-" . str_pad($waybill_counter, 3, '0', STR_PAD_LEFT);

                $sql = 'insert into car_orders (waybill_number, shipment, car) values ("' . $wb_nbr . '", "' . $row['id'] . '", "0")';
                if (!mysqli_query($dbc, $sql))
                {
                  print 'Insert Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
                }
              }
            }
          }
          // display the number of car orders created
          print $waybill_counter . ' car orders generated<br /><br />';
        }
      }
      elseif (isset($_POST['mangenerate_btn']))         // -------------------------------- was the "Manual Generate" button clicked?
      {
        // initialize the waybill counter
        $sql = 'select max(substr(waybill_number, 6, 2)) from car_orders where waybill_number like "' . str_pad($session_number, 3, '0', STR_PAD_LEFT) . '-M__"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $order_counter = $row[0];
// print "Order counter: " . $order_counter . "<br />";
        // loop through the shipments and order cars for each one that was checkedmarked
        for ($i=0; $i<$_POST['row_count']; $i++)
        {
          if (isset($_POST['select' . $i]))
          {
// print 'Ordering cars for row ' . $i . '<br />';
            
            // get the things we need to create the waybills
            $shipment_id = $_POST['id' . $i];
            $min_amount = $_POST['min_amt' . $i];
            $max_amount = $_POST['max_amt' . $i];

            // store this session number as the new last ship date
            $sql = 'update shipments set last_ship_date = ' . $session_number . ' where id = "' . $_POST['id' . $i] . '"';
            if (!mysqli_query($dbc, $sql))
            {
              print 'Update Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
              die();
            }

            // determine the number of cars to order and round either up or down
            $num_cars = round(mt_rand($min_amount * 100, $max_amount * 100)/100);
// print 'Ordering ' . $num_cars . '<br />';
            for ($j=0; $j<$num_cars; $j++)
            {
              // increment the waybill counter
              $order_counter++;

              // build the waybill number
              $wb_nbr = str_pad($session_number, 3, '0', STR_PAD_LEFT) . '-M' . str_pad($order_counter, 2, '0', STR_PAD_LEFT);
// print 'Generating Waybill ' . $wb_nbr . '<br />';
              $sql = 'insert into car_orders (waybill_number, shipment, car) values ("' . $wb_nbr . '", "' . $shipment_id . '", "0")';
// print 'SQL: ' . $sql . '<br /.';
              if (!mysqli_query($dbc, $sql))
              {
                print 'Insert Error: [' . mysqli_error($dbc) . '] SQL: ' . $sql . '<br /><br />';
                die();
              }
            }
          }
        }
      }

//-------------------------------------------- automatic generation -------------------------------------------

      // set up the auto-generate div
      print '<div name="automatic" id="automatic" style="display:none;">';
      
      // start the auto-generate form
      print '<form name="automatic" id="automatic" method="post" action="generate.php">';
      print 'Ready...<br /><br />';
      print '<input name="autogenerate_btn" id="autogenerate_btn" value="AUTOMATIC" type="submit"
             style="background-color: #80ff00; font-size: 24px;"><br /><br />';
      print '</form>';
      
      print '</div>';

//----------------------------------- manual generation division -------------------------------

      // set up the manual car order generation div
      print '<div id="manual" name="manual" style="display:none;">';
        
      // start the manual generation form
      print '<form name="manual" id="manual" method="post" action="generate.php">';
    
      // pull in all shipments
      $sql = 'select shipments.id as id,
                     shipments.code as code,
                     shipments.description as description,
                     shipments.last_ship_date as last_ship_date,
                     shipments.min_interval as min_interval,
                     shipments.max_interval as max_interval,
                     shipments.min_amount as min_amount,
                     shipments.max_amount as max_amount,
                     commodities.code as commodity,
                     car_codes.code as car_code,
                     loc01.code as loading_location,
                     sta01.station as loading_station,
                     loc02.code as unloading_location,
                     sta02.station as unloading_station
                from shipments
                left join commodities on commodities.id = shipments.consignment
                left join car_codes on car_codes.id = shipments.car_code
                left join locations loc01 on loc01.id = shipments.loading_location
                left join routing sta01 on sta01.id = loc01.station
                left join locations loc02 on loc02.id = shipments.unloading_location
                left join routing sta02 on sta02.id = loc02.station
               order by shipments.code';
                
      $rs_shipments = mysqli_query($dbc, $sql);
      if (mysqli_num_rows($rs_shipments) > 0)
      {
        print 'Ready...<br /><br />';
        // put a submit button on the top and the bottom of the div
        print '<input name="mangenerate_btn" value="MANUAL" type="submit" onmouseup="confirm_manual_order();" 
               style="background-color: #80ff00; font-size: 24px;"><br /><br />';
        // first, set up a table header row with drop down boxes that will determine which rows are displayed
        print '<table id="ship_tbl" class="sortable" style="white-space: nowrap;">
                 <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Row Filters</caption>
                 <thead>
                   <tr>
                     <th style="border-bottom:0px; border-right:0px; text-align: left;" colspan=3>
                       <input tabindex="4" type="button" id="clear_filters_btn" name="clear_filters_btn" value="CANCEL SORTING AND FILTERS"
                       onclick="clear_filters();" style="font: bold 10px Verdana, Arial, sans-serif; text-align:left;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(3, document.getElementById(\'commodity_filter\').options[document.getElementById(\'commodity_filter\').selectedIndex].text);
                                   document.getElementById(\'commodity_filter\').disabled=true;">' .
                                   drop_down_commodities('commodity_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(4, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                                   document.getElementById(\'car_code_filter\').disabled=true;">' .
                                   drop_down_car_codes('car_code_filter', '', 'no_wild') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(5, document.getElementById(\'loading_loc_filter\').options[document.getElementById(\'loading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'loading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('loading_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(6, document.getElementById(\'unloading_loc_filter\').options[document.getElementById(\'unloading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'unloading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('unloading_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px">
                     </th>
                   </tr>';

        // display the column headings - locked so they won't scroll with the table rows    
        print '      <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th class="sorttable_nosort">Select</th>
                     <th><i>Shipment<br />Code</th>
                     <th><i>Description</th>
                     <th><i>Commodity</th>
                     <th><i>Car<br />Code</th>
                     <th><i>Loading<br />Location</th>
                     <th><i>Unloading<br />Location</th>
                     <th><i>Last<br />Ship<br /> Date</th>
                     <th><i>Min<br />Int</th>
                     <th><i>Max<br />Int</th>
                     <th><i>Min<br />Amt</th>
                     <th><i>Max<br />Amt</th>
                 </tr>
                </thead>';

        $row_count = 0;
        while ($row = mysqli_fetch_array($rs_shipments))
        {
          print '<tr>
                   <td style="text-align:center;"><input type="checkbox" name="select' . $row_count . '" id="select' . $row_count . '"></th>
                   <td>' .
                     $row['code'] . '<input type="hidden" name="id' . $row_count . '" id="id' . $row_count . '" value="' . $row['id'] . '">
                   </td>
                   <td>' . $row['description'] . '</td>
                   <td>' . $row['commodity'] . '</td>
                   <td style="text-align:center;">' . $row['car_code'] . '</td>
                   <td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>
                   <td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>
                   <td style="text-align:center;">' . $row['last_ship_date'] . '</td>
                   <td style="text-align:center;">' . $row['min_interval'] . '</td>
                   <td style="text-align:center;">' . $row['max_interval'] . '</td>
                   <td style="text-align:center;">' .
                     $row['min_amount'] . '<input type="hidden" name="min_amt' . $row_count . '"id=min_amt' . $row_count . '" value="' . $row['min_amount'] . '">
                   </td>
                   <td style="text-align:center;">' .
                     $row['max_amount'] . '<input type="hidden" name="max_amt' . $row_count . '"id=max_amt' . $row_count . '" value="' . $row['max_amount'] . '"?
                   </td>
                 </tr>';
          $row_count++;
        }
        print '</table>';
        
        // save the row count for the next time around
        print '<input type="hidden" name="row_count" id="row_count" value="' . $row_count . '">';
        // put a submit button at the bottom as well as the top of the div
        print '<br /><input name="mangenerate_btn" value="MANUAL" type="submit" onclick="return confirm(\'Order these cars?\');"><br /><br />';
      }
      else
      {
        print 'No shipments found...<br />';
      }
      print '</form>';
      print '</div>';
    ?>
</body>
</html>
