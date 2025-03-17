<?php
  // edit_shipments.php

  // edits the selected row in the shipment table if the Update button was clicked
  // it replaces only those items where a new value was submitted

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Shipments";</script>';
  
  // generate a javascript line to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Shipment Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=shipments";</script>';

  // generate some javascript that will mark the shipment as being turned off by adding one million (1000000) to the last ship date
  print '<script>
           function set_ship_off()
           {
             var old_ship_date = parseInt(document.getElementById("old_last_ship_date").innerHTML, 10) || 0;
             document.getElementById("last_ship_date").value = old_ship_date + 1000000;
             document.getElementById("last_ship_date").readOnly = true;
           }
           
           function set_ship_on()
           {
             var old_ship_date = parseInt(document.getElementById("old_last_ship_date").innerHTML, 10) || 0;
             if (old_ship_date < 1000000)
             {
               old_ship_date = old_ship_date + 1000000;
             }
             document.getElementById("last_ship_date").value = old_ship_date - 1000000;
             document.getElementById("last_ship_date").readOnly = false;
           }
           
         </script>';

  // get a database connection
  $dbc = open_db();

  // initiate a database response message
  $sql_msg = '<br />Transaction completed';

  // has the submit button been clicked?
  if (isset($_GET['update_btn']))
  {
    // is this a remove operation?
    if ($_GET['update_remove_btn'] == 'remove')
    {
      // build a query to remove the selected shipment
      $sql = 'delete from shipments where id = "' . $_GET["obj_id"] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = '<br />Delete Error: ' . mysqli_error($dbc);
      }
      else
      {
        // if the delete was successful, return to the list_shipments page
        header('Location: db_list.php?tbl_name=shipments');
        exit();
      }
    }
    else
    {
      // this must be an update operation
      // build the update query based on the contents of the input text boxes
      $sql = 'update shipments set ';
      $first_field = true;

      // check each incoming input text box and add it to the update if there's something there
      if (strlen($_GET['obj_name']) > 0)
      {
        $sql .= 'code = "' . $_GET["obj_name"] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['description']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'description = "' . $_GET['description'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['consignment']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'consignment = "' . $_GET['consignment'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['car_code']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'car_code = "' . $_GET['car_code'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['loading_location']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'loading_location = "' . $_GET['loading_location'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['unloading_location']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'unloading_location = "' . $_GET['unloading_location'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['last_ship_date']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'last_ship_date = ' . $_GET['last_ship_date'] . ' ';
        $first_field = false;
      }

      if (strlen($_GET['min_interval']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'min_interval = ' . $_GET['min_interval'] . ' ';
        $first_field = false;
      }

      if (strlen($_GET['max_interval']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'max_interval = ' . $_GET['max_interval'] . ' ';
        $first_field = false;
      }

      if (strlen($_GET['min_amount']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'min_amount = ' . $_GET['min_amount'] . ' ';
        $first_field = false;
      }

      if (strlen($_GET['max_amount']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'max_amount = ' . $_GET['max_amount'] . ' ';
        $first_field = false;
      }

      if (strlen($_GET['special_instructions']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'special_instructions = "' . $_GET['special_instructions'] . '" ';
        $first_field = false;
      }

       if (strlen($_GET['remarks']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'remarks = "' . $_GET['remarks'] . '" ';
        $first_field = false;
      }

       if (strlen($_GET['min_load_time']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'min_load_time = "' . $_GET['min_load_time'] . '" ';
        $first_field = false;
      }

       if (strlen($_GET['max_load_time']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'max_load_time = "' . $_GET['max_load_time'] . '" ';
        $first_field = false;
      }

       if (strlen($_GET['min_unload_time']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'min_unload_time = "' . $_GET['min_unload_time'] . '" ';
        $first_field = false;
      }

       if (strlen($_GET['max_unload_time']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'max_unload_time = "' . $_GET['max_unload_time'] . '" ';
        $first_field = false;
      }

      // run the update query if at least one field is to be updated
      if (!$first_field)
      {
        $sql .= 'where id = "' . urldecode($_GET["obj_id"]) . '"';
        if (mysqli_query($dbc, $sql))
        {
          $sql_msg =  '<br />Transaction completed<br /><br />';
        }
        else
        {
          $sql_msg =  '<br />Update Error: ' . mysqli_error($dbc) . 'SQL: ' . $sql;
        }
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="shipments" type="hidden">';

  // query the database for the properties of the selected  car code and display them in a table
  $sql = 'select shipments.id as id,
                 shipments.code as code,
                 shipments.description as description,
                 shipments.consignment as consignment_id,
                 shipments.car_code as car_code_id,
                 shipments.loading_location as loading_location_id,
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
                 loc01.code as loading_location,
                 loc02.code as unloading_location
            from shipments
       left join commodities on shipments.consignment = commodities.id
       left join car_codes on shipments.car_code = car_codes.id
       left join locations loc01 on shipments.loading_location = loc01.id
       left join locations loc02 on shipments.unloading_location = loc02.id
	         where shipments.id = "' . $_GET['obj_id'] . '"'; 
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);

  // find out if the shipper is operating or not
  if ($row['last_ship_date'] > 1000000)
  {
    // if the last ship date is greater than one million, this shipment is off
    $ship = '';
    $no_ship = 'checked';
    $ship_status = 'OFF';
  }
  else
  {
    // if the last ship date is less than one million, this shipment is on
    $ship = 'checked';
    $no_ship = '';
    $ship_status = 'ON';
  }

  // generate a hidden field to send this form's previous object ID to itself when it's refreshed
  print '<input id="obj_id" name="obj_id" value="' . $_GET['obj_id'] . '" type="hidden">';

  print
    '<table>
      <tr>
        <th>Property</th>
        <th>Current Value</th>
        <th>New Value</th>
      </tr>
      <tr>
        <td>Shipment ID</td>
        <td>' . $row['code'] . '</td>
        <td><input id="obj_name" name="obj_name" type="text"></td>
      </tr>
      <tr>
        <td>Description</td>
        <td>' . $row['description'] . '</td>
        <td><input name="description" type="text"></td>
      </tr>
      <tr>
        <td>Commodity</td>
        <td>' . $row['consignment'] . '</td>
        <td>' . drop_down_commodities('consignment', '') . '</td>
      </tr>
      <tr>
        <td>Car Code</td>
        <td>' . $row['car_code'] . '</td>
        <td>' . drop_down_car_codes('car_code', '', 'wild_ok') . '</td>
      </tr>
      <tr>
        <td>Loading Location</td>
        <td>' . $row['loading_location'] . '</td>
        <td>' . drop_down_locations('loading_location', '', '') . '</td>
      </tr>
      <tr>
        <td>Unloading Location</td>
        <td>' . $row['unloading_location'] . '</td>
        <td>' . drop_down_locations('unloading_location', '', '') . '</td>
      </tr>
      <tr>
        <td>Last Ship Date</td>
        <td id="old_last_ship_date">' . $row['last_ship_date'] . '</td>
        <td><input id="last_ship_date" name="last_ship_date" type="text"</td>
      </tr>
      <tr>
        <td>Min Interval</td>
        <td>' . $row['min_interval'] . '</td>
        <td><input name="min_interval" type="text"></td>
      </tr>
      <tr>
        <td>Max Interval</td>
        <td>' . $row['max_interval'] . '</td>
        <td><input name="max_interval" type="text"></td>
      </tr>
      <tr>
        <td>Min Amount</td>
        <td>' . $row['min_amount'] . '</td>
        <td><input name="min_amount" type="text"></td>
      </tr>
      <tr>
        <td>Max Amount</td>
        <td>' . $row['max_amount'] . '</td>
        <td><input name="max_amount" type="text"></td>
      </tr>
      <tr>
        <td>Special Instructions</td>
        <td>' . $row['special_instructions'] . '</td>
        <td><input name="special_instructions" type="text"></td>
      </tr>
      <tr>
        <td>Remarks</td>
        <td>' . $row['remarks'] . '</td>
        <td><input name="remarks" type="text"></td>
      </tr>
      <tr>
        <td>Shipment On/Off</td>
        <td id="ship_status" name="ship_status">' . $ship_status . '</td>
        <td>ON <input id="ship_on" name="ship_on_off" type="radio" value="True" onchange="set_ship_on(); " '. $ship .'>
            OFF <input id="ship_off" name="ship_on_off" type="radio" value="False" onchange="set_ship_off(); " ' . $no_ship . '>
      </tr>
      <tr>
        <td>Min Load Time</td>
        <td>' . $row['min_load_time'] . '</td>
        <td><input name="min_load_time" type="text"></td>
      </tr>
      <tr>
        <td>Max Load Time</td>
        <td>' . $row['max_load_time'] . '</td>
        <td><input name="max_load_time" type="text"></td>
      </tr>
      <tr>
        <td>Min Unload Time</td>
        <td>' . $row['min_unload_time'] . '</td>
        <td><input name="min_unload_time" type="text"></td>
      </tr>
      <tr>
        <td>Max Unload Time</td>
        <td>' . $row['max_unload_time'] . '</td>
        <td><input name="max_unload_time" type="text"></td>
      </tr>
    </table>';

  // get this shipment's prioritized empty locations, if any
  $sql = 'select empty_locations.priority as priority,
                 locations.code as location
            from empty_locations, locations
           where shipment = "' . $_GET['obj_id'] . '"
             and locations.id = empty_locations.location
           order by priority';
  $rs = mysqli_query($dbc, $sql);
  if (mysqli_num_rows($rs) > 0)
  {
    $link = 'db_edit.php?tbl_name=empty_locations&obj_id=' . $_GET['obj_id'] . '&obj_name=' . $_GET['obj_name'];

    print '<br />';
    print 'Prioritized Empty Car Search Locations - 
           Click <a href="' . $link . '">here</a> to modify the priorities<br /><br />';
    print '<table>
           <tr>
           <th>Priority</th>
           <th>Location</th>
           </tr>';
    while ($row = mysqli_fetch_array($rs))
    {
      print '<tr>
               <td>' . $row[0] . '</td>
               <td>' . $row[1] . '</td>
             </tr>';
    }
    print '</table>';
  }
  else
  {
    print '<br />';
    print 'This shipment does not have any prioritized empty car locations';
  }

  // display a status message
  print $sql_msg;

  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("obj_name").focus();</script>';

?>
