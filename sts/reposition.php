<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<html>
  <head>
    <title>STS - Reposition Cars</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top;}
      th {border: 1px solid black; padding: 10px;}
      td {border: 1px solid black; padding: 10px;}
      td.checkbox {text-align: center;}
    </style>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
    ?>
    
    <script>
      function filter_rows(tbl_col, needle)
      {
        // this script filters the contents of the table based on the user selections of car type, current location, or home location
        
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

          var table = document.getElementById("car_tbl");
          
          //iterate through rows
          for (var i = 2, row; row = table.rows[i]; i++)
          {
            var haystack_length = row.cells[tbl_col].innerText.length;
            var needle_length = needle.length;
            var match_start = haystack_length - needle_length;
           
            var haystack = row.cells[tbl_col].innerText.substr(match_start);
           
            if (haystack != needle)
            {
              row.style.display = "none";
            }
          }
        }
      }

      function show_all() // not currently used because it's not working... :(
      {
        // this script filters the contents of the table based on cars current locations compared to their home locations

        var table = document.getElementById("car_tbl");

        // iterate through the rows
        for (var i = 2, row; row = table.rows[i]; i++)
        {
          if (row.cells[3].innerText == row.cells[5].innerText)
          {
            row.style.display = "none";
          }
        }
      }
      
      function repo_to_home()
      {
        if (confirm("Reposition all cars not at their\nhome location to that destination?"))
        {
          // submit the request to generate car orders for the empty non-billed cars so they move to their home locations
          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function()
          {console.log("readyState = " + this.readyState + " this.status = " + this.status);
            if (this.readyState == 4 && this.status == 200)
            {
               show_car_order_count(this);
            }
          }
          var url = 'repo_to_home.php';
          xmlhttp.open('GET', url, false);
          xmlhttp.send();
        }
        else
        {
          alert("Reposition Cancelled");
        }
      }
      
      function show_car_order_count(xmlhttp)
      { console.log("Return handler called");
        alert(xmlhttp.responseText + " Non-Revenue Car Orders Generated");
        location.reload();
      }

    </script>
  </head>
  <body style="margin-left: 50px;">
  <p><img src="ImageStore/GUI/Menu/operations.jpg" width="716" height="145" border="0" usemap="#Map2">
    <map name="Map2">
      <area shape="rect" coords="568,5,712,46" href="index.html">
      <area shape="rect" coords="570,97,710,138" href="index-t.html">
      <area shape="rect" coords="568,52,717,93" href="operations.html">
    </map>
  </p>
