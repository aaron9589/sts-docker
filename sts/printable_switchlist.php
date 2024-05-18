<html>
  <head>
    <title>STS - Print Switchlist</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse; table-layout: fixed;}
      tr {vertical-align: middle}
      th {border: 1px solid black; padding: 1px}
      td {border: 1px solid black; padding: 1px}
      @media print
      {
        .noprint {display:none;}
      }
    </style>
  </head>
  <body>
    <script>

      function toggle_half_sheet()
      {
        var checkbox = document.getElementById("half_sheet_checkbox");
        if (checkbox.checked == true)
        {
          document.getElementById("half_sheet_job_instructions").style.display = "table-cell";
        }
        else
        {
          document.getElementById("half_sheet_job_instructions").style.display = "none";
        }
      }
      
      function toggle_full_sheet()
      {
        var checkbox = document.getElementById("full_sheet_checkbox");
        if (checkbox.checked == true)
        {
          document.getElementById("full_sheet_job_instructions").style.display = "block";
        }
        else
        {
          document.getElementById("full_sheet_job_instructions").style.display = "none";
        }
      }
      function toggle_dot_matrix()
      {
        var checkbox = document.getElementById("dot_matrix_checkbox");
        if (checkbox.checked == true)
        {
          document.getElementById("dot_matrix_job_instructions").style.display = "block";
        }
        else
        {
          document.getElementById("dot_matrix_job_instructions").style.display = "none";
        }
      }

      function toggle_workorder()
      {
        var checkbox = document.getElementById("workorder_checkbox");
        if (checkbox.checked == true)
        {
          document.getElementById("workorder_job_instructions").style.display = "block";
        }
        else
        {
          document.getElementById("workorder_job_instructions").style.display = "none";
        }
      }
      
      function move_row(cell, move)
      {
        // incoming cell is the one containing the up or down arrow image
        var row_num = cell.parentElement.rowIndex;

        // get the collection of rows in the table
        var rows = document.getElementById("consist").rows;

        // make sure that we do not go above the top or below the bottom of the table
        if ((move == 1) && (row_num < rows.length - 1) || ((move == -1) && (row_num > 1)))
        {
          // swap the rows
          var old_row = rows[row_num].innerHTML;
          var new_row = rows[row_num + move].innerHTML;
          rows[row_num].innerHTML = new_row;
          rows[row_num + move].innerHTML = old_row;
        }
      }

      function find_car(target, starting_row)
      {
        // find the row containing the specified reporting marks
        // and insert a new line after that location
        consist_table = document.getElementById("consist");
        table_rows = consist_table.getElementsByTagName("tr");
        for (i=starting_row; i<table_rows.length; i++)
        {
          table_cells = table_rows[i].getElementsByTagName("td");
          reporting_marks = table_cells[0].innerText;
          if (reporting_marks == target)
          {
            insert_blank_row(i);
            return(0);
          }
        }
      }

      function insert_blank_row(where)
      {
        // insert a blank row at the location specified by "where"
        // create one cell on the left with the up and down arrows in it to it can be moved if necessary
        // the other seven columns will be left blank
        var table = document.getElementById("consist");
        var blank_row = table.insertRow(where);
        var blank_td_1 = blank_row.insertCell(0);
        blank_td_1.innerHTML = "<img src='./ImageStore/DB_Images/graphics/up_arrow.png' onclick='move_row(this.parentElement, -1);' alt='UP'/>" +
      " <br /><br />" + " <img src='./ImageStore/DB_Images/graphics/dn_arrow.png' onclick='move_row(this.parentElement, 1);' alt='DN'/>";
        blank_td_1.style = "text-align: center; vertical-align: middle;";
        var blank_td_2 = blank_row.insertCell(1);
        blank_td_2.colSpan = 7;
        blank_td_2.innerHTML = document.getElementById("comment").value;
      }

    </script>
 
    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';
      require 'set_colors.php';

      // has the display button be clicked?
      if (isset($_GET['display_btn']))
      {
        // get a database connection
        $dbc = open_db();

        // get the desired job name
        $job_name = $_GET['job_name'];

        // get the print width from the settings table
        $sql = 'select setting_value from settings where setting_name = "print_width"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $print_width = $row[0];
        $page_width = substr($print_width, 0, 3) * 10;

        // get the railroad initials and name from the settings table
        $sql = 'select setting_value from settings where setting_name = "railroad_initials"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $rr_initials = $row[0];
        
        $sql = 'select setting_value from settings where setting_name = "railroad_name"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_row($rs);
        $rr_name = $row[0];

        // build a query to pull in the job's description and table name
        $sql = 'select jobs.description as description,
                       jobs.name as table_name
                  from jobs
                 where id = "' . $job_name . '"';
        $rs = mysqli_query($dbc, $sql);
        $row = mysqli_fetch_array($rs);
        $job_desc = $row['description'];
        $table_name = $row['table_name'];

        // build a query to pull in the switchlist information
        // the first query in the union looks for cars that are assigned to the specified job and are revenue moves
        // the second query in the union looks for cars that are assigned to the specified job but are repositioning moves

/*--        $sql = '(select `' . $table_name . '`.step_number,
                       cars.current_location_id,
                       cars.position as position,
                       cars.reporting_marks as reporting_marks,
                       cars.car_code_id as car_code_id,
                       cars.status as status,
                       shipments.consignment as consignment_id,
                       shipments.loading_location as loading_location_id,
                       shipments.unloading_location as unloading_location_id,
                       shipments.special_instructions as special_instructions,
                       car_orders.shipment as shipment,
                       car_orders.waybill_number as waybill_number,
                       commodities.code as consignment,
                       sta01.station as current_station,
                       loc01.code as current_location,
                       sta02.station as loading_station,
                       loc02.code as loading_location,
                       sta03.station as unloading_station,
                       loc03.code as unloading_location,
                       car_codes.code as car_code
                from cars
                inner join car_orders on car_orders.car = cars.id
                inner join shipments on shipments.id = car_orders.shipment
                inner join locations on locations.id = cars.current_location_id
                inner join car_codes on car_codes.id = cars.car_code_id
                inner join `' . $table_name . '` on `' . $table_name . '`.station = locations.station
                inner join commodities on commodities.id = shipments.consignment
                inner join locations loc01 on loc01.id = cars.current_location_id
                inner join locations loc02 on loc02.id = shipments.loading_location
                inner join locations loc03 on loc03.id = shipments.unloading_location
                inner join routing sta01 on sta01.id = loc01.station
                inner join routing sta02 on sta02.id = loc02.station
                inner join routing sta03 on sta03.id = loc03.station
                where ((cars.handled_by_job_id = "' . $job_name . '" and `' . $table_name . '`.pickup = "T")
                   or  (cars.handled_by_job_id = "' . $job_name . '" and cars.current_location_id = "0"))
                  and (not instr(car_orders.waybill_number, "E")))
                UNION
                (select `' . $table_name . '`.step_number,
                       cars.current_location_id,
                       cars.position as position,
                       cars.reporting_marks as reporting_marks,
                       cars.car_code_id as car_code_id,
                       cars.status as status,
                       0 as consignment_id,
                       0 as loading_location_id,
                       car_orders.shipment as unloading_location_id,
                       "" as special_instructions,
                       0 as shipment,
                       car_orders.waybill_number as waybill_number,
                       0 as consignment,
                       sta01.station as current_station,
                       loc01.code as current_location,
                       "" as loading_station,
                       0 as loading_location,
                       sta03.station as unloading_station,
                       loc03.code as unloading_location,
                       car_codes.code as car_code
                from cars
                inner join car_orders on car_orders.car = cars.id
                inner join locations on locations.id = cars.current_location_id
                inner join car_codes on car_codes.id = cars.car_code_id
                inner join `' . $table_name . '` on `' . $table_name . '`.station = locations.station
                inner join locations loc01 on loc01.id = cars.current_location_id
                inner join locations loc03 on loc03.id = car_orders.shipment
                inner join routing sta01 on sta01.id = loc01.station
                inner join routing sta03 on sta03.id = loc03.station
                where ((cars.handled_by_job_id = "' . $job_name . '" and `' . $table_name . '`.pickup = "T")
                        or (cars.handled_by_job_id = "' . $job_name . '" and cars.current_location_id = "0"))
                       and (instr(car_orders.waybill_number, "E")))
                order by step_number,
                         current_location,
                         position,
                         unloading_location,
                         reporting_marks';
--*/

         $sql = '(select
                 cars.reporting_marks as reporting_marks, 
                 car_codes.code as car_code,
                 cars.status as status,
                 commodities.code as consignment,
                 shipments.consignment as consignment_id,
                 shipments.special_instructions as special_instructions,
                 routing.station as current_station,
                 locations.code as current_location,
                 loading_sta.station as loading_station,
                 loading_loc.code as loading_location,
                 unloading_sta.station as unloading_station,
                 unloading_loc.code as unloading_location,
                 
                 cars.current_location_id,
                 cars.position as position, 
                 cars.car_code_id as car_code_id, 
                 cars.handled_by_job_id as handled_by,
                 locations.station as current_station_id,
                 `' . $table_name . '`.step_number

                 from cars
                 
                 left join locations on locations.id = cars.current_location_id
                 left join routing on routing.id = locations.station
                 inner join car_orders on car_orders.car = cars.Id
                 inner join car_codes on car_codes.id = cars.car_code_id
                 inner join shipments on shipments.id = car_orders.shipment
                 inner join commodities on commodities.id = shipments.consignment
                 
                 inner join locations loading_loc on loading_loc.id = shipments.loading_location
                 inner join routing loading_sta on loading_sta.id = loading_loc.station
                 
                 inner join locations unloading_loc on unloading_loc.id = shipments.unloading_location
                 inner join routing unloading_sta on unloading_sta.id = unloading_loc.station

                 left join `' . $table_name . '` on `' . $table_name . '`.station = routing.id

                 where ((cars.handled_by_job_id = "' . $job_name . '") and (not instr(car_orders.waybill_number, "E")))
                 
                 group by cars.reporting_marks)
                 
                 UNION
                 
                 (select
                 cars.reporting_marks as reporting_marks, 
                 car_codes.code as car_code,
                 cars.status as status,
                 "" as consignment,
                 0 as consignment_id,
                 "" as special_instructions,
                 routing.station as current_station,
                 locations.code as current_location,
                 0 as loading_station,
                 "" as loading_location,
                 unloading_sta.station as unloading_station,
                 unloading_loc.code as unloading_location,
                 
                 cars.current_location_id,
                 cars.position as position, 
                 cars.car_code_id as car_code_id, 
                 cars.handled_by_job_id as handled_by,
                 locations.station as current_station_id,
                 `' . $table_name . '`.step_number

                 from cars
                 
                 left join locations on locations.id = cars.current_location_id
                 left join routing on routing.id = locations.station
                 inner join car_orders on car_orders.car = cars.Id
                 inner join car_codes on car_codes.id = cars.car_code_id
                 
                 inner join locations unloading_loc on unloading_loc.id = car_orders.shipment
                 inner join routing unloading_sta on unloading_sta.id = unloading_loc.station

                 left join `' . $table_name . '` on `' . $table_name . '`.station = routing.id

                 where ((cars.handled_by_job_id = "' . $job_name . '") and (instr(car_orders.waybill_number, "E")))
                 
                 group by cars.reporting_marks)
                 
                 ORDER BY position, step_number, current_station, current_location, unloading_location, reporting_marks';
