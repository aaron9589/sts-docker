<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<?php
  // list_locations_codes.php

  // adds a new location to the locations table if the Update button was
  // clicked and if there is a code in the location code text box.

  // generate some javascript to display the table name, identify this table to the form, and set the update button's tab index
  print '<script>
           document.getElementById("table_name").innerHTML = "Locations";
           document.getElementById("tbl_name").value = "locations";
           document.getElementById("update_btn").tabIndex = "8";

           function display_msg()
           {
             document.getElementById("sort_msg").innerHTML = "Sorting...";
           }
           
           function clear_msg()
           {
             document.getElementById("sort_msg").innerHTML = "&nbsp;";
           }             
         </script>';


  // get a database connection
  $dbc = open_db();

  // has the submit button been clicked?
  if (isset($_POST['update_btn']))
  {
    // yes, so check to see if there is anything in the first input text box
    if (strlen($_POST['code']) > 0)
    {
      // add the new location to the locations table
      $sql = 'insert into locations (code, station, track, spot, rpt_station, remarks, color)
	                        values ("' . $_POST['code'] . '",
                                  "' . $_POST['station'] . '",
                                  "' . $_POST['track'] . '",
                                  "' . $_POST['spot'] . '",
                                  "' . $_POST['rpt_station'] . '",
                                  "' . $_POST['remarks'] . '",
                                  "' . $_POST['color'] . '")';
//print $sql;
      $rs = mysqli_query($dbc, $sql);
    }
  }

  // query the database for all of the locations and display them in a table
  $sql = 'select locations.id as id,
                 locations.code as code, 
                 locations.station as loc_station, 
                 locations.track as track, 
                 locations.spot as spot, 
                 locations.rpt_station as rpt_station,
                 locations.remarks as remarks, 
                 locations.color as color,
                 routing.station as station
          from locations
          left join routing on locations.station = routing.id
		      order by routing.sort_seq, routing.station, code';
//print $sql;
  $rs = mysqli_query($dbc, $sql);

  print '<table class="sortable" id="loc_tbl">
          <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Add New Location</caption>
          <thead>
           <tr>
             <th>Station</th>
             <th>Location Code</th>
             <th>Track</th>
             <th>Spot</th>
             <th>Reporting<br />Station</th>
             <th>Remarks</th>
             <th>Color</th>
           </tr>
           <tr>
             <td>' . drop_down_stations('station', '1', '') . '</td>
             <td><input id="code" name="code" type="text" tabindex="2" required></td>
             <td><input name="track" type="text" tabindex="3"></td>
             <td><input name="spot" type="text" tabindex="4"></td>
             <td style="text-align: center;"><input name="rpt_station" type="text" tabindex="5" size="25"></td>
             <td style="text-align: center;"><input name="remarks" type="text" tabindex="6" size="25"></td>
             <td>' . drop_down_colors('color', '7', '') . '</td>
           </tr>
           <tr>
             <td colspan="7" style="border:0px;">
             </td>
           </tr>
           <tr style="position: sticky; top: 0; background-color: #F5F5F5">
             <th><i>Station</i></th>
             <th><i>Location Code</i></th>
             <th><i>Track</i></th>
             <th><i>Spot</i></th>
             <th><i>Reporting<br />Station</i></th>
             <th><i>Remarks</i></th>
             <th><i>Color</i></th>
           </tr>
         </thead>';

  if (mysqli_num_rows($rs) > 0)
  {
    while ($row = mysqli_fetch_array($rs))
    {
      print '<tr>';
      print '  <td>' . $row['station'] . '</td>';
      print '  <td><a href="db_edit.php?tbl_name=locations&obj_id=' . $row['id'] . '&obj_name=' . $row['code'] . '">' . $row['code'] . '</a></td>';
      print '  <td>' . $row['track'] . '</td>';
      print '  <td>' . $row['spot'] . '</td>';
      print '  <td>' . $row['rpt_station'] . '</td>';
      print '  <td>' . $row['remarks'] . '</td>';
      print '  <td style="background-color: ' . $row['color'] . '"></td>';
      print '</tr>';
    }
  }
  print "</table>";

  // add some extra lines to the instructions div
  print '<script>
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + "Click on column titles shown in <i>italics</i> to sort the table<br /><br />";
         </script>';
  
  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("code").focus();</script>';
?>