<h2>Simulation Operations</h2>
    <h3>Reposition Empty Cars</h3>
    Select a destination for each empty car that is to be repositioned and then click the UPDATE button.<br />
    Leave the destination blank if the car is to remain at it's current location.<br /><br />
    To reposition all cars that are NOT at their home location to their home, click the REPOSITION TO HOME button.<br /><br />
    The list of cars can be filtered by selecting a car type, a current location and/or a home location.<br />
    To view only those cars not at their home locations, click the "SHOW ONLY CARS NOT AT THEIR HOME LOC" button.<br />
    To remove the four filters, click the "CLEAR FILTERS" button.<br /><br />
    If all empty cars are displayed, those that are not at their home locations are highlighted. These cars<br />
    should be sent to their home locations if they are not needed for revenue moves. They may also be blocking<br />
    an unloading location.<br /><br />
    <form method="post" action="reposition.php">
    <?php
      // this program displays all cars that have a status of "Empty" and are not billed anywhere
      // it also creates empty car waybills for any cars where the user selects a destination
      
      // bring in the function files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // open a database connection
      $dbc = open_db();

      // check to see if the Update button was clicked
      if (isset($_POST['update_btn']))
      {
        // get the current operating session number, default to zero if the query returns nothing
        $sql = 'select setting_value from settings where setting_name = "session_nbr"';
        $rs = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($rs) > 0)
        {
          $row = mysqli_fetch_row($rs);
          $session_number = $row[0];
        }
        else
        {
          $session_number = 0;
        }

        // get the last reposition waybill number generated, default to 1 if the query returns nothing
        $sql = 'select waybill_number from car_orders where waybill_number like "' . str_pad($session_number, 3, '0', STR_PAD_LEFT) .  '-E__" order by waybill_number desc limit 1';
        $rs = mysqli_query($dbc, $sql);
        if (mysqli_num_rows($rs) > 0)
        {
          $row = mysqli_fetch_row($rs);
          $waybill_counter = substr($row[0], -2, 2) + 1;
        }
        else
        {
          $waybill_counter = 1;
        }

        // go through each of the rows from the incoming page and if a destination was selected from any car's drop-down list,
        // insert an empty car waybill into the waybills table
        for ($i=0; $i<$_POST['row_count']; $i++)
        {
          // build the names of the input fields
          $list_name = 'list' . $i;
          $car_name = 'car' . $i;

          // construct the waybill number
          $wb_nbr = str_pad($session_number, 3, '0', STR_PAD_LEFT) . '-E' . str_pad($waybill_counter, 2, '0', STR_PAD_LEFT);

          if (strlen($_POST[$list_name]) > 0)
          {
            // build an sql query to create the empty car waybill
            $sql = 'insert into car_orders values ("' . $wb_nbr . '", "' . $_POST[$list_name] . '", "' . $_POST[$car_name] . '")';

            if (!mysqli_query($dbc, $sql))
            {
              print 'Insert Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
            }

            // build an sql query to update the car's status to "Ordered"
            $sql = 'update cars set status = "Ordered" where id = "' . $_POST[$car_name] . '"';

            if (!mysqli_query($dbc, $sql))
            {
              print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
            }

            // the background information needed by the history file
            $sql = 'select current_location_id from cars where id = "' . $_POST[$car_name] . '"';
            $rs = mysqli_query($dbc, $sql);
            $row = mysqli_fetch_array($rs);
            $car_id = $row["current_location_id"];

            $sql = 'select code from locations where id = "' . $_POST[$list_name] . '"';
            $rs = mysqli_query($dbc, $sql);
            $row = mysqli_fetch_array($rs);
            $destination = $row["code"];

            // insert a car history record
            $sql = 'insert into history(car_id, session_nbr, event_date, event, location)
                    values ("' . $_POST[$car_name] . '", 
                            "' . $session_number . '", 
                            "' . date("Y-m-d H:i:s") . '", 
                            "Repositioned to ' . $destination . '", 
                            "' . $car_id . '")';
//print 'SQL: ' . $sql . ' session: ' . $session_nbr . ' destination: ' . $_POST[$list_name] . ' location: ' . $row["current_location_id"] . '<br /><br />';
             
            if (!mysqli_query($dbc, $sql))
            {
              print 'Insert error: ' . mysqli_error($dbc) . ' SQL: ' . $sql . '<br /><br />';
            }

            $waybill_counter++;
          }
        }
      }

      // build the sql query to pull in cars that have a status of "Empty-Available" and aren't billed
      $sql = 'select cars.id as id, 
                     cars.reporting_marks as reporting_marks,
                     cars.position as position,
                     cars.remarks as remarks,
                     car_codes.code as car_code,
                     loc01.code as current_location,
                     loc02.code as home_location,
                     sta01.station as current_station,
                     sta02.station as home_station
                from cars
                left join car_codes on car_codes.id = cars.car_code_id
                left join locations loc01 on loc01.id = cars.current_location_id
                left join locations loc02 on loc02.id = cars.home_location
                left join routing sta01 on sta01.id = loc01.station
                left join routing sta02 on sta02.id = loc02.station
               where status = "Empty"
                 and not exists (select car_orders.car from car_orders where cars.id = car_orders.car)
               order by case when cars.current_location_id != cars.home_location then concat("0", sta01.sort_seq) else concat("1", sta01.sort_seq) end asc,
                     loc01.code asc, reporting_marks asc';
//print '<br />Query started ' . date("h:i:s") . '<br />';
      $rs = mysqli_query($dbc, $sql);
