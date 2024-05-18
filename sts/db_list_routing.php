<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<?php
  // list_routing.php

  // adds a new station code to the routing table if the Update button was
  // clicked and if there is a code in the station code text box.

  // generate some javascript to display the table name, identify this table to the form, and set the update button's tab index
  print '<script>
           document.getElementById("table_name").innerHTML = "Stations and Routing Instructions";
           document.getElementById("tbl_name").value = "routing";
           document.getElementById("update_btn").tabIndex = "5";
         </script>';

  // get a database connection
  $dbc = open_db();

  // has the submit button been clicked?
  if (isset($_POST['update_btn']))
  {
    // yes, so check to see if there is anything in the input text boxes
    if (strlen($_POST['station_name']) > 0)
    {
      // add the new station code to the routing table
      // station_nbr is now used to identify the default set-out location for each particular station
      $sql = 'insert into routing (station, station_nbr, instructions, sort_seq, color1, color2)
              values ("' . $_POST['station_name'] . '", 0, "' . $_POST['instructions'] . '", "' . intval($_POST['sort_seq']) . '", 0, 0)';

      $rs = mysqli_query($dbc, $sql);
    }
  }

  // query the database for all of the station codes and display them in a table
  $sql = "select routing.id, routing.station, routing.station_nbr, routing.instructions, routing.sort_seq, locations.code
            from routing
       left join locations on routing.station_nbr = locations.id
           order by sort_seq, station";
  $rs = mysqli_query($dbc, $sql);

  print '<table class="sortable">
           <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Add New Station</caption>
           <thead>
             <tr>
               <th>Station<br />Name</th>
               <th>Routing Instructions</th>
               <th>Sort<br />Seq.</th>
               <th></th>
             </tr>
             <tr>
               <td><input id="station_name" name="station_name" type="text" tabindex="1" required autofocus></td>
               <td><textarea name="instructions" type="text" tabindex="3" cols="100" rows="5"></textarea></td>
               <td><input id="sort_seq" name="sort_seq" type="text" tabindex="4" style="width: 75px;"></td>
               <td></td>
             </tr>
             <tr>
               <td style="border:0px;">
               </td>
             </tr>
             <tr style="position: sticky; top: 0; background-color: #F5F5F5">
               <th><i>Station<br />Name</i></th>
               <th><i>Routing Instructions</i></th>
               <th><i>Sort<br />Seq.</i></th>
               <th><i>Default Set-Out Location</i></th>
             </tr>
           </thead>';

  if (mysqli_num_rows($rs) > 0)
  {
    while ($row = mysqli_fetch_array($rs))
    {
      print '<tr>
               <td><a href="db_edit.php?tbl_name=routing&obj_id=' . $row['id'] . '&obj_name=' . $row['station'] . '">' . $row['station'] . '</a></td>
               <td>' . $row['instructions'] . '</td>
               <td style="text-align: center;">' . $row['sort_seq'] . '</td>
               <td>' . $row['code'] . '</td>
             </tr>';
    }
  }
  print '</table>';

  // add some extra lines to the instructions div
  print '<script>
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML + "Click on column titles shown in <i>italics</i> to sort the table<br /><br />";
         </script>';
  
  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("station_code").focus();</script>';

?>
