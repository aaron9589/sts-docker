<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<?php
  // list_shipments_codes.php

  // adds a new shipment to the shipments table if the Update button was
  // clicked and if there is a code in the location code text box.

  // set up some styles for the table
  print '<style>
           th, td {padding: 3px;}
         </style>';
  
  // generate some javascript to display the table name, identify this table to the program, and set the update button's tab index
  print '<script>
           document.getElementById("table_name").innerHTML = "Shipments";
           document.getElementById("tbl_name").value = "shipments";
           document.getElementById("update_btn").tabIndex = "18";
         </script>';

  // generate some javascript utility functions
  print '<script>
           function check_min_interval(min_interval)
           {
             // check for minimum interval > maximum interval
             if ((min_interval.trim().length > 0) && (document.getElementById("max_int").value.trim().length > 0))
             {
               if (min_interval > document.getElementById("max_int").value)
                 window.alert("Minimum interval must be less than or equal to Maximum interval");
             }
           }
           
           function check_max_interval(max_interval)
           {
             // check for maximum interval < minimum interval
             if ((max_interval.trim().length > 0) && (document.getElementById("min_int").value.trim().length > 0))
             {
               if (max_interval < document.getElementById("min_int").value)
                 window.alert("Minimum interval must be less than or equal to Maximum interval");
             }
           }
           
           function check_min_amount(min_amount)
           {
             // check for minimum amount > maximum amount
             if ((min_amount.trim().length > 0) && (document.getElementById("max_amt").value.trim().length > 0))
             {
               if (min_amount > document.getElementById("max_amt").value)
                 window.alert("Minimum amount must be less than or equal to Maximum amount");
             }
           }
           
           function check_max_amount(max_amount)
           {
             // check for maximum amount < minimum amount
             if ((max_amount.trim().length > 0) && (document.getElementById("min_amt").value.trim().length > 0))
             {
               if (max_amount < document.getElementById("min_amt").value)
                 window.alert("Minimum amount must be less than or equal to Maximum amount");
             }
           }

           function filter_rows(tbl_col, needle)
           {
             // hide rows that do not match this filter
             
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
               for (var i = 6, row; row = table.rows[i]; i++)
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
         </script>';
         
  // get a database connection
  $dbc = open_db();

  // has the submit button been clicked?
  if (isset($_POST['update_btn']))
  {
    // yes, so check to see if there is anything in the input text boxes
    if (strlen($_POST['code']) > 0)
    {
      // add the new shipment to the shipments table
      $sql = 'insert into shipments (code, 
                                    description, 
                                    consignment, 
                                    car_code, 
                                    loading_location, 
                                    unloading_location, 
                                    last_ship_date, 
                                    min_interval, 
                                    max_interval, 
                                    min_amount, 
                                    max_amount,
                                    special_instructions,
                                    remarks,
                                    min_load_time,
                                    max_load_time,
                                    min_unload_time,
                                    max_unload_time)	  
              values ("' . $_POST['code'] . '",
                      "' . $_POST['description'] . '",
                      "' . $_POST['consignment'] . '",
                      "' . $_POST['car_code'] . '",
                      "' . $_POST['loading_location'] . '",
                      "' . $_POST['unloading_location'] . '",
                      "' . $_POST['last_ship_date'] . '",
                      "' . $_POST['min_interval'] . '",
                      "' . $_POST['max_interval'] . '",
                      "' . $_POST['min_amount'] . '",
                      "' . $_POST['max_amount'] . '",
                      "' . $_POST['special_instructions'] . '",
                      "' . $_POST['remarks'] . '",
                      "' . $_POST['min_load_time'] . '",
                      "' . $_POST['max_load_time'] . '",
                      "' . $_POST['min_unload_time'] . '",
                      "' . $_POST['max_unload_time'] . '")';
      $rs = mysqli_query($dbc, $sql);
    }
  }

  // query the database for all of the shipments and display them in a table
  $sql = 'select shipments.id as id,
                 shipments.code as code,
                 shipments.description as description,
                 shipments.consignment as consignment_id,
                 shipments.car_code as car_code_id,
                 shipments.loading_location as loading_location_code_id,
                 shipments.unloading_location as unloading_location_id,
                 shipments.last_ship_date as last_ship_date,
                 shipments.min_interval as min_interval,
                 shipments.max_interval as max_interval,
                 shipments.min_amount as min_amount,
                 shipments.max_amount as max_amount,
                 shipments.special_instructions as special_instructions,
                 shipments.remarks as remarks,
                 shipments.min_load_time as min_load_time,
                 shipments.max_load_time as max_load_time,
                 shipments.min_unload_time as min_unload_time,
                 shipments.max_unload_time as max_unload_time,
                 commodities.code as consignment,
                 car_codes.code as car_code,
                 sta01.station as loading_station,
                 loc01.code as loading_location,
                 sta02.station as unloading_station,
                 loc02.code as unloading_location
            from shipments
            left join commodities on commodities.id = shipments.consignment
            left join locations loc01 on loc01.id = shipments.loading_location
            left join locations loc02 on loc02.id = shipments.unloading_location
            left join routing sta01 on sta01.id = loc01.station
            left join routing sta02 on sta02.id = loc02.station
            left join car_codes on car_codes.id = shipments.car_code
            order by shipments.code';
