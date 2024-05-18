<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<html>
  <head>
    <title>STS - Fill Car Orders</title>
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
    <h3>Fill Car Orders</h3>
    <div style="font: normal 15px Verdana, Arial, sans-serif;">
    Select the car order to fill by clicking on the order's <b>FILL</b> button. All empty cars that fit the order's<br />
    requirements will be displayed on a new page. On the new page assign the desired car to the car order<br />
    by clicking first on it's radio button and then on the <b>ASSIGN</b> button.<br /><br />
    Filters can be used to hide rows.  Click on column titles shown in italics to sort the table.<br /><br />
    Empty cars that meet the shipment's car code requirement be displayed with color codes and as follows:
    <ol>
      <li><span  style="color:white; background-color: Gray;">Cars</span> in this shipment's pool, regardless of their current location. Car orders associated with shipments<br />
      that are in car/shipment pooling arrangements are <span style="background-color:#ffff80;">highlighted.</span><br /><br /></li>
      <li><span  style="background-color: DarkGray;">Cars</span> currently at the same station as the shipper. If there are multiple eligible cars at the shipper's<br />
      station, they will be sorted by least used first. (Lowest load count)<br /><br /></li>
      <li><span  style="background-color: LightGray;">Cars</span> at locations that have been prioritized for this shipment, sorted in order of location priority and<br />
      then by least used first.<br /><br /></li>
      <li>All remaining eligible cars on the system will be displayed sorted by the least used first.</li>
    </ol>
    If there aren't any cars available that meet the shipment requirements, a message to that effect will be displayed.
    </div>
    <br />
    <form>

    <?php
    // generate some javascript that will hide rows
    // - filter_rows() is called to hide cars that don't have the selected property
    print '<script type="text/javascript">
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

                 var table = document.getElementById("wb_tbl");
                 
                 //iterate through rows
                 for (var i = 2, row; row = table.rows[i]; i++)
                 {
                   // hide all car orders except the type specified (A = Automatic, E = Reposition, M = Manual)
                   if ((tbl_col == 1) && (needle == "A"))
                   {
                     if((row.cells[tbl_col].innerText.substr(4,1) == "E") || (row.cells[tbl_col].innerText.substr(4,1)) == "M")
                     {
                       row.style.display = "none";
                     }
                   }
                   else if((tbl_col == 1) && ((needle == "E") || (needle == "M")))
                   {
                     if (row.cells[tbl_col].innerText.substr(4,1) != needle)
                     {
                       row.style.display = "none";
                     }
                   }
                   else
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
             }
           </script>';

      // bring in the function files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // get a database connection
      $dbc = open_db();

      // did we get here when a car was assigned to a car order?
      if (isset($_POST['car_id']))
      {
        // assign the selected car to the specified car order
        $sql = 'update car_orders
                set car = "' . $_POST['car_id'] . '"
                where waybill_number = "' . $_POST['wbnbr'] . '"';

        if (!mysqli_query($dbc, $sql))
        {
          print 'Update error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
        }

        // get the info that the history table needs
        $sql = 'select setting_value from settings where setting_name = "session_nbr"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_array($rs);
        $session_nbr = $row['setting_value'];
        
        $sql = 'select current_location_id from cars where id = "' . $_POST['car_id'] . '"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_array($rs);
        $location = $row['current_location_id'];
//print 'SQL: ' . $sql . ' Location: ' . $location . '<br /><br />';        
        // insert a car history record
        $sql = 'insert into history(car_id, session_nbr, event_date, event, location)
                values ("' . $_POST['car_id'] . '", 
                        "' . $session_nbr . '", 
                        "' . date("Y-m-d H:i:s") . '", 
                        "Filled car order ' . $_POST['wbnbr'] . '", 
                        "' . $location . '")';
                        
        if (!mysqli_query($dbc, $sql))
        {
          print 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
        }

        // check to see if the car is at it's loading location
        $sql = 'select count(*)
                from cars, shipments, car_orders
                where car_orders.waybill_number = "' . $_POST['wbnbr'] . '"
                  and shipments.id = car_orders.shipment
                  and cars.id = "' . $_POST['car_id'] . '"
                  and cars.current_location_id = shipments.loading_location
                  and cars.status = "Empty"';
// print 'SQL: ' . $sql . '<br /><br />';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
// print 'count: ' . $row[0] . '<br /><br />';
        if ($row[0] > 0)
        {
          // if it's at it's loading location, mark the car as "Loaded" and increment it's load count by 1
          $sql = 'update cars
                  set status = "Loaded",
                      load_count = load_count + 1
                  where id = "' . $_POST['car_id'] . '"';  // print 'Already loaded SQL: ' . $sql . '<br /><br />';
        }
        else
        {
          // otherwise, mark the assigned car as "Ordered" and increment it's load count by 1
          $sql = 'update cars
                  set status = "Ordered",
                      load_count = load_count + 1
                  where id = "' . $_POST['car_id'] . '"';  // print 'Ordering SQL: ' . $sql . '<br /><br />';
        }
        
        if (!mysqli_query($dbc, $sql))
        {
          print 'Update error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
        }
      }

      // pull in all of the car orders that do not have a car assigned
      $sql = 'select car_orders.waybill_number as waybill_number,
                     car_orders.shipment as shipment_id,
                     shipments.code as shipment,
                     shipments.description as description,
                     shipments.consignment as consignment_id,
                     shipments.car_code as car_code_id, 
                     shipments.loading_location as loading_location_id,
                     shipments.unloading_location as unloading_location_id,
                     shipments.remarks,
                     commodities.code as consignment,
                     car_codes.code as car_code,
                     loc01.code as loading_location,
                     loc02.code as unloading_location,
                     sta01.station as loading_station,
                     sta02.station as unloading_station
              from car_orders
              left join shipments on shipments.id = car_orders.shipment
              left join commodities on commodities.id = shipments.consignment
              left join car_codes on car_codes.id = shipments.car_code
              left join locations loc01 on loc01.id = shipments.loading_location
              left join locations loc02 on loc02.id = shipments.unloading_location
              left join routing sta01 on sta01.id = loc01.station
              left join routing sta02 on sta02.id = loc02.station
              where (car_orders.shipment = shipments.id
              and ((car_orders.car = "") or (car_orders.car is null)))
              order by car_orders.waybill_number';
      $rs = mysqli_query($dbc, $sql);

      // generate a table of the eligible car orders
      if (mysqli_num_rows($rs) > 0)
      {
        print '<table id="wb_tbl" class="sortable" style="white-space: nowrap;">
                 <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Row Filters</caption>
                 <thead>
                   <tr>
                     <th style="border-bottom:0px; border-right:0px;">
                       <button tabindex="4" type="submit" id="clear_filters_btn" name="clear_filters_btn"
                       onclick="location.reload();" style="font: bold 10px Verdana, Arial, sans-serif; text-align:center; background-color: #ffff00; font-size: 12px">
                         CLEAR<br />FILTERS
                       </button>
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(1, document.getElementById(\'wb_type_filter\').options[document.getElementById(\'wb_type_filter\').selectedIndex].value);
                         document.getElementById(\'wb_type_filter\').disabled=true;">
                         <select id="wb_type_filter" name="wb_type_filter" tabindex="5">
                           <option value=""></option>
                           <option value="A">Automatic</option>
                           <option value="M">Manual</option>
                           <option value="E">Reposition</option>
                         </select>
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(2, document.getElementById(\'shipment_filter\').options[document.getElementById(\'shipment_filter\').selectedIndex].text);
                                   document.getElementById(\'shipment_filter\').disabled=true;">' .
                                   drop_down_shipments('shipment_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(4, document.getElementById(\'commodity_filter\').options[document.getElementById(\'commodity_filter\').selectedIndex].text);
                                   document.getElementById(\'commodity_filter\').disabled=true;">' .
                                   drop_down_commodities('commodity_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(5, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                                   document.getElementById(\'car_code_filter\').disabled=true;">' .
                                   drop_down_car_codes('car_code_filter', '', 'no_wild') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(6, document.getElementById(\'loading_loc_filter\').options[document.getElementById(\'loading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'loading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('loading_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(7, document.getElementById(\'unloading_loc_filter\').options[document.getElementById(\'unloading_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'unloading_loc_filter\').disabled=true;">' .
                                   drop_down_locations('unloading_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px">
                     </th>
                   </tr>                  
                  <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                    <th class="sorttable_nosort">Click to<br />fill Car<br />Order</th>
                    <th><i>Waybill<br />Number</i></th>
                    <th><i>Shipment Code</i></th>
                    <th><i>Shipment Description</i></th>
                    <th><i>Consignment</i></th>
                    <th><i>Car<br />Code</i></th>
                    <th><i>Loading<br /><u>Station</u><br/>Location</i></th>
                    <th><i>Unloading<br /><u>Station</u><br />Location</i></th>
                    <th><i>Remarks</i></th>
                  </tr>
                </thead>';

        $row_count = 0;
        while ($row = mysqli_fetch_array($rs))
        {
          // if a car order / waybill is associated with a shipment that is in a pool arrangement with certain cars,
          // highlight the background-color
          $sql_pool = 'select count(*) from pool where shipment_id = "' . $row['shipment_id'] . '"';
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
          
          print '<tr style="background-color:' . $background . ';">
                  <td style="text-align: center; vertical-align: middle;">
                    <input name="fill' . $row_count . '" type="submit" value="FILL" formmethod="post" formaction="assign_car.php"
                     style="background-color: #80ff00; font-size: 24px;">
                  </td>
                  <td>' . $row['waybill_number'] . '<input name="wbnbr' . $row_count . '" type="hidden" value="' . $row['waybill_number'] . '"</td>
                  <td>' . $row['shipment'] . '</td>
                  <td>' . $row['description'] . '</td>
                  <td>' . $row['consignment'] . '</td>
                  <td>' . $row['car_code'] . '</td>
                  <td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>
                  <td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>
                  <td>' . $row['remarks'] . '</td>
                </tr>';
          $row_count++;
        }
        print '<input name="row_count" type="hidden" value="' . $row_count . '">';
      }
      else
      {
        print '<h3>There are no car orders that need to be filled.</h3>';
      }
    ?>
    </form>
  </body>
</html>