//                 inner join shipments on shipments.id = car_orders.shipment // removed because repositions don't have shipments
//                 inner join commodities on commodities.id = shipments.consignment // ditto
//                 ORDER BY max_step_number, position, unloading_location, reporting_marks'; // fixed sort order

//print 'SQL: ' . $sql . '<br /><br />';

        // run the query before generating the page in order to collect a list of all the reporting marks
        $rs = mysqli_query($dbc, $sql);
        $i = 0;
        while ($row = mysqli_fetch_array($rs))
        {
          $car_list[$i] = $row['reporting_marks'];
          $i++;
        }

        // run the query again to build the switchlist table
        $rs = mysqli_query($dbc, $sql);
//print "num_rows = " . mysqli_num_rows($rs) . '<br />';
        if (mysqli_num_rows($rs) > 0)
        {
          // initialize the counters for loads and empties
          $loads = 0;
          $empties = 0;

          // determine the format
          if ($_GET['format'] == 'half')
          {
/*----------------------------------------------------------------------------------------------------------------*/
            // half-sheet format

            // remember if there are any special instructions to be displayed/printed
            $special_instructions = array();
            $special_instruction_counter = 0;

            print '<div class="noprint">';
            // generate a print button at the bottom of the page and a return link
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;';
            // generate a check box that when checked displays the job instructions
            print '<input type="checkbox" checked id="half_sheet_checkbox" onchange=toggle_half_sheet();>Show Job Instructions&nbsp;&nbsp;';
            // generate a link to go back to the previous page
            print '<a href="display_switchlist.php">Return to Display Switchlist page</a><br /<br /><br />';
            // generate a drop-down list, a text box, and a button to insert a blank line
            // after the specified reporting marks with an optional text comment
            print 'Insert blank line before this car:&nbsp';
            print '<select name="reporting_marks_list" id="reporting_marks_list">';
            for ($i=0; $i<sizeof($car_list); $i++)
            {
              print '<option value="' . $car_list[$i] . '">' . $car_list[$i]. '</option>';
            }
            print '</select>&nbsp;';
            print 'With comment:&nbsp; <input type="text" name="comment" id="comment">&nbsp;';
            print '<button onclick="find_car(document.getElementById(\'reporting_marks_list\').value, 1);">Insert</button>&nbsp;';
            print '<br /><br />';
            print '</div><br />';

            // set up two columns, left one for the switch list, right one for the job's description
            $units = substr($print_width, -2);
            $value = substr($print_width, 0, strlen($print_width) - 2);
            $col_width = (($value/2) - 0.125) . $units;

            print '<table>';
            print '<tr>';
            print '<td>';

            // generate the heading
            print '<h2 style="text-align: center;">' . $rr_initials . '</h2>';
            print '<h3 style="text-align: center;">Switchlist</h3>';

            // generate the train number and other header information
            print '<table style="font: normal 10px Verdana, Arial, sans-serif; width: ' . $col_width . ';">';
            print '<tr>';
            print '<td style="width: 50%;"><b>Train: ' . $table_name . '</b><br /><br /><br /></td>';
            print '<td style="width: 50%; vertical-align: top;"><b>Dpt (station/date/time)</b><br /><br /><br /></td>';
            print '</tr>';
            print '<tr>';
            print '<td style="width: 50%;"><b>Engine:</b><br /><br /><b>DCC Address:</b><br /><br /><b>Caboose:</b><br /></td>';
            print '<td style="width: 50%; vertical-align: top;"><b>Arr (station/date/time)</b><br /><br /><br /></td>';
            print '</tr>';
            print '<tr>';
            print '<td style="width: 50%;"><b>Engineer:</b><br /><br /><br /></td>';
            print '<td style="width: 50%;"><b>Conductor:</b><br /><br /><br /></td>';
            print '</tr>';
            print '</table>';

            // build a table for the selected job's switchlist
            print '<table id="consist" style="font: normal 8px Verdana, Arial, sans-serif; width: ' . $col_width . ';">';
            print '<tr>
                     <th style="width: 60px;">Rptg<br />Marks</th>
                     <th style="width: 22px; text-align: center;">Car<br />Code</th>
                     <th style="width: 15px; text-align: center;">E/L</th>
                     <th>Contents</th>
                     <th>From</th>
                     <th>To</th>
                     <th style="width: 30px">Picked<br />Up</th>
                     <th style="width: 35px">Left<br /> At</th>
                   </tr>';

            $row_num = 1;
            while ($row = mysqli_fetch_array($rs))
            {
              print '<tr>';
              
              // column 1 - reporting marks
              print '<td>' . $row['reporting_marks'] . '</td>';
              
              // column 2 - car code
              print '<td style="text-align: center">' . substr($row['car_code'], 0, 2) . '</td>';

              // column 3 - L/E
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                print '<td style="text-align: center">E</td>';
                $empties++;
              }
              elseif ($row['status'] == "Loaded")
              {
                print '<td style="text-align: center">L</td>';
                $loads++;
              }

              // column 4 - consignment
              if ($row['status'] == "Loaded")
              {
                print '<td>' . $row['consignment'];
                if (strlen($row['special_instructions']) > 0)
                {
                  print '<br />Spec Instr';
                  $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                  $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                  $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                  $special_instruction_counter++;
                }
                print '</td>';
              }
              else
              {
                print '<td>&nbsp;';
                if (strlen($row['special_instructions']) > 0)
                {
                  print 'Spec Instr';
                  print '<br />Spec Instr';
                  $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                  $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                  $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                  $special_instruction_counter++;
                }
                print '</td>';
              }

              // column 5 - current location
              if ($row['current_location_id'] > 0)
              {
                print '<td><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>';
              }
              else
              {
                print '<td>In Train</td>';
              }

              // column 6 - destination
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                // if the commodity column is empty, this is a non revenue move and the car's destination
                // is the unloading location
                if ($row['consignment_id'] <= 0)
                {
                  print '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location']  . '</td>';
                }
                else
                {
                  print '<td style="' . set_colors($dbc, $row['loading_location']) . '"><u>' . $row['loading_station'] . '</u><br />'. $row['loading_location']  . '</td>';
                }
              }
              elseif ($row['status'] == "Loaded")
              {
                print '<td style="' . set_colors($dbc, $row['unloading_location']) . '"><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location']  . '</td>';
              }

              // column 7 - picked up
              // If the car hasn't been picked up yet, leave this box blank, otherwise put an X in it
              if ($row['current_location_id'] > 0)
              {
                print '<td style="width: 30px;"></td>';
              }
              else
              {
                print '<td style="width: 30px; text-align: center"><b>X</b></td>';
              }

              // column 8 - left at
              print '<td style="width: 35px"></td>';

              print '</tr>';

              // insert a blank line between cars for easier readability
