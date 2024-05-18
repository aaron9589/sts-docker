<?php
  // edit_routing.php

  // edits the selected row in the stations and routing instructions table if the Update button was clicked
  // it replaces only those items where a new value was submitted

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Stations & Routing Instructions";</script>';

  // generate a javascript line to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Stations & Routing Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=routing";</script>';

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
      // build a query to remove the selected station
      $sql = 'delete from routing where id = "' . $_GET['obj_id'] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg =  '<br />Delete Error: ' . mysqli_error($dbc);
      }
      else
      {
        // if the delete was successful, return to the list_car_codes page
        //header('Location: db_list.php?tbl_name=routing');
        exit();
      }
    }
    else
    {
      // this must be an update operation
      // build the update query based on the contents of the input text boxes
      $sql = 'update routing set ';
      $first_field = true;

      // check each incoming input text box and add it to the update if there's something there
      if (strlen($_GET['obj_name']) > 0)
      {
        $sql .= 'station = "' . $_GET['obj_name'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['default_location']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'station_nbr = "' . $_GET['default_location'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['instructions']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'instructions = "' . $_GET['instructions'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['sort_seq']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'sort_seq = "' . $_GET['sort_seq'] . '" ';
        $first_field = false;
      }

      // run the update query if at least one field is to be updated
      if (!$first_field)
      {
        $sql .= 'where id = "' . $_GET['obj_id'] . '"';
        if (mysqli_query($dbc, $sql))
        {
          $sql_msg =  '<br />Transaction completed';
        }
        else
        {
          $sql_msg =  '<br />Update Error: ' . mysqli_error($dbc);
        }
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="routing" type="hidden">';

  // query the database for the properties of the selected  car code and display them in a table
  $sql = 'select routing.id, routing.station, routing.station_nbr, routing.instructions, routing.sort_seq, locations.code
            from routing
       left join locations on routing.station_nbr =  locations.id
           where routing.id = "' . $_GET['obj_id'] . '"';
           
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);

  // generate a hidden field to send this form's object ID to itself when it's refreshed
  print '<input name="obj_id" value="' . $row['id'] . '" type="hidden">';

  print
    '<table>
      <tr>
        <th>Property</th>
        <th>Current Value</th>
        <th>New Value</th>
      </tr>
      <tr>
        <td>Station Name</td>
        <td>' . $row['station'] . '</td>
        <td><input id="obj_name" name="obj_name" type="text"></td>
      </tr>
      <tr>
         <td>Default Set-Out Location</td>
        <td>' . $row['code'] . '</td>
        <td>' . drop_down_locations_at_station($row['id'], 'default_location', 1, '') . '</td>
      </tr>
      <tr>
       <td>Routing Instructions</td>
        <td>' . nl2br($row['instructions']) . '</td>
        <td><textarea name="instructions" rows="5" cols="50"></textarea></td>
      </tr>
      <tr>
        <td>Sort Sequence</td>
        <td>' . $row['sort_seq'] . '</td>
        <td><input id="sort_seq" name="sort_seq" type="text"></td>
      </tr>
    </table>';

  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("obj_name").focus();</script>';

  // display a status message
  print $sql_msg;

?>
