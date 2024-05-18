<?php
  // edit_special_pool.php

  // edits the selected cars's list of shipments to which it is assigned
  // it replaces only those items where a new value was submitted

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Edit Special Shipment Pool Cars";</script>';
  
  // generate some javascript to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Car Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=cars";</script>';
  
  // get a database connection
  $dbc = open_db();

  // decode the incoming car reporting marks and ID
  if (isset($_GET['obj_name']))
  {
    $obj_name = urldecode($_GET['obj_name']);
  }
  else
  {
    $obj_name = $_GET['prev_obj_name'];
  }

  if (isset($_GET['obj_id']))
  {
    $obj_id = $_GET['obj_id'];
  }
  
  // generate some javascript to remove the Update/Remove radio buttons since we don't need it for this page
  print '<script>var parent=document.getElementById("update_remove_btn").parentNode; var child=document.getElementById("update_remove_btn"); parent.removeChild(child);</script>';

  // generate some javascript to replace the boilerplate instructions with proper ones for this page
  $instructions = '<h3>Reporting Marks: ' . $obj_id . '</h3>';
  $instructions .= 'A car assigned to a special pool of shipments can only be used to fill to a car order<br />generated by one of those shipments<br /><br />';
  $instructions .= 'After selecting a shipment to be added to the pool to which this car is assigned, click<br />the UPDATE button.<br /><br />';
  $instructions .= 'To remove a shipment from the list, put a check mark in the the REMOVE checkbox<br />of the car to be removed and click the Update button.<br /><br />';
  $instructions .= 'Cars in a special pool CANNOT be assigned to car orders from outside of the pool.<br /><br />';
  print '<script>document.getElementById("instructions").innerHTML = "' . $instructions . '";</script>';
         
  // has the submit button been clicked?
  if (isset($_GET['update_btn']))
  {
    // check the location priority table
    if (isset($_GET['row_count']) && ($_GET['row_count'] > 0))
    {
      // first, delete all of this shipment's search locations from the table
      // then go through each of the existing rows coming in from the web page
      // insert any row that doesn't have a sequence number of zero
      // including anything in the input text boxes

      // delete the existing rows
      $sql = 'delete from pool where car_id = "' . $_GET['obj_name'] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = 'Delete error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
      }
      else
      {
        // loop through the existing lines on the web page and put them back into this car's pool of shipments
        for ($i=0; $i<$_GET['row_count']; $i++)
        {
         // if this shipment's checkbox isn't checked, put it back into the table
         // if this shipment's checkbox is checked, skip it (this deletes it from the list of steps)
         if (!isset($_GET['checkbox' . $i]))
         {
            // build the sql insert command
            $sql = 'insert into pool (car_id, shipment_id)
                    values ("' . $_GET['obj_name'] . '",
                            "' . $_GET['shipment' . $i] . '")';

            if (!mysqli_query($dbc, $sql))
            {
              $sql_msg = 'Insert Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
            }
          }
        }
      }
    }
    // if there's new priority and location information in the text boxes, insert that information as well
    if ($_GET['new_shipment_id'] > 0)
    {
      // build the sql insert query
      $sql = 'insert into pool (car_id, shipment_id)
              values ("' . $_GET['obj_name'] . '",
                      "' . $_GET['new_shipment_id'] . '")';
                         
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="pool" type="hidden">';

  // generate a hidden field to send this form's previous object name to itself when it's refreshed
  print '<input id="prev_obj_name" name="prev_obj_name" value="' . $obj_name . '" type="hidden">';
  print '<input id="prev_obj_id" name="prev_obj_id" value="' . $obj_id . '" type="hidden">';

  // set up input fields for new entries to the table and the column headings
  print '<table>
           <caption style="font: bold 15px Verdana, Arial, sans-serif;">Add new Shipment to Pool</caption>
           <thead>
             <tr>
               <td style="border-right:0px;">Shipment:</td>
               <td style="border-left:0px;">' . drop_down_ship_desc('new_shipment_id', 1, '') . '</td>
             </tr>
             <tr>
             <td colspan="2" style="border:0px;"></td>
             </tr>
             <tr>
               <th>Remove</th>
               <th>Shipment</th>
             </tr>
           </thead>';

  // query the database for the list of empty car location search priorities for this shipment
  $sql = 'select pool.shipment_id as shipment_id,
                 shipments.code as shipment_code,
                 shipments.description as description
            from pool, shipments
           where pool.car_id = ' . $_GET['obj_name'] . '
             and shipments.id = pool.shipment_id';
// print 'SQL: ' . $sql . '<br /><br />';
  $rs = mysqli_query($dbc, $sql);

  // build the table of shipments in this car's pool
  $row_count = 0;
  if (mysqli_num_rows($rs) > 0)
  {
    while ($row = mysqli_fetch_array($rs))
    {
      print '<tr>
               <td style="text-align: center;">
                 <input name="checkbox' . $row_count . '" type="checkbox" style="text-align: center;">
               </td>
               <td>
                 <input name="shipment' . $row_count . '" type="hidden" value="' . $row['shipment_id'] . '">' . $row['shipment_code'] . ' - ' . substr($row['description'], 0, 20) . '
               </td>
             </tr>';
      $row_count++;
    }
  }
  else
  {
    print '<tr><td colspan="2" style="text-align:center;">This car is not assigned<br />to any pool of shipments</td></tr>';
  }

  print '</table>';

  // store the number of rows in the table for future use
  print '<input name="row_count" id="row_count" type="hidden" value="' . $row_count . '">';

  // pass the shipment name and ID on to this form
  print '<input name="obj_name" type="hidden" value="' . $obj_name . '">
         <input name="obj_id" type="hidden" value="' . $obj_id . '">';

?>