//              print '<tr><td colspan="8"></tr>';

              // increment the row number
              $row_num++;
            }
            print '</table>';

            // display the number of loads and empties
            $total_cars = $loads + $empties;
            print "<br />";
            print "Loads: " . $loads . "<br />";
            print "Empties: " . $empties . "<br />";
            print "Total cars: " . $total_cars;
            print '</td>';

            // build the right hand column
            print '<td id="half_sheet_job_instructions" style="padding: 10px">';

            // display the selected job's description
            print '<h3>Crew Instructions</h3>';
            print '<table style="width: ' . $col_width . '";>';
            print '  <tr>';
            print '    <td style="border: 0px;">
                         <h3>Job: ' . $table_name . '</h3>
                             Description: ' . nl2br($job_desc) . '
                       </td>
                     </tr>
                   </table>';
            print '</td>';
            print '</tr>';
            print '</table>';

            // if there are any special instructions, print them on their own page
            if ($special_instruction_counter > 0)
            {
              // generate a page break
              print '<p style="page-break-after: always;">&nbsp;</p>';
              print '<table  style="table-collapse: collapse; font: normal 15px Verdana, Arial, sans-serif; width: ' . $print_width . '">';
              print '<tr>
                       <td style="border: 0px;">
                         <h3>Special Instructions</h3>';
              for ($i=0; $i<$special_instruction_counter; $i++)
              {
                print $special_instructions[$i][0] . ' (' . $special_instructions[$i][1] . ') ' . $special_instructions[$i][2] . '<br /><br />';
              }
              print '    </td>
                       </tr>
                     </table>';
            }

            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
            print '<div class="noprint">';
            print '<hr />';
            print '</div>';
          }
          else if ($_GET['format'] == 'full')
          {
/*----------------------------------------------------------------------------------------------------------------*/
            // full page format
            
            // remember if there are any special instructions to be displayed/printed
            $special_instructions = array();
            $special_instruction_counter = 0;

            print '<div class="noprint">';
            // generate a print button at the bottom of the page and a return link
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;';
            // generate a check box that when checked displays the job instructions
            print '<input type="checkbox" checked id="full_sheet_checkbox" onchange=toggle_full_sheet();>Show Job Instructions&nbsp;&nbsp;';
            // generate a button that will insert a blank row into the list
            // display a link to return to the previous page
            print '<a href="display_switchlist.php">Return to Display Switchlist page</a><br /><br />';
            print 'Insert blank line before this car:&nbsp';
            print '<select name="reporting_marks_list" id="reporting_marks_list">';
            for ($i=0; $i<sizeof($car_list); $i++)
            {
              print '<option value="' . $car_list[$i] . '">' . $car_list[$i]. '</option>';
            }
            print '</select>&nbsp;';
            print 'With comment:&nbsp; <input type="text" name="comment" id="comment">&nbsp;';
            print '<button onclick="find_car(document.getElementById(\'reporting_marks_list\').value, 5);">Insert</button>&nbsp;';
            print '<br /><br />';
            print '</div><br />';

            print '<table id="consist">';
            print '  <tr>';
            print '    <td colspan="8">';

            // generate the heading
            print '      <h2 style="text-align: center;">' . $rr_initials . '</h2>';
            print '      <h3 style="text-align: center;">Switchlist</h3>';
            print '    </td>';
            print '  </tr>';

            // generate the train number and other header information
            print '  <tr>';
            print '    <td colspan="4"><b>Train: ' . $table_name . '</b><br /><br /><br /></td>';
            print '    <td colspan="4"><b>Dpt (station/date/time)</b><br /><br /><br /></td>';
            print '  </tr>';
            print '  <tr>';
            print '    <td colspan="4"><b>Engine:</b><br /><br /><b>DCC Address:</b><br /><br /><b>Caboose:</b><br ></td>';
            print '    <td colspan="4" style="vertical-align: top;"><b>Arr (station/date/time)</b><br /><br /><br /></td>';
            print '  </tr>';
            print '  <tr>';
            print '    <td colspan="4"><b>Engineer:</b><br /><br /><br /></td>';
            print '    <td colspan="4"><b>Conductor:</b><br /><br /><br /></td>';
            print '  </tr>';

            // build the selected job's switchlist
            print '  <tr>
                       <th>Rptg<br />Marks</th>
                       <th style="text-align: center;">Car<br />Code</th>
                       <th style="text-align: center;">E/L</th>
                       <th>Contents</th>
                       <th>From</th>
                       <th>To</th>
                       <th>Picked<br />Up</th>
                       <th style="width: 100px;">Left<br /> At</th>
                     </tr>';

            $row_num = 1;
            while ($row = mysqli_fetch_array($rs))
            {
              print '  <tr>';
              
              // column 1 - reporting marks
              print '    <td>' . $row['reporting_marks'] . '</td>';
              
              // column 2 - car code
              print '    <td style="text-align: center">' . $row['car_code'] . '</td>';

              // column 3 - L/E
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                print '    <td style="text-align: center">E</td>';
                $empties++;
              }
              elseif ($row['status'] == "Loaded")
              {
                print '    <td style="text-align: center">L</td>';
                $loads++;
              }

              // column 4 - consignment
              if ($row['status'] == "Loaded")
              {
                print '    <td>' . $row['consignment'];
                if (strlen($row['special_instructions']) > 0)
                {
                  print '<br />Spec Instr';
                  $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                  $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                  $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                  $special_instruction_counter++;
                }
                print '</td>';
              }
              else
              {
                print '    <td>&nbsp;';
                if (strlen($row['special_instructions']) > 0)
                {
                  print 'Spec Instr';
                  $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                  $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                  $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                  $special_instruction_counter++;
                }
                print '</td>';
              }

              // column 5 - current location
              if ($row['current_location_id'] > 0)
              {
                print '    <td><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>';
              }
              else
              {
                print '    <td>In Train</td>';
              }

              // column 6 - destination
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                // if the commodity column is empty, this is a non revenue move and the car's destination
                // is the unloading location
                if ($row['consignment_id'] <= 0)
                {
                  print '    <td style="' . set_colors($dbc, $row['unloading_location']) . '"><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location']  . '</td>';
                }
                else
                {
                  print '    <td style="' . set_colors($dbc, $row['loading_location']) . '"><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location']  . '</td>';
                }
              }
              elseif ($row['status'] == "Loaded")
              {
                print '    <td style="' . set_colors($dbc, $row['unloading_location']) . '"><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location']  . '</td>';
              }

              // column 7 - picked up
              // If the car hasn't been picked up yet, leave this box blank, otherwise put an X in it
              if ($row['current_location_id'] > 0)
              {
                print '    <td>&nbsp;</td>';
              }
              else
              {
                print '    <td style="text-align: center"><b>X</b></td>';
              }

              // column 8 - left at
              print '    <td>&nbsp;</td>';

              print '  </tr>';

              // print a blank line between cars for improved readability
