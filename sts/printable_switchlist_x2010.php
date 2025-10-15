<html>

<head>
  <title>STS - Print Switchlist</title>
  <style>
    body {
      font: normal 20px Verdana, Arial, sans-serif;
    }

    table {
      border-collapse: collapse;
      table-layout: fixed;
    }

    tr {
      vertical-align: middle
    }

    th {
      border: 1px solid black;
      padding: 1px
    }

    td {
      border: 1px solid black;
      padding: 1px
    }

    @media print {
      .noprint {
        display: none;
      }
    }
  </style>
</head>

<body>
  <script>

    function move_row(cell, move) {
      // incoming cell is the one containing the up or down arrow image
      var row_num = cell.parentElement.rowIndex;

      // get the collection of rows in the table
      var rows = document.getElementById("consist").rows;

      // make sure that we do not go above the top or below the bottom of the table
      if ((move == 1) && (row_num < rows.length - 1) || ((move == -1) && (row_num > 1))) {
        // swap the rows
        var old_row = rows[row_num].innerHTML;
        var new_row = rows[row_num + move].innerHTML;
        rows[row_num].innerHTML = new_row;
        rows[row_num + move].innerHTML = old_row;
      }
    }

    function find_car(target, starting_row) {
      // find the row containing the specified reporting marks
      // and insert a new line after that location
      consist_table = document.getElementById("consist");
      table_rows = consist_table.getElementsByTagName("tr");
      for (i = starting_row; i < table_rows.length; i++) {
        table_cells = table_rows[i].getElementsByTagName("td");
        reporting_marks = table_cells[0].innerText;
        if (reporting_marks == target) {
          insert_blank_row(i);
          return (0);
        }
      }
    }

    function insert_blank_row(where) {
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
  if (isset($_GET['display_btn'])) {
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
  
    $sql = '(select
                 cars.reporting_marks as reporting_marks,
                 car_codes.code as car_code,
                 cars.status as status,
                 cars.remarks as remarks,
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
                 cars.remarks as remarks,
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
    while ($row = mysqli_fetch_array($rs)) {
      $car_list[$i] = $row['reporting_marks'];
      $i++;
    }

    // set a variable for the number of pages
    $car_count = count($car_list);

    if ($car_count > 25) {
      $page_count = 3;
    } elseif ($car_count > 13) {
      $page_count = 2;
    } else {
      $page_count = 1;
    }

    // run the query again to build the switchlist table
    $rs = mysqli_query($dbc, $sql);
    //print "num_rows = " . mysqli_num_rows($rs) . '<br />';
    if (mysqli_num_rows($rs) > 0) {
      // initialize the counters for loads and empties
      $loads = 0;
      $empties = 0;

      // x2010 format based on Pacific National Train Consist Form
      if ($_GET['format'] == 'x2010') {
        print '<div class="noprint">';
        // Print button and return link
        print '<button onclick="window.print()">PRINT</button>&nbsp;&nbsp;';
        print '<a href="display_switchlist.php">Return to Display Switchlist page</a><br /><br />';
        print '</div>';

        // Generate random serial number
        $serial_number = sprintf("%06d", rand(1, 999999));

        // Main form container
        print '<div style="font-family: \'Arial Narrow\', \'Franklin Gothic\', Arial, sans-serif; width: 100%; max-width: 1200px;">';

        $operator_number = $table_name[2] ?? "No third digit found.";
        $logo = match ($operator_number) {
          '0' => 'railcorp',
          '2' => 'pn',
          '8' => 'arg',
          '5' => 'sct',
          '4' => 'ssr',
          'n' => 'manildra',
          default => 'nswgr'
        };

        // Define the widths for the aligned columns
        $driverNameWidth = "15%";
        $timeOnDutyWidth = "12%";
        $depotWidth = "12%";

        // Header with logo and form title - modified to include logo
        print '<table style="width: 100%; border-collapse: collapse;">';
        print '<tr>';
        print '<td style="width: 30%;"><img src="images/' . $logo . '_logo.jpg" alt="Company Logo" style="height: 100px; width: auto;"></td>';
        print '<td style="width: 45%; text-align: center;"><h2 style="height: 3px;">Train Consist Form x 2010</h2></td>';
        print '<td style="width: 25%; text-align: right; position: relative;">
                      <div style="color: red; font-size: 24px; margin-top: 60px; text-align: right;">' . $serial_number . '</div>
                      <div style="position: absolute; bottom: 0; left: 0;">PAGE 1 OF ' . $page_count . '</div>
                    </td>';
        print '</tr>';
        print '</table>';

        // Top section with train details
        print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black;">';
        print '<tr>';
        print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Train No.<br/><b>' . trim(explode("|", $table_name)[0]) . '</b></td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Date</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Dept Time</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 15%;">Origin</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 16%;">Destination</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: ' . $driverNameWidth . ';">Driver Name</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: ' . $timeOnDutyWidth . ';">Time on Duty</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: ' . $depotWidth . ';">Depot</td>';
        print '</tr>';
        print '</table>';

        // Second row with radio and unit details - matching column widths
        print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px;">';
        print '<tr>';
        print '<td style="border: 1px solid black; padding: 5px; width: 32.5%;">Train Radio Number</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 15.25%;">Unit No.</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 16.25%;">P.M. Date Due</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 15.2%;">Driver Name</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 12.3%;">Time on Duty</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 12.1%;">Depot</td>';
        print '</tr>';
        print '</table>';

        // Rest of the form remains the same
        print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px;">';
        print '<tr>';
        print '<td style="border: 1px solid black; padding: 5px; width: 50%;">Mobile Number</td>';
        print '<td style="border: 1px solid black; padding: 5px; width: 30%;">Brake Certificate No.</td>';
        print '<td style="border: 1px solid black; padding: 5px;">Train Type</td>';
        print '</tr>';
        print '</table>';

        // Main consist table
        print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px; font-size: 12px; table-layout: auto;">';
        print '<tr style="background-color: #f5f5f5;">';
        print '<th style="border: 1px solid black; padding: 5px;">Sl.<br/>No</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Wagon Class</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Wagon or Locomotive<br/>Number</th>';
        print '<th style="border: 1px solid black; padding: 5px;">CL</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Sta</th>';
        print '<th style="border: 1px solid black; padding: 5px;">DG</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Gross<br/>Mass</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Length<br/>Metres</th>';
        //current location
        print '<th style="border: 1px solid black; padding: 5px;">Current Location</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Destination</th>';
        print '<th style="border: 1px solid black; padding: 5px;">Contents</th>';
        print '</tr>';

        //initialise an array
        $special_instruction_counter = 0;

        // Generate rows for consist entries
        $row_num = 1;
        //$row = mysqli_fetch_array($rs);
        //print json_encode($row, JSON_PRETTY_PRINT);
        while ($row = mysqli_fetch_array($rs)) {
          print '<tr>';
          print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . $row_num . '</td>';
          print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . substr($row['car_code'], 0, 4) . '</td>';

          // reporting marks - strip any text
          if (ctype_alpha($row['reporting_marks'][strlen($row['reporting_marks']) - 1])) {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . preg_replace("/[a-zA-Z\-]+$/", "", $row['reporting_marks']) . '</td>';
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . $row['reporting_marks'] . '</td>';
          }


          // CL (Check Letter) column logic
          // Display the check letter if it exists - otherwise done
  
          if (ctype_alpha($row['reporting_marks'][strlen($row['reporting_marks']) - 1])) {
            // Display the last character
            //echo "The last letter is: " . $row['reporting_marks'][strlen($row['reporting_marks']) - 1];
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . $row['reporting_marks'][strlen($row['reporting_marks']) - 1] . '</td>'; // CL
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;"></td>';
          }


          // status logic - loaded or M/T
          //print '<td style="border: 1px solid black; padding: 5px;"></td>'; // Sta
  
          if ($row['status'] == "Loaded") {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">L</td>'; // Sta
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">E</td>'; // Sta
          }

          // Mark ATMF/NTAF as dangerous goods
  
          if (($row['car_code'] == "ATMF") || ($row['car_code'] == "NTAF")) {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">Y-Petroleum</td>'; // DG
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;"></td>'; // DG
          }


          //
  

          //print '<td style="border: 1px solid black; padding: 5px;"></td>'; // Gross Mass
  
          print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . trim(explode('|', $row['remarks'])[0]) . '</td>';


          print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . trim(explode('|', $row['remarks'])[1]) . '</td>'; // Length
  

          // current location logic
          if ($row['current_location_id'] > 0) {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;"><b>' . $row['current_station'] . '</b><br>' . $row['current_location'] . '</td>'; // Length
  
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">In Train</td>'; // DG
  
          }


          // Updated Destination Logic
  
          if (($row['status'] == "Empty") || ($row['status'] == "Ordered")) {
            // if the commodity column is empty, this is a non revenue move and the car's destination
            // is the unloading location
            if ($row['consignment_id'] <= 0) {
              print '<td style="border: 1px solid black; padding: 5px; text-align: center;"><b>' . $row['unloading_station'] . '</b><br>' . $row['unloading_location'] . '</td>';
            } else {
              print '<td style="border: 1px solid black; padding: 5px; text-align: center;"><b>' . $row['loading_station'] . '</b><br>' . $row['loading_location'] . '</td>';
            }
          } elseif ($row['status'] == "Loaded") {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;"><b>' . $row['unloading_station'] . '</b><br>' . $row['unloading_location'] . '</td>';
          }


          // Contents
          if ($row['status'] == "Loaded") {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">' . $row['consignment'];
            if (strlen($row['special_instructions']) > 0) {
              print '<br />Spec Instr';
              $special_instructions[$special_instruction_counter][0] = $row['car_code'];
              $special_instructions[$special_instruction_counter][1] = $row['reporting_marks'];
              $special_instructions[$special_instruction_counter][2] = $row['consignment'];
              $special_instructions[$special_instruction_counter][3] = $row['special_instructions'];
              $special_instruction_counter++;
            }
            print '</td>';
          } else {
            print '<td style="border: 1px solid black; padding: 5px; text-align: center;">';
            if (strlen($row['special_instructions']) > 0) {
              print 'Spec Instr';
              $special_instructions[$special_instruction_counter][0] = $row['car_code'];
              $special_instructions[$special_instruction_counter][1] = $row['reporting_marks'];
              $special_instructions[$special_instruction_counter][2] = $row['consignment'];
              $special_instructions[$special_instruction_counter][3] = $row['special_instructions'];
              $special_instruction_counter++;
            }
            print '</td>';
          }

          print '</tr>';
          $row_num++;
          if (($row_num == 14 || $row_num == 25) && count($car_list) > 13) { //only generate next page if it will spill over to another page
            $serial_number++; //generate the next page serial
            print '</table>';
            // generate a page break
            print '<p style="page-break-after: always;">&nbsp;</p>';
            // Header with logo and form title - modified to include logo
            print '<table style="width: 100%; border-collapse: collapse;">';
            print '<tr>';
            print '<td style="width: 30%;"><img src="images/' . $logo . '_logo.jpg" alt="Company Logo" style="height: 100px; width: auto;"></td>';
            print '<td style="width: 45%; text-align: center;"><h2 style="height: 3px;">Train Consist Form x 2010</h2></td>';
            print '<td style="width: 25%; text-align: right; position: relative;">
                      <div style="color: red; font-size: 24px; margin-top: 60px; text-align: right;">' . $serial_number . '</div>
                      <div style="position: absolute; bottom: 0; left: 0;">PAGE ' . ($row_num == 14 ? 2 : 3) . ' OF ' . $page_count . '</div>
                    </td>';
            print '</tr>';
            print '</table>';

            // Top section with train details
            print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black;">';
            print '<tr>';
            print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Train No.<br/><b>' . trim(explode("|", $table_name)[0]) . '</b></td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Date</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 10%;">Dept Time</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 15%;">Origin</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 16%;">Destination</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: ' . $driverNameWidth . ';">Driver Name</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: ' . $timeOnDutyWidth . ';">Time on Duty</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: ' . $depotWidth . ';">Depot</td>';
            print '</tr>';
            print '</table>';

            // Second row with radio and unit details - matching column widths
            print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px;">';
            print '<tr>';
            print '<td style="border: 1px solid black; padding: 5px; width: 32.5%;">Train Radio Number</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 15.25%;">Unit No.</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 16.25%;">P.M. Date Due</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 15.2%;">Driver Name</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 12.3%;">Time on Duty</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 12.1%;">Depot</td>';
            print '</tr>';
            print '</table>';

            // Rest of the form remains the same
            print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px;">';
            print '<tr>';
            print '<td style="border: 1px solid black; padding: 5px; width: 50%;">Mobile Number</td>';
            print '<td style="border: 1px solid black; padding: 5px; width: 30%;">Brake Certificate No.</td>';
            print '<td style="border: 1px solid black; padding: 5px;">Train Type</td>';
            print '</tr>';
            print '</table>';

            // Main consist table
            print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; margin-top: -1px; font-size: 12px; table-layout: auto;">';
            print '<tr style="background-color: #f5f5f5;">';
            print '<th style="border: 1px solid black; padding: 5px;">Sl.<br/>No</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Wagon Class</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Wagon or Locomotive<br/>Number</th>';
            print '<th style="border: 1px solid black; padding: 5px;">CL</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Sta</th>';
            print '<th style="border: 1px solid black; padding: 5px;">DG</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Gross<br/>Mass</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Length<br/>Metres</th>';
            //current location
            print '<th style="border: 1px solid black; padding: 5px;">Current Location</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Destination</th>';
            print '<th style="border: 1px solid black; padding: 5px;">Contents</th>';
            print '</tr>';
          }
        }

        print '</table>';
        print '</div>';
      }



      // finish up the table
      print '</table>';
    } else {
      print '<p style="font-family: verdana;">';
      print 'No switchlist found for ' . $table_name . '<br />';
      print '</p>';
    }

    // if there are any special instructions, print them on their own page
    if ($special_instruction_counter > 0) {
      // generate a page break
      print '<p style="page-break-after: always;">&nbsp;</p>';
      print '<div id="wagon_special_instructions" style="font-family: Arial Narrow, Franklin Gothic, Arial, sans-serif; width: 100%; max-width: 1200px;">';
      print '<h3>Special Instructions</h3>';
      print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; font-size: 12px; table-layout: auto;">';
      print '<tr style="background-color: #f5f5f5;">';
      print '<td style="border: 1px solid black; padding: 5px;">';
      print '<ul>';
      for ($i = 0; $i < $special_instruction_counter; $i++) {
        print '<li>' . $special_instructions[$i][0] . ' ' . $special_instructions[$i][1] . ' (' . $special_instructions[$i][2] . ')</li> <ul><li> ' . $special_instructions[$i][3] . '</li></ul><br>';
      }
      print '</ul>';
      print '</td>';
      print '</tr>';
      print '</table>';
      print '</div>';
    }

    // generate a page break
    print '<p style="page-break-after: always;">&nbsp;</p>';

    // display the selected job's description with X2010 styling
    print '<div id="full_sheet_job_instructions" style="font-family: Arial Narrow, Franklin Gothic, Arial, sans-serif; width: 100%; max-width: 1200px;">';
    print '<h3>Crew Instructions</h3>';
    print '<table style="width: 100%; border-collapse: collapse; border: 1px solid black; font-size: 12px; table-layout: auto;">';
    print '<tr style="background-color: #f5f5f5;">';
    print '<td style="border: 1px solid black; padding: 5px;">';
    print '<h3>Job: ' . $table_name . '</h3>';
    print 'Description: ' . nl2br($job_desc);
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

  function print_chunks($incoming_string, $page_width)
  {
    // because text inside <pre></pre> tags doesn't respect boundaries, reformat any strings that
    // are longer than the page width setting_name by turning the text string into an array and
    // checking each of the individual lines
    $string_array = explode('<br />', nl2br($incoming_string));
    for ($i = 0; $i < sizeof($string_array); $i++) {
      if (strlen($string_array[$i]) <= $page_width) {
        // if the string is less than page width, just print it
        print $string_array[$i] . '<br />';
      } else {
        // turn this string into an array of words and print them one by one until the next word
        // in line will exceed the page width
        $line_string_array = explode(' ', $string_array[$i]);
        $col_counter = 0;
        for ($j = 0; $j < sizeof($line_string_array); $j++) {
          $end_of_word = $col_counter + strlen($line_string_array[$j]);
          if ($end_of_word > $page_width) {
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
  <!-- Begin Strikeout Table Script -->
  <style>
    .strikethrough {
      text-decoration: line-through;
      opacity: 0.5;
    }

    .clear-strikes-button {
      display: inline-block;
      margin: 10px 0 15px 0;
      padding: 5px 10px;
      font-size: 14px;
      background-color: #f44336;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .clear-strikes-button:hover {
      background-color: #d32f2f;
    }

    @media print {
      .noprint {
        display: none !important;
      }
    }
  </style>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const tables = document.querySelectorAll("table");
      let clearButtonInserted = false; // only add button once

      tables.forEach((table, tableIndex) => {
        const headers = table.querySelectorAll("th");
        if (headers.length > 0 && headers[0].innerText.trim().startsWith("Sl.")) {
          const rows = table.querySelectorAll("tbody tr");

          rows.forEach((row, rowIndex) => {
            const firstCell = row.querySelector("td:first-child");
            if (firstCell) {
              const checkbox = document.createElement("input");
              checkbox.type = "checkbox";
              checkbox.style.marginRight = "5px";

              const rowId = `row-${tableIndex}-${rowIndex}`;
              row.setAttribute("data-row-id", rowId);

              if (localStorage.getItem(rowId) === "striked") {
                row.classList.add("strikethrough");
                checkbox.checked = true;
              }

              checkbox.addEventListener("change", function () {
                if (this.checked) {
                  row.classList.add("strikethrough");
                  localStorage.setItem(rowId, "striked");
                } else {
                  row.classList.remove("strikethrough");
                  localStorage.removeItem(rowId);
                }
              });

              firstCell.prepend(checkbox);
            }
          });

          // Insert clear button once above the first consist table
          if (!clearButtonInserted) {
            const clearButton = document.createElement("button");
            clearButton.textContent = "Clear Strikes";
            clearButton.className = "clear-strikes-button noprint";
            clearButton.addEventListener("click", function () {
              document.querySelectorAll("tr[data-row-id]").forEach((row) => {
                row.classList.remove("strikethrough");
                const checkbox = row.querySelector("input[type='checkbox']");
                if (checkbox) checkbox.checked = false;
                localStorage.removeItem(row.getAttribute("data-row-id"));
              });
            });

            table.parentNode.insertBefore(clearButton, table);
            clearButtonInserted = true;
          }
        }
      });
    });
  </script>
  <!-- End Strikeout Table Script -->



  <div class="noprint">
    <br /><a href="display_switchlist.php">Return to Display Switchlist page</a>
    <br />
  </div>
</body>

</html>

