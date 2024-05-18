<?php
  // edit_locations.php

  // edits the selected row in the location table if the Update button was clicked
  // it replaces only those items where a new value was submitted

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Locations";</script>';
  
  // generate a javascript line to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Location Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=locations";</script>';

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
      // build a query to remove the selected location
      $sql = 'delete from locations where id = "' . $_GET["obj_id"] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = '<br />Delete Error: ' . mysqli_error($dbc);
      }
      else
      {
        // if the delete was successful, return to the list_locations page
        header('Location: db_list.php?tbl_name=locations');
        exit();
      }
    }
    else
    {
      // this must be an update operation
      // build the update query based on the contents of the input text boxes
      $sql = 'update locations set ';
      $first_field = true;

      // check each incoming input text box and add it to the update if there's something there
      if (strlen($_GET['obj_name']) > 0)
      {
        $sql .= 'code = "' . $_GET["obj_name"] . '" ';
        $first_field = false;
      }

      if (strlen($_GET["station"]) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'station = "' . $_GET['station'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['track']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ", ";
        }
        $sql .= 'track = "' . $_GET['track'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['spot']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'spot = "' . $_GET['spot'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['rpt_station']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'rpt_station = "' . $_GET['rpt_station'] . '" ';
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

      if (strlen($_GET['color']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'color = "' . $_GET['color'] . '" ';
        $first_field = false;
      }

      // run the update query if at least one field is to be updated
      if (!$first_field)
      {
        $sql .= 'where id = "' . $_GET['obj_id'] . '"';
        if (mysqli_query($dbc, $sql))
        {
          $sql_msg =  '<br />Transaction completed<br /><br />';
        }
        else
        {
          $sql_msg =  '<br />Update Error: ' . mysqli_error($dbc);
        }
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="locations" type="hidden">';

  // query the database for the properties of the selected location and display them in a table
  $sql = 'select locations.id as id,
                 locations.code as code, 
                 locations.station as loc_station, 
                 locations.track as track, 
                 locations.spot as spot, 
                 locations.rpt_station as rpt_station,
                 locations.remarks as remarks, 
                 locations.color as color,
                 routing.station as station
	      from locations, routing
		  where locations.station = routing.id
		    and locations.id = "' . $_GET['obj_id'] . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);

  // generate a hidden field to send this form's previous object name to itself when it's refreshed
  print '<input id="obj_id" name="obj_id" value="' . $_GET['obj_id'] . '" type="hidden">';

  print
    '<table>
      <tr>
        <th>Property</th>
        <th>Current Value</th>
        <th>New Value</th>
      </tr>
      <tr>
        <td>Location Code</td>
        <td>' . $row['code'] . '</td>
        <td><input id="obj_name" name="obj_name" type="text"></td>
      </tr>
      <tr>
        <td>Station</td>
        <td>' . $row['station'] . '</td>
        <td>' . drop_down_stations("station", "", "") . '</td>
      </tr>
      <tr>
        <td>Track</td>
        <td>' . $row['track'] . '</td>
        <td><input name="track" type="text"></td>
      </tr>
      <tr>
        <td>Spot</td>
        <td>' . $row['spot'] . '</td>
        <td><input name="spot" type="text"></td>
      </tr>
      <tr>
        <td>Rpt Station</td>
        <td>' . $row['rpt_station'] . '</td>
        <td><input name="rpt_station" type="text"></td>
      </tr>
      <tr>
        <td>Remarks</td>
        <td>' . $row['remarks'] . '</td>
        <td><input name="remarks" type="text"></td>
      </tr>
      <tr>
        <td>Color</td>
        <td style="background-color: ' . $row['color'] . '"></td>
        <td>' . drop_down_colors("color", "") . '</td>
      </tr>
    </table>';

  // display a status message
  print $sql_msg;

  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("obj_name").focus();</script>';
  
  // generate some javascript to pre-select the locations current color on that drop-down list
  print '<script>
           document.getElementById("color").value = "' . $row['color'] . '";
         </script>';

?>