//              print '<tr><td colspan="8"></td></tr>';

              // increment the row number
              $row_num++;
            }
            print '</table>';

            // display the number of loads and empties
            $total_cars = $loads + $empties;
            print "<br />";
            print "Loads: " . $loads . "<br />";
            print "Empties: " . $empties . "<br />";
            print "Total cars: " . $total_cars;

            // if there are any special instructions, print them on their own page
            if ($special_instruction_counter > 0)
            {
              // generate a page break
              print '<p style="page-break-after: always;">&nbsp;</p>';
              print '<table  style="table-collapse: collapse; font: normal 15px Verdana, Arial, sans-serif; width: ' . $print_width . '">';
              print '<tr>
                       <td style="border: 0px;">
                         <h3>Special Instructions</h3>';
              for ($i=0; $i<$special_instruction_counter; $i++)
              {
                print $special_instructions[$i][0] . ' (' . $special_instructions[$i][1] . ') ' . $special_instructions[$i][2] . '<br /><br />';
              }
              print '    </td>
                       </tr>
                     </table>';
            }

            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';

            // display the selected job's description
            print '<div  id="full_sheet_job_instructions">';
            print '<h3>Crew Instructions</h3>';
            print '<table>';
            print '<tr>';
            print '<td style="border: 0px;">
                   <h3>Job: ' . $table_name . '</h3>
                   Description: ' . nl2br($job_desc) . '
                   </td>';
            print '</tr>';
            print '</td>';
            print '</tr>';
            print '</table>';
            print '</div>';
            
            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
            print '<div class="noprint">';
            print '<hr />';
            print '</div>';
          }
          else if (($_GET['format'] == 'dmp') or ($_GET['format'] == 'mobile'))
          {
/*----------------------------------------------------------------------------------------------------------------*/
            // dot matrix and mobile formats
            
            // remember if there are any special instructions to be displayed/printed
            $special_instructions = array();
            $special_instruction_counter = 0;

            // mark this output as pre-formated
            print '<pre>';

            print '<div class="noprint">';
            // generate a print button at the bottom of the page and a return link
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;';
            // generate a check box that when checked displays the job instructions
            print '<input type="checkbox" checked id="dot_matrix_checkbox" onchange=toggle_dot_matrix();>Show Job Instructions&nbsp;&nbsp;';
            // generate a button that will insert a blank row into the list
            print '<button onclick="insert_blank_row(5);">Add Blank Row</button><br /><br />';
            // display a link to go to the previous page
            print '<a href="display_switchlist.php">Return to Display Switchlist page</a>';
            print '</div><br />';
            
            // generate the headings based on format (mobile = less heading information)
            print str_pad($rr_name, $page_width, ' ', STR_PAD_BOTH). '<br/>';
            if ($_GET['format'] != 'mobile')
            {
              print '<br />';
            }
            
            print str_pad('Switchlist', $page_width, ' ', STR_PAD_BOTH) . '<br />';
            if ($_GET['format'] != 'mobile')
            {
              print '<br />';
            }

            // generate the train number and other header information
            if ($_GET['format'] == 'dmp')
            {
              print str_pad('Train: ' . substr($table_name, 0, 22), $page_width / 3) .
                    str_pad(' Dpt ___________________', $page_width / 3) . 'Arr ___________________<br />';
              print str_repeat(' ', $page_width / 3) .
                    str_pad('     (station/date/time)', $page_width / 3) . '    (station/date/time)<br /> <br />';
              print str_pad('Engine: ______________', $page_width / 3) .
                    str_pad('Engineer: _____________', $page_width / 3) . 'Conductor: ____________<br /> <br />';
              print str_pad('DCC Address: _________', $page_width / 3) .
                    str_repeat(' ', $page_width / 3) . 'Caboose:   ____________<br /><br />';
            }
            else if ($_GET['format'] == 'mobile')
            {
              print str_pad('Train: ' . $table_name, $page_width, ' ', STR_PAD_BOTH) . '<br />';
            }
            print '<br />';

            // build the selected job's switchlist
            print 'Rptg Marks  Type E/L Contents      From           To             PkUp Left<br />';
            print '----------- ---- --- ------------- -------------- -------------- ---- ----</br />';

            $row_num = 1;
            while ($row = mysqli_fetch_array($rs))
            {
              // generate the first line for each car -----------------------------------------------------------------------------------------------------
              
              // columns 1 and 2 - reporting marks and car code
              print str_pad(substr($row['reporting_marks'], 0, 11), 11) . ' ' . str_pad(substr($row['car_code'], 0, 4), 4) . ' ';

              // column 3 - L/E
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                print ' E  ';
                $empties++;
              }
              elseif ($row['status'] == "Loaded")
              {
                print ' L  ';
                $loads++;
              }

              // column 4 - consignment
              if ($row['status'] == "Loaded")
              {
                print str_pad(substr($row['consignment'], 0, 13), 13) . ' ';
              }
              else
              {
                print str_repeat(' ', 13) . ' ';
              }

              // column 5 - current location
              if ($row['current_location_id'] > 0)
              {
                print str_pad(substr($row['current_station'], 0, 14), 14) . ' ';
              }
              else
              {
                print str_pad('In Train', 14) . ' ';
              }

              // column 6 - destination
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                // if the commodity column is empty, this is a non revenue move and the car's destination
                // is the unloading location
                if ($row['consignment_id'] <= 0)
                {
                  if ($_GET['format'] != 'mobile')
                  {
                    print str_pad(substr($row['unloading_station'], 0, 14), 14) . ' ';
                  }
                  else
                  {
                    print '<span style="' . set_colors($dbc, $row['unloading_location']) . '">' . str_pad(substr($row['unloading_station'], 0, 14), 14) . '</span> ';
                  }
                }
                else
                {
                  if ($_GET['format'] != 'mobile')
                  {
                    print str_pad(substr($row['loading_station'], 0, 14), 14) . ' ';
                  }
                  else
                  {
                    print '<span style="' . set_colors($dbc, $row['loading_location']) . '">' . str_pad(substr($row['loading_station'], 0, 14), 14) . '</span> ';
                  }
                }
              }
              elseif ($row['status'] == "Loaded")
              {
                if ($_GET['format'] != 'mobile')
                {
                  print str_pad(substr($row['unloading_station'], 0, 14), 14) . ' ';
                }
                else
                {
                  print '<span style="' . set_colors($dbc, $row['unloading_location']) . '">' . str_pad(substr($row['unloading_station'], 0, 14), 14) . '</span> ';
                }
              }
              print '<br />';
              
              // generate the second line for each car ---------------------------------------------------------------------------------------------------
              
              // skip past the first three fields
              print str_repeat(' ', 20) . ' ';
              
              // column 4 - display a special instructions reminder if one exists
              if (strlen($row['special_instructions']) > 0)
              {
                print 'See Spec Inst ';
                $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                $special_instruction_counter++;
              }
              else
              {
                print str_repeat(' ', 13) . ' ';
              }
              
              // column 5 - current location