// print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // generate the table

  print '<table class="sortable" id="ship_tbl" style="font: normal 12px Verdana, Arial, sans-serif; white-space: nowrap;">
           <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Add New Shipment</caption>
           <thead>
             <tr>
               <th>Shipment<br />ID</th>
               <th>Description</th>
               <th>Consignment</th>
               <th>Car<br />Code</th>
               <th>Loading<br /><u>Station</u><br />Location</th>
               <th>Unloading<br /><u>Station</u><br />Location</th>
               <th>Last<br />Ship<br />Date</th>
               <th>Min<br />Int</th>
               <th>Max<br />Int</th>
               <th>Min<br />Amt</th>
               <th>Max<br />Amt</th>
               <th>Special<br />Instructions</th>
               <th>Remarks</th>
               <th>Min<br />Load<br />Time</th>
               <th>Max<br />Load<br />Time</th>
               <th>Min<br />Unload<br />Time</th>
               <th>Max<br />Unload<br />Time</th>
               <th>ON/OFF</th>
               <th>Empty Location<br />Search Priority</th>
             </tr>
             <tr>
               <td style="text-align:center;"><input id="code" name="code" type="text" tabindex="1" required autofocus></td>
               <td style="text-align:center;"><input name="description" type="text" tabindex="2"></td>
               <td style="text-align:center;">' . drop_down_commodities('consignment', 3) . '</td>
               <td style="text-align:center;">' . drop_down_car_codes('car_code', 4, 'wild_ok') . '</td>
               <td style="text-align:center;">' . drop_down_locations('loading_location', 5, '') . '</td>
               <td style="text-align:center;">' . drop_down_locations('unloading_location', 6, '') . '</td>
               <td style="text-align:center;"><input name="last_ship_date" type="text" size="1" tabindex="7" value="0" required style="text-align: center;"></td>
               <td style="text-align:center;"><input name="min_interval" type="text" size="1" tabindex="8" required onchange="check_min_interval(this.value);" id="min_int" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="max_interval" type="text" size="1" tabindex="9" required onchange="check_max_interval(this.value);" id="max_int" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="min_amount" type="text" size="1" tabindex="10" required onchange="check_min_amount(this.value);" id="min_amt" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="max_amount" type="text" size="1" tabindex="11" required onchange="check_max_amount(this.value);" id="max_amt" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="special_instructions" type="text" tabindex="12"></td>
               <td style="text-align:center;"><input name="remarks" type="text" tabindex="13"></td>
               <td style="text-align:center;"><input name="min_load_time" type="text" value="0" size="1" tabindex="14" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="max_load_time" type="text" value="0" size="1" tabindex="15" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="min_unload_time" type="text" value="0" size="1" tabindex="16" style="text-align: center;"></td>
               <td style="text-align:center;"><input name="max_unload_time" type="text" value="0" size="1" tabindex="17" style="text-align: center;"></td>
               <td></td>
               <td></td>
             </tr>
             <tr>
               <td colspan="19" style="border:0px;">&nbsp;</td>
             </tr>
             <tr>
              <td colspan="19" style="border:0px; font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Row Filters</td>
             </tr>
             <tr>
               <td style="border-bottom: 0px; border-right:0px; text-align:center;">
                 <input type="button" id="clear_filters_btn" name="clear_filters_btn" value="CLEAR FILTERS"
                 onclick="location.reload();" style="font: normal 10px Verdana, Arial, sans-serif; white-space: nowrap;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px; text-align:center;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px; text-align:center;"
                 onchange="filter_rows(2, document.getElementById(\'commodity_filter\').options[document.getElementById(\'commodity_filter\').selectedIndex].text);
                 document.getElementById(\'commodity_filter\').disabled = true;">' . 
                 drop_down_commodities('commodity_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px; text-align:center;"
                 onchange="filter_rows(3, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                 document.getElementById(\'car_code_filter\').disabled = true;">' . 
                 drop_down_car_codes('car_code_filter', '', 'wild_ok') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px; text-align:center;"
                 onchange="filter_rows(4, document.getElementById(\'loading_location_filter\').options[document.getElementById(\'loading_location_filter\').selectedIndex].text);
                 document.getElementById(\'loading_location_filter\').disabled = true;">' . 
                 drop_down_locations('loading_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px; text-align:center;"
                 onchange="filter_rows(5, document.getElementById(\'unloading_location_filter\').options[document.getElementById(\'unloading_location_filter\').selectedIndex].text);
                 document.getElementById(\'unloading_location_filter\').disabled = true;">' . 
                 drop_down_locations('unloading_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;">
               </td>
               <td style="border-bottom: 0px; border-left: 0px;">
               </td>
             </tr>
             <tr style="position: sticky; top: 0; background-color: #F5F5F5">
               <th><i>Shipment<br />ID</th>
               <th><i>Description</th>
               <th><i>Consignment</th>
               <th><i>Car<br />Code</th>
               <th><i>Loading<br /><u>Station</u><br />Location</th>
               <th><i>Unloading<br /><u>Station</u><br />Location</th>
               <th><i>Last<br />Ship<br />Date</th>
               <th><i>Min<br />Int</th>
               <th><i>Max<br />Int</th>
               <th><i>Min<br />Amt</th>
               <th><i>Max<br />Amt</th>
               <th><i>Special<br />Instructions</th>
               <th><i>Remarks</th>
               <th class="sorttable_nosort">Min<br />Load<br />Time</th>
               <th class="sorttable_nosort">Max<br />Load<br />Time</th>
               <th class="sorttable_nosort">Min<br />Unload<br />Time</th>
               <th class="sorttable_nosort">Max<br />Unload<br />Time</th>
               <th class="sorttable_nosort">ON/OFF</th>
               <th class="sorttable_nosort">Empty Location<br />Search Priority</th>
             </tr>
           </thead>';
         
  if (mysqli_num_rows($rs) > 0)
  {
    while ($row = mysqli_fetch_array($rs))
    {
      // if a shipment is in a pool arrangement with certain cars, highlight the background-color
      $sql_pool = 'select count(*) from pool where shipment_id = "' . $row['id'] . '"';
      $rs_pool = mysqli_query($dbc, $sql_pool);
      $row_pool = mysqli_fetch_array($rs_pool);
      if ($row_pool[0] > 0)
      {
        $background = '#ffff80';
      }
      else
      {
        $background = 'White';
      }
      
      print '<tr style="background-color:' . $background . ';">';
      print '  <td><a href="db_edit.php?tbl_name=shipments&obj_id=' . $row['id'] . '&obj_name=' . urlencode($row['code']) . '">' . $row['code'] . '</td>';
      print '  <td>' . $row['description'] . '</td>';
      print '  <td>' . $row['consignment'] . '</td>';
      print '  <td style="text-align: center;">' . $row['car_code'] . '</td>';
      print '  <td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
      print '  <td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>';
      print '  <td style="text-align: center">' . $row['last_ship_date'] . '</td>';
      print '  <td style="text-align: center">' . $row['min_interval'] . '</td>';
      print '  <td style="text-align: center">' . $row['max_interval'] . '</td>';
      print '  <td style="text-align: center">' . $row['min_amount'] . '</td>';
      print '  <td style="text-align: center">' . $row['max_amount'] . '</td>';
      print '  <td>' . $row['special_instructions'] . '</td>';
      print '  <td>' . $row['remarks'] . '</td>';
      print '  <td style="text-align: center">' . $row['min_load_time'] . '</td>';
      print '  <td style="text-align: center">' . $row['max_load_time'] . '</td>';
      print '  <td style="text-align: center">' . $row['min_unload_time'] . '</td>';
      print '  <td style="text-align: center">' . $row['max_unload_time'] . '</td>';
      if ($row['last_ship_date'] > 1000000)
      {
        print '  <td style="text-align:center;">OFF</td>';
      }
      else
      {
        print '  <td style="text-align:center;">ON</td>';
      }
      print '  <td style="text-align:center;">
                 <a href="db_edit.php?tbl_name=empty_locations&obj_id=' . $row['id'] . '&obj_name=' . urlencode($row['code']) . '">Add/Edit
               </td>';
      print '</tr>';
    }
  }
  print '</table>';

  // add some extra lines to the instructions div
  print '<script>
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + 
                                                               \'Filters can be used to hide rows. \';
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + 
                                                               \'Click on column titles shown in <i>italics</i> to sort the table<br /><br />\';
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML +
                                                               \'Shipments in a car/shipment pooling arrangement are <span style="background-color:#ffff80;">highlighted.</span><br /><br />\';
         </script>';
  
  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("code").focus();</script>';
?>