//print '<br />Query returned ' . date("h:i:s") . '<br />';
      // initialize a car counter
      $row_count = 0;

      // build the table of empty-available cars
      if (mysqli_num_rows($rs) > 0)
      {
        // generate the update button
        print '<input name="update_btn" value="UPDATE" type="submit" style="background-color: #80ff00; font-size: 24px;">&nbsp;&nbsp;';
        print '<button onclick="repo_to_home()" style="background-color: #00ffff; font-size: 24px;">REPOSITION TO HOME</button><br /><br />';
        
        // generate the filters and header rows
        print '<table class="sortable" id="car_tbl" name="car_tbl" style="white-space: nowrap;">
                 <thead>
                   <tr>
                     <th style="border-bottom:0px; border-right:0px;">
                       <button tabindex="4" type="submit" id="clear_filters_btn" name="clear_filters_btn" onclick="location.reload();"
                        style="font: bold 10px Verdana, Arial, sans-serif; text-align: center; background-color: #ffff80; font-size: 12px;">
                        CLEAR<br />FILTERS
                       </button>
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(2, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                                   document.getElementById(\'car_code_filter\').disabled=true;">' .
                                   drop_down_car_codes('car_code_filter', '', 'no_wild') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(3, document.getElementById(\'current_loc_filter\').options[document.getElementById(\'current_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'current_loc_filter\').disabled=true;">' .
                                   drop_down_locations('current_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;">
                     </th>
                     <th style="border-bottom:0px; border-left:0px; border-right:0px;"
                         onchange="filter_rows(5, document.getElementById(\'home_loc_filter\').options[document.getElementById(\'home_loc_filter\').selectedIndex].text);
                                   document.getElementById(\'home_loc_filter\').disabled=true;">' .
                                   drop_down_locations('home_loc_filter', '', '') . '
                     </th>
                     <th style="border-bottom:0px; border-left:0px;">
                     </th>
                   </tr>
                   <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                     <th class="sorttable_nosort">Destination</th>
                     <th><i>Reporting<br />Marks</i></th>
                     <th><i>Car Code</i></th>
                     <th><i>Current<br /><u>Station</u><br />Location</i></th>
                     <th><i>Position</i></th>
                     <th><i>Home<br /><u>Station</u><br />Location</i></th>
                     <th><i>Remarks</i></th>
                   </tr>
                 </thead>';
/*
// these lines came from the last <th> cell
                       <button name="show_all_btn" id="show_all_btn" onclick="show_all();" 
                        style="font: bold 12px Verdana, Arial, sans-serif; text-align: center; background-color: #ffff80;">
                         SHOW ONLY CARS<br />NOT AT HOME LOC
                       </button>
*/
        $location_list = drop_down_locations("listx", 0, '');
                
        while ($row = mysqli_fetch_array($rs))
        {

          if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['id'] . '.jpg'))
          {
            $parm_string = '\'' . $row['id'] . '\', \'' . $row['reporting_marks'] . '\'';
          }
          else
          {
            $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
          }

          if ($row['current_location'] != $row['home_location'])
          {
            print '<tr style="background-color:LightGray;">';
          }
          else
          {
            print '<tr>';
          }

          $loc_list_part1 = substr($location_list, 0, 16);
          $loc_list_part2 = substr($location_list, 17, 12);
          $loc_list_part3 = substr($location_list, 32);

          $new_location_list = $loc_list_part1 . $row_count . $loc_list_part2 . $row_count . '" ' . $loc_list_part3;
          print '<td>' . $new_location_list . '</td>
                 <td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '<input name="car' . $row_count . '" value="' . $row['id'] . '" type="hidden"></td>
                 <td style="text-align: center;">' . $row['car_code'] . '</td>
                 <td><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>
                 <td style="text-align: center;">' . $row['position'] . '</td>
                 <td><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>
                 <td>' . $row['remarks'] . '</td>
                 </tr>';
          $row_count++;

        }
        print '</table>';
        // put the row count into a hidden field for when this program calls itself
        print '<input name="row_count" value="' . $row_count . '" type="hidden">';
      }
      else
      {
        print "<br />No cars are currently available for repositioning.";
      }
    ?>
    </form>
  </body>
</html>