//              if ($row['current_location_id'] > 0)
//              {
                print str_pad(substr($row['current_location'], 0, 14), 14) . ' ';
//              }
//              else
//              {
//                print str_pad('In Train', 15) . ' ';
//              }

              // column 6 - destination
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                // if the commodity column is empty, this is a non revenue move and the car's destination
                // is the unloading location
                if ($row['consignment_id'] <= 0)
                {
                  print str_pad(substr($row['unloading_location'], 0, 14), 14) . ' ';
                }
                else
                {
                  print str_pad(substr($row['loading_location'], 0, 14), 14) . ' ';
                }
              }
              elseif ($row['status'] == "Loaded")
              {
                print str_pad(substr($row['unloading_location'], 0, 14), 14) . ' ';
              }
              
              // column 7 - picked up
              // If the car hasn't been picked up yet, leave this box blank, otherwise put an X in it
              if ($row['current_location_id'] > 0)
              {
                print '____ ';
              }
              else
              {
                print '_XX_ ';
              }

              // column 8 - left at
              print '____ <br />';

              // print a blank line between cars for improved readability
              print '<br />';

              // increment the row number
              $row_num = $row_num + 3;
            }
            
            // display the number of loads and empties
            $total_cars = $loads + $empties;
            print 'Loads: ' . $loads . '<br />';
            print 'Empties: ' . $empties . '<br />';
            print 'Total cars: ' . $total_cars . '<br />';
  
            // generate a page break
            if ($special_instruction_counter > 0)
            {
              print '<p style="page-break-after: always;">&nbsp;</p>';
              print str_pad(' Special Instructions ', $page_width-1, '-', STR_PAD_BOTH) . '<br /><br />';
              for ($i=0; $i<$special_instruction_counter; $i++)
              {
                $special_instruction_string = $special_instructions[$i][0] . ' (' . $special_instructions[$i][1] . ') ' . $special_instructions[$i][2];
                print_chunks($special_instruction_string, $page_width);
              }
            }
            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
    
            // display the selected job's description
            print '<div  id="dot_matrix_job_instructions">';
            print str_pad(' Crew Instructions ', $page_width-1, '-', STR_PAD_BOTH) . '<br />';
            print 'Job: ' . $table_name . '<br /><br />';
            print_chunks($job_desc, $page_width);
            print '</div>';

            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
            print '<div class="noprint">';            
            print '<hr />';
            print '</div>';
            print '</pre>';
          }
          else if ($_GET['format'] == 'wo')
          {
/*----------------------------------------------------------------------------------------------------------------*/
            // work order format
            
            // remember if there are any special instructions to be displayed/printed
            $special_instructions = array();
            $special_instruction_counter = 0;

            // mark this output as pre-formated
            print '<pre>';

            print '<div class="noprint">';
            // generate a print button at the bottom of the page and a return link
            print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;';
            // generate a check box that when checked displays the job instructions
            print '<input type="checkbox" checked id="workorder_checkbox" onchange=toggle_workorder();>Show Job Instructions&nbsp;&nbsp;';
            // display a link to go to the previous page
            print '<a href="display_switchlist.php">Return to Display Switchlist page</a>';
            print '</div><br />';
            
            // generate the headings based on format (mobile = less heading information)
            print str_pad($rr_name, $page_width, ' ', STR_PAD_BOTH). '<br/><br />';
            
            print str_pad('Work Order', $page_width, ' ', STR_PAD_BOTH) . '<br /><br />';

            // generate the train number and other header information
            print str_pad('Train: ' . substr($table_name, 0, 22), $page_width / 3) .
                  str_pad('Dpt ___________________', $page_width / 3) . 'Arr ___________________<br />';
            print str_repeat(' ', $page_width / 3) .
                  str_pad('    (station/date/time)', $page_width / 3) . '    (station/date/time)<br /> <br />';
            print str_pad('Engine: ______________', $page_width / 3) .
                  str_pad('Engineer: _____________', $page_width / 3) . 'Conductor: ____________<br /> <br />';
            print str_pad('DCC Address: _________', $page_width / 3) .
                  str_repeat(' ', $page_width / 3) . 'Caboose:   ____________<br /><br />';

            // display the selected job's description
            print '<div id="workorder_job_instructions">';
            print str_pad(' CREW INSTRUCTIONS ', $page_width, '-', STR_PAD_BOTH) . '<br /><br />';
            print wordwrap(nl2br($job_desc), $page_width, "<br />") . '<br /><br />';
            print '</div>';

//            print str_repeat('-', $page_width) . '<br /><br />';

            // build the selected job's switchlist
            $row_num = 1;
            $prev_station = '';
            $first_station = true;
            while ($row = mysqli_fetch_array($rs))
            {
              // print a station header if it's changed from the previous car, or if it's the first one
              if (($first_station) || ($prev_station != $row['current_station']))
              {
                if ($row['current_location_id'] > 0)
                {
                  $current_station = 'STATION/YARD PICK UP AT ' . $row['current_station'];
                }
                else
                {
                  $current_station = 'IN TRAIN';
                }
                print str_repeat('-', (($page_width - strlen($current_station)) / 2)) . 
                      ' ' . $current_station . ' ' . 
                      str_repeat('-', (($page_width - strlen($current_station)) /2)) . '<br /><br />';

                print 'Loc/Trk/Spot   Rptg Marks  E/L Type Contents      To Station     Loc/Trk/Spot<br />';
                print '-------------- ----------- --- ---- ------------- -------------- --------------</br />';

                $prev_station = $row['current_station'];
                $first_station = false;
              }
              
              // generate the first line for each car -----------------------------------------------------------------------------------------------------
              
              // column 1 - current location
              if ($row['current_location_id'] > 0)
              {
                print str_pad(substr($row['current_location'], 0, 14), 14) . ' ';
                
                // get the track and spot for this location
                $sql1 = 'select track, spot from locations where code = "' . $row['current_location'] . '"';
                $rs1 = mysqli_query($dbc, $sql1);
                $row1 = mysqli_fetch_array($rs1);
                $current_track = $row1['track'];
                $current_spot = $row1['spot'];
              }
              else
              {
                print str_pad('In Train', 14) . ' ';
                $current_track = '';
                $current_spot = '';
              }
              
              // columns 2 reporting marks
              print str_pad(substr($row['reporting_marks'], 0, 11), 11) . ' ';

              // column 3 - L/E
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                print ' E  ';
                $empties++;
              }
              elseif ($row['status'] == "Loaded")
              {
                print ' L  ';
                $loads++;
              }

              // column 4 car code
              print str_pad(substr($row['car_code'], 0, 4), 4) . ' ';

              // column 5 - consignment
              if ($row['status'] == "Loaded")
              {
                print str_pad(substr($row['consignment'], 0, 13), 13) . ' ';
              }
              else
              {
                print str_repeat(' ', 13) . ' ';
              }

              // columns 6 and 7 - destination station, location, track, and spot
              if (($row['status'] == "Empty") || ($row['status'] == "Ordered"))
              {
                // if the commodity column is empty, this is a non revenue move and the car's destination
                // is the unloading location
                if ($row['consignment_id'] <= 0)
                {
                  print '<span style="' . set_colors($dbc, $row['unloading_location']) . '">' . str_pad(substr($row['unloading_station'], 0, 14), 14) . '</span> ';
                  print str_pad(substr($row['unloading_location'], 0, 14), 14);
                  $sql3 = 'select track, spot from locations where code = "' . $row['unloading_location'] . '"';
                  $rs3 = mysqli_query($dbc, $sql3);
                  $row3 = mysqli_fetch_array($rs3);
                  $destination_track = $row3['track'];
                  $destination_spot = $row3['spot'];
                  $style_color = set_colors($dbc, $row['unloading_location']);
                }
                else
                {
                  print '<span style="' . set_colors($dbc, $row['loading_location']) . '">' . str_pad(substr($row['loading_station'], 0, 14), 14) . '</span> ';
                  print str_pad(substr($row['loading_location'], 0, 14), 14);
                  $sql2 = 'select track, spot from locations where code = "' . $row['loading_location'] . '"';
                  $rs2 = mysqli_query($dbc, $sql2);
                  $row2 = mysqli_fetch_array($rs2);
                  $destination_track = $row2['track'];
                  $destination_spot = $row2['spot'];
                  $style_color = set_colors($dbc, $row['loading_location']);
                }
              }
              elseif ($row['status'] == "Loaded")
              {
                print '<span style="' . set_colors($dbc, $row['unloading_location']) . '">' . str_pad(substr($row['unloading_station'], 0, 14), 14) . '</span> ';
                print str_pad(substr($row['unloading_location'], 0, 14), 14);
                $sql3 = 'select track, spot from locations where code = "' . $row['unloading_location'] . '"';
                $rs3 = mysqli_query($dbc, $sql3);
                $row3 = mysqli_fetch_array($rs3);
                $destination_track = $row3['track'];
                $destination_spot = $row3['spot'];
                $style_color = set_colors($dbc, $row['unloading_location']);
              }
              
              print '<br />';
              
              // generate the second line for each car ---------------------------------------------------------------------------------------------------
              
              // column 1 - display the current track
              if (strlen(trim($current_spot)) > 0)
              {
                print str_pad(substr($current_track, 0, 9) . '/' . substr($current_spot, 0, 4), 14) . ' ';
              }
              else
              {
                print str_pad(substr($current_track, 0, 9), 14) . ' ';
              }
              
              // skip past the next three fields
              print str_repeat(' ', 20) . ' ';
              
              // column 5 - display a special instructions reminder if one exists
              if (strlen($row['special_instructions']) > 0)
              {
                print 'See Spec Inst ';
                $special_instructions[$special_instruction_counter][0] = $row['reporting_marks'];
                $special_instructions[$special_instruction_counter][1] = $row['consignment'];
                $special_instructions[$special_instruction_counter][2] = $row['special_instructions'];
                $special_instruction_counter++;
              }
              else
              {
                print str_repeat(' ', 13) . ' ';
              }
              
              // skip column 6
              print str_repeat(' ', 14) . ' ';
              
              // column 7 - destination location, track and spot
              if (strlen(trim($destination_spot)) > 0)
              {
                print str_pad(substr($destination_track, 0, 9) . '/' . substr($destination_spot, 0, 4), 14);
              }
              else
              {
                print str_pad(substr($destination_track, 0, 14), 14);
              }
              
              // print two blank lines between cars for improved readability
              print '<br /><br />';

              // increment the row number
              $row_num = $row_num + 3;
            }
            
            // display the number of loads and empties
            $total_cars = $loads + $empties;
            print 'Loads: ' . $loads . '<br />';
            print 'Empties: ' . $empties . '<br />';
            print 'Total cars: ' . $total_cars . '<br />';
  
            // generate a page break
            if ($special_instruction_counter > 0)
            {
              print '<p style="page-break-after: always;">&nbsp;</p>';
              print str_pad(' Special Instructions ', $page_width, '-', STR_PAD_BOTH) . '<br /><br />';
              for ($i=0; $i<$special_instruction_counter; $i++)
              {
                $special_instruction_string = $special_instructions[$i][0] . ' (' . $special_instructions[$i][1] . ') ' . $special_instructions[$i][2];
                print_chunks($special_instruction_string, $page_width);
              }
            }

           // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
            print '<div class="noprint">';            
            print '<hr />';
            print '</div>';
            print '</pre>';
          }
/*-----------------------------------------------------------------------------------------------------------------*/
          // finish up the table
          print '</table>';
        }
        else
        {
          print '<p style="font-family: verdana;">';
          print 'No switchlist found for ' . $table_name . '<br />';
          print '</p>';
        }
      }

    function print_chunks($incoming_string, $page_width)
    {
      // because text inside <pre></pre> tags doesn't respect boundaries, reformat any strings that
      // are longer than the page width setting_name by turning the text string into an array and
      // checking each of the individual lines
      $string_array = explode('<br />', nl2br($incoming_string));
      for ($i=0; $i<sizeof($string_array); $i++)
      {
        if (strlen($string_array[$i]) <= $page_width)
        {
          // if the string is less than page width, just print it
          print $string_array[$i] . '<br />';
        }
        else
        {
          // turn this string into an array of words and print them one by one until the next word
          // in line will exceed the page width
          $line_string_array = explode(' ', $string_array[$i]);
          $col_counter = 0;
          for ($j=0; $j<sizeof($line_string_array); $j++)
          {
            $end_of_word = $col_counter + strlen($line_string_array[$j]);
            if ($end_of_word > $page_width)
            {
              $end_of_word = 0;
              print '<br />';
            }
            print $line_string_array[$j] . ' ';
            $col_counter = $end_of_word + strlen($line_string_array[$j]);
          }
        }
      }
    }
            
 
    ?>
<div class="noprint">
    <br /><a href="display_switchlist.php">Return to Display Switchlist page</a>
    <br />
</div>
  </body>
</html>
