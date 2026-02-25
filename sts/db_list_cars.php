<!-- Bootstrap CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<!-- include the HTML table sort scripts -->
<script src="sorttable.js"></script>

<?php
  // list_cars.php

  // adds a new car to the cars table if the Update button was
  // clicked and if there is something in the reporting marks text box.

  // generate some javascript to display the table name, identify this table to the form, and set the
  // update button's tab index as well as disabling it until the data entry radio button is clicked
  print '<script type="text/javascript">
           document.getElementById("table_name").innerHTML = "Cars";
           document.getElementById("tbl_name").value = "cars";
           document.getElementById("update_btn").tabIndex = "9";
           document.getElementById("update_btn").disabled = true;
         </script>';

  // Add inline editing styles and HTML
  print '
  <style>
    .editable-cell {
      cursor: pointer;
      position: relative;
      padding: 8px !important;
    }
    .editable-cell:hover {
      background-color: #ffeb3b;
    }
    .editable-cell.editing {
      background-color: #fff9c4 !important;
      padding: 4px !important;
    }
    .editable-input {
      width: 100%;
      padding: 4px;
      border: 2px solid #2196F3;
      border-radius: 3px;
      font-family: inherit;
      font-size: inherit;
      box-sizing: border-box;
    }
    .editable-select {
      width: 100%;
      padding: 4px;
      border: 2px solid #2196F3;
      border-radius: 3px;
      font-family: inherit;
      font-size: inherit;
    }
    .save-indicator {
      position: absolute;
      top: 50%;
      right: 5px;
      transform: translateY(-50%);
      font-size: 18px;
      display: none;
    }
    .save-indicator.saving {
      display: inline;
      color: #ff9800;
    }
    .save-indicator.saved {
      display: inline;
      color: #4caf50;
    }
    table {
      font-size: 13px;
    }
    th, td {
      padding: 6px;
    }
    .alert {
      margin: 10px 0;
    }
  </style>

  <div id="saveNotification" class="alert alert-info" style="display:none;"></div>
  ';
  ?>

  <script>
    // Cache for dropdown options
    let dropdownCache = {
      carCodes: [],
      locations: [],
      jobs: [],
      loaded: false
    };

    // Load dropdown options from server
    function loadDropdownOptions() {
      if (dropdownCache.loaded) {
        return $.Deferred().resolve().promise();
      }

      return $.ajax({
        url: "get_dropdowns_ajax.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
          dropdownCache = {
            carCodes: data.carCodes || [],
            locations: data.locations || [],
            jobs: data.jobs || [],
            loaded: true
          };
        },
        error: function(error) {
          console.error("Error loading dropdown options:", error);
        }
      });
    }

    function makeEditable(cell, carId, field, fieldType = "text", currentValue = null) {
      if (cell.classList.contains("editing")) return;

      const originalValue = currentValue || cell.textContent.trim();
      cell.classList.add("editing");

      // Store reference to the row for later use
      const row = cell.closest("tr");

      // Show loading indicator while we load dropdown data
      cell.textContent = "Loading...";

      // Ensure dropdown options are loaded before proceeding
      loadDropdownOptions().done(function() {
        createEditableElement();
      }).fail(function() {
        alert("Failed to load dropdown options");
        cell.classList.remove("editing");
      });

      function createEditableElement() {
        let input;

        if (fieldType === "status") {
          input = document.createElement("select");
          input.className = "editable-select";
          const options = ["", "Empty", "Unavailable"];
          options.forEach(opt => {
            const optionEl = document.createElement("option");
            optionEl.value = opt;
            optionEl.textContent = opt || "-- Select Status --";
            if (opt === originalValue) optionEl.selected = true;
            input.appendChild(optionEl);
          });
        } else if (fieldType === "car_code") {
          input = document.createElement("select");
          input.className = "editable-select";
          const optionEl0 = document.createElement("option");
          optionEl0.value = "";
          optionEl0.textContent = "-- Select Car Code --";
          input.appendChild(optionEl0);

          dropdownCache.carCodes.forEach(code => {
            const optionEl = document.createElement("option");
            optionEl.value = code.id;
            optionEl.textContent = code.code;
            if (code.id == currentValue) optionEl.selected = true;
            input.appendChild(optionEl);
          });
        } else if (fieldType === "location") {
          input = document.createElement("select");
          input.className = "editable-select";
          const optionEl0 = document.createElement("option");
          optionEl0.value = "";
          optionEl0.textContent = "-- Select Location --";
          input.appendChild(optionEl0);

          dropdownCache.locations.forEach(loc => {
            const optionEl = document.createElement("option");
            optionEl.value = loc.id;
            optionEl.textContent = loc.station + " - " + loc.location;
            if (loc.id == currentValue) optionEl.selected = true;
            input.appendChild(optionEl);
          });
        } else if (fieldType === "job") {
          input = document.createElement("select");
          input.className = "editable-select";
          const optionEl0 = document.createElement("option");
          optionEl0.value = "";
          optionEl0.textContent = "-- Select Job --";
          input.appendChild(optionEl0);

          dropdownCache.jobs.forEach(job => {
            const optionEl = document.createElement("option");
            optionEl.value = job.id;
            optionEl.textContent = job.name;
            if (job.id == currentValue) optionEl.selected = true;
            input.appendChild(optionEl);
          });
        } else {
          input = document.createElement("input");
          input.type = "text";
          input.className = "editable-input";
          input.value = originalValue;
        }

        cell.textContent = "";
        cell.appendChild(input);

        const indicator = document.createElement("span");
        indicator.className = "save-indicator";
        cell.appendChild(indicator);

        input.focus();
        input.select?.();

        function saveValue() {
          const newValue = input.value;
          if (newValue === originalValue) {
            cell.classList.remove("editing");
            cell.textContent = originalValue;
            return;
          }

          indicator.textContent = "⏳";
          indicator.classList.add("saving");

          $.ajax({
            url: "update_car_ajax.php",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({
              car_id: carId,
              field: field,
              value: newValue
            }),
            success: function(response) {
              indicator.textContent = "✓";
              indicator.classList.remove("saving");
              indicator.classList.add("saved");
              setTimeout(() => {
                cell.classList.remove("editing");
                if (response.formatAsHtml) {
                  cell.innerHTML = response.displayValue;
                } else {
                  cell.textContent = response.displayValue || newValue || originalValue;
                }
                indicator.textContent = "";
                indicator.classList.remove("saved");

                // If status changed to Empty or Unavailable, refresh dependent fields
                if (field === "status" && (newValue === "Empty" || newValue === "Unavailable")) {
                  refreshDependentCells(row);
                }
              }, 500);
            },
            error: function(error) {
              indicator.textContent = "✗";
              indicator.classList.remove("saving");
              indicator.classList.add("saved");
              console.error("Error updating field:", error);
              setTimeout(() => {
                cell.classList.remove("editing");
                cell.textContent = originalValue;
                indicator.textContent = "";
                indicator.classList.remove("saved");
              }, 1000);
            }
          });
        }

        input.addEventListener("blur", saveValue);
        input.addEventListener("keydown", (e) => {
          if (e.key === "Enter") saveValue();
          if (e.key === "Escape") {
            cell.classList.remove("editing");
            cell.textContent = originalValue;
          }
        });
      }
    }

    function refreshDependentCells(row) {
      // The row structure is: [0]Reporting Marks, [1]Car Code, [2]Current Location, [3]Position, [4]Status, [5]Handled By, [6]Consignment, [7]Loading, [8]Unloading, [9]Remarks, [10]Home, [11]Load Count, [12]Pool, [13]RFID, [14]Last Spotted, [15]History
      if (row && row.cells) {
        // Clear the dependent cells that will be emptied when car is Empty/Unavailable
        row.cells[5].textContent = "";  // Handled By
        row.cells[6].textContent = "";  // Consignment
        row.cells[7].textContent = "";  // Loading Location
        row.cells[8].textContent = "";  // Unloading Location
      }
    }
  </script>

  <?php

  // generate some javascript that will hide rows
  // - hide_rows() is called to hide cars with a status of "Unavailable"
  // - filter_rows() is called to hide cars that don't have the selected property
  // - filter_reporting_marks() is called to hide cars who's reporting marks don't start with the specified characters
  // - clear_filters() is called to show all cars
  print '<script type="text/javascript">
           function hide_rows(tbl_col)
           {
             // confirm that the hide_unavail checkbox is checked
             if (document.getElementById("hide_unavail").checked == true)
             {
               var table = document.getElementById("car_tbl");
               //iterate through rows
               for (var i = 6, row; row = table.rows[i]; i++)
               {
                 if (row.cells[tbl_col].innerText == "Unavailable")
                 {
                     row.style.display = "none";
                 }
               }
             }
             else
             {
               var table = document.getElementById("car_tbl");
               //iterate through rows
               for (var i = 6, row; row = table.rows[i]; i++)
               {
                 if (row.cells[tbl_col].innerText == "Unavailable")
                 {
                   row.style.display = "";
                 }
               }
             }
           }

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

               var table = document.getElementById("car_tbl");

               //iterate through rows
               for (var i = 6, row; row = table.rows[i]; i++)
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

           function filter_reporting_marks(needle)
           {
             // confirm that a non-blank option has been selected
             if (needle.length > 0)
             {
               var table = document.getElementById("car_tbl");

               //iterate through rows
               for (var i = 6, row; row = table.rows[i]; i++)
               {
                 var needle_length = needle.length;

                 var haystack = row.cells[0].innerText.substr(0, needle_length);

                 if (haystack != needle)
                 {
                   row.style.display = "none"
                 }
               }
             }
           }

           // generate some javascript that disable the data entry boxes (default setting) and enable the
           // filters when the filter radio button is clicked
           function add_cars()
           {
             document.getElementById("update_btn").disabled = false;
             document.getElementById("rptgmarks").disabled = false;
             document.getElementById("car_code").disabled = false;
             document.getElementById("current_location").disabled = false;
             document.getElementById("position").disabled = false;
             document.getElementById("remarks").disabled = false;
             document.getElementById("home_location").disabled = false;
             document.getElementById("RFID_code").disabled = false;

             document.getElementById("reporting_marks_filter").disabled = true;
             document.getElementById("car_code_filter").disabled = true;
             document.getElementById("current_location_filter").disabled = true;
             document.getElementById("status_filter").disabled = true;
             document.getElementById("job_filter").disabled = true;
             document.getElementById("commodity_filter").disabled = true;
             document.getElementById("loading_location_filter").disabled = true;
             document.getElementById("unloading_location_filter").disabled = true;
             document.getElementById("home_location_filter").disabled = true;
           }

           // generate some javascript that disables the filters and enables the data entry boxes when
           // the data entry radio button is clicked
           function filter_cars()
           {
             document.getElementById("update_btn").disabled = true;
             document.getElementById("rptgmarks").disabled = true;
             document.getElementById("car_code").disabled = true;
             document.getElementById("current_location").disabled = true;
             document.getElementById("position").disabled = true;
             document.getElementById("remarks").disabled = true;
             document.getElementById("home_location").disabled = true;
             document.getElementById("RFID_code").disabled = true;

             document.getElementById("reporting_marks_filter").disabled = false;
             document.getElementById("car_code_filter").disabled = false;
             document.getElementById("current_location_filter").disabled = false;
             document.getElementById("status_filter").disabled = false;
             document.getElementById("job_filter").disabled = false;
             document.getElementById("commodity_filter").disabled = false;
             document.getElementById("loading_location_filter").disabled = false;
             document.getElementById("unloading_location_filter").disabled = false;
             document.getElementById("home_location_filter").disabled = false;
           }

           // replace reloads the window without sending the POST parameters through again
           // because doing just a window.location.reload() was sending those parameters
           // through atain which caused a second record to be inserted into the cars table
           function clear_filters()
           {
             window.location.replace("db_list.php?tbl_name=cars");
           }
         </script>';

  // set up some styles for the table
  print '<style>
           th, td {padding: 3px;}
         </style>';

  // get a database connection
  $dbc = open_db();

  // has the submit button been clicked?
  if (isset($_POST['update_btn']))
  {
    // yes, so check to see if there is anything in the input text boxes
    if (strlen($_POST['rptgmarks']) > 0)
    {
      // check for bad position value
      if (strlen($_POST['position']) > 0)
      {
        $position = $_POST['position'];
      }
      else
      {
        $position = 0;
      }
      // build the insert query
      $sql = 'insert into cars (reporting_marks, car_code_id, current_location_id, position, status, handled_by_job_id, remarks, home_location, load_count, RFID_code)
          	  values ("' . $_POST['rptgmarks'] . '",
                      "' . $_POST['car_code'] . '",
                      "' . $_POST['current_location'] . '",
                      "' . $position . '",
                      "' . 'Empty' . '",
                       ' . '0,
                      "' . $_POST['remarks'] . '",
                      "' . $_POST['home_location'] . '",
                      "0",
                      "' . $_POST['RFID_code'] .'")';

      // run the insert operation
// print $sql;
      if (!mysqli_query($dbc, $sql))
      {
        print 'Insert Error: ' . mysqli_error($dbc) . '<br /><br />';
      }
    }
  }

  // generate a check box that when activated will run a javascript routine which will hide cars with a status of "Unavailable"
  $parm_string = '4, \'Unavailable\'';
  print 'Hide unavailable cars: <input type="checkbox" id="hide_unavail" name="hide_unavail" onclick="hide_rows(' . $parm_string . ');"><br /><br />';

  // generate the table
  print '<table class="sortable" id="car_tbl" style="font: normal 12px Verdana, Arial, sans-serif;"  style="white-space: nowrap;">
           <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">
           <input type="radio" name="entry-filter" id="entry" value="entry" checked=false onclick="add_cars()">Add New Car</caption>
           <thead>
             <tr style="background-color: lightgreen;">
               <th>Reporting<br />Marks</th>
               <th>Car<br />Code</th>
               <th>Current<br /><u>Station</u><br />Location</th>
               <th>Position</th>
               <th>Status</th>
               <th>Handled By</th>
               <th>Consignment</th>
               <th>Loading<br /><u>Station</u><br />Location</th>
               <th>Unloading<br /><u>Station</u><br />Location</th>
               <th>Remarks</th>
               <th>Home<br /><u>Station</u><br />Location</th>
               <th>Load<br />Count</th>
               <th>Shipment<br />Pool</th>
               <th>RFID<br />Code</th>
               <th>Last<br />Spotted</th>
               <th>Car<br />History</th>
             </tr>
             <tr id="entry_row" style="background-color: lightgreen;">
               <td style="text-align:center;">
                 <input id="rptgmarks" name="rptgmarks" type="text" tabindex="1" size="10" required autofocus>
               </td>
               <td style="text-align:center;">' . drop_down_car_codes('car_code', 2, 'no_wild') . '</td>
               <td style="text-align:center;">' . drop_down_locations('current_location', 3, '') . '</td>
               <td style="text-align:center;"><input id="position" name="position" type="text" tabindex="4" size="3"></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td></td>
               <td style="text-align: center";><input id="remarks" name="remarks" type="text" tabindex="5"></td>
               <td style="text-align:center;">' . drop_down_locations('home_location', 6, '') . '</td>
               <td></td>
               <td></td>
               <td style="text-align:center;"><input id="RFID_code" name="RFID_code" type="text" tabindex=7></td>
               <td></td>
               <td></td>
             </tr>
             <tr>
               <td colspan="15" style="border:0px; height:50px">&nbsp;
             </td>
             </tr id="filter_row">
               <td style="font: bold 15px Verdana, Arial, sans-serif; text-align:left; border:0px;">
                 <input type="radio" name="entry-filter" id="filter" value="filter" checked=true onclick="filter_cars()"><b>Filters</b>
               </td>
               <td  colspan="14" style="border: 0px;">
                 <input type="button" id="clear_filters_btn" name="clear_filters_btn" value="CLEAR FILTERS"
                  onclick="clear_filters();" style=" font: normal 10px Verdana, Arial, sans-serif;">
               </td>
             </tr>
             <tr style="background-color: yellow;">
               <td style="border-bottom: 0px; border-right: 0px;">
                 <input type="text" id="reporting_marks_filter" size="10" onchange="filter_reporting_marks(document.getElementById(\'reporting_marks_filter\').value);">
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(1, document.getElementById(\'car_code_filter\').options[document.getElementById(\'car_code_filter\').selectedIndex].text);
                 document.getElementById(\'car_code_filter\').disabled=true;">' .
                 drop_down_car_codes('car_code_filter', '', 'no_wild') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(2, document.getElementById(\'current_location_filter\').options[document.getElementById(\'current_location_filter\').selectedIndex].text);
                 document.getElementById(\'current_location_filter\').disabled=true;">' .
                 drop_down_locations('current_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"></td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(4, document.getElementById(\'status_filter\').options[document.getElementById(\'status_filter\').selectedIndex].text);
                 document.getElementById(\'status_filter\').disabled=true;">' .
                 drop_down_status('status_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(5, document.getElementById(\'job_filter\').options[document.getElementById(\'job_filter\').selectedIndex].text);
                 document.getElementById(\'job_filter\').disabled=true;">' .
                 drop_down_jobs('job_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(6, document.getElementById(\'commodity_filter\').options[document.getElementById(\'commodity_filter\').selectedIndex].text);
                 document.getElementById(\'commodity_filter\').disabled=true;">' .
                 drop_down_commodities('commodity_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(7, document.getElementById(\'loading_location_filter\').options[document.getElementById(\'loading_location_filter\').selectedIndex].text);
                 document.getElementById(\'loading_location_filter\').disabled=true;">' .
                 drop_down_locations('loading_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(8, document.getElementById(\'unloading_location_filter\').options[document.getElementById(\'unloading_location_filter\').selectedIndex].text);
                 document.getElementById(\'unloading_location_filter\').disabled=true;">' .
                 drop_down_locations('unloading_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"</td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"
                 onchange="filter_rows(10, document.getElementById(\'home_location_filter\').options[document.getElementById(\'home_location_filter\').selectedIndex].text);
                 document.getElementById(\'home_location_filter\').disabled=true;">' .
                 drop_down_locations('home_location_filter', '', '') . '
               </td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"></td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"></td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"></td>
               <td style="border-bottom: 0px; border-left: 0px; border-right:0px;"></td>
               <td style="border-bottom: 0px; border-left: 0px;"></td>
             </tr>
             <tr style="position: sticky; top: 0; background-color: #F5F5F5">
               <th><i>Reporting<br />Marks</i></th>
               <th><i>Car<br />Code</i></th>
               <th><i>Current<br /><u>Station</u><br />Location</i></th>
               <th><i>Position</i></th>
               <th><i>Status</i></th>
               <th><i>Handled By</i></th>
               <th><i>Consignment</i></th>
               <th><i>Loading<br /><u>Station</u><br />Location</i></th>
               <th><i>Unloading<br /><u>Station</u><br />Location</i></th>
               <th><i>Remarks</i></th>
               <th><i>Home<br /><u>Station</u><br />Location</i></th>
               <th><i>Load<br />Count</i></th>
               <th class="sorttable_nosort">Shipment<br />Pool</th>
               <th><i>RFID<br />Code</i></th>
               <th><i>Last<br />Spotted</i></th>
               <th>Car<br />History</th>
             </tr>
           </thead>';

  // query the database for all of the cars (and associated info from waybills and shipments) and display them in a table
  $sql = 'select cars.id as id,
                 cars.reporting_marks as reporting_marks,
                 cars.car_code_id as car_code_id,
                 cars.current_location_id as current_location_id,
		             cars.position as position,
                 cars.status as status,
                 cars.handled_by_job_id as handled_by,
                 cars.remarks as remarks,
                 cars.load_count as load_count,
                 cars.RFID_code as RFID_code,
                 cars.last_spotted as last_spotted,
                 car_orders.waybill_number as waybill_number,
                 car_orders.shipment as shipment,
                 commodities.code as consignment,
                 shipments.loading_location as loading_location_id,
                 shipments.unloading_location as unloading_location_id,
		             jobs.name as job_name,
		             car_codes.code as car_code,
		             loc01.code as current_location,
                 loc02.code as loading_location,
                 loc03.code as unloading_location,
                 loc04.code as home_location,
                 cars.home_location as home_location_id,
                 sta01.station as current_station,
                 sta02.station as loading_station,
                 sta03.station as unloading_station,
                 sta04.station as home_station
            from cars
            left join car_orders on cars.id = car_orders.car
            left join shipments on car_orders.shipment = shipments.id
            left join commodities on commodities.id = shipments.consignment
            left join jobs on cars.handled_by_job_id = jobs.id
            left join car_codes on cars.car_code_id = car_codes.id
            left join locations loc01 on cars.current_location_id = loc01.id
            left join locations loc02 on shipments.loading_location = loc02.id
            left join locations loc03 on shipments.unloading_location = loc03.id
            left join locations loc04 on cars.home_location = loc04.id
            left join routing sta01 on sta01.id = loc01.station
            left join routing sta02 on sta02.id = loc02.station
            left join routing sta03 on sta03.id = loc03.station
            left join routing sta04 on sta04.id = loc04.station
           order by cars.reporting_marks';
// print "SQL: " . $sql . "<br /><br />";
  $rs = mysqli_query($dbc, $sql);

  if (mysqli_num_rows($rs) > 0)
  {
    $row_count = 3; // don't count the header rows

    while ($row = mysqli_fetch_array($rs))
    {
      // if a shipment is in a pool arrangement with certain cars, highlight the background-color
      $sql_pool = 'select count(*) from pool where car_id = "' . $row['id'] . '"';
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

      // build the table rows
      print '<tr id="row' . $row_count . '" style="background-color:' . $background . ';">';

  	  // column 1 - reporting marks
      print '<td><a href="db_edit.php?tbl_name=cars&obj_name=' . urlencode($row['id']) . '&obj_id=' . urlencode($row['reporting_marks']) . '">' . $row['reporting_marks'] . '</td>';

  	  // column 2 - car code
      print '<td class="editable-cell" style="text-align: center" onclick="makeEditable(this, ' . $row['id'] . ', \'car_code_id\', \'car_code\', ' . $row['car_code_id'] . ')">' . $row['car_code'] . '</td>';

  	  // column 3 - current location - a current location of 0 (zero) means that the car is in the assigned job
      if ($row['current_location_id'] > 0)
      {
        print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'current_location_id\', \'location\', ' . $row['current_location_id'] . ')"><u>' . $row['current_station'] . '</u><br />' . $row['current_location'] . '</td>';
      }
      else
      {
        print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'current_location_id\', \'location\', 0)">In Train</td>';
      }

  	  // column 4 - position on the track or in the block of cars
      print '<td class="editable-cell" style="text-align: center" onclick="makeEditable(this, ' . $row['id'] . ', \'position\')">' . $row['position'] . '</td>';

  	  // column 5 - empty/ordered/loaded status
      print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'status\', \'status\')">' . $row['status'] . '</td>';

  	  // column 6 - name of the job handling this car
      print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'handled_by_job_id\', \'job\', ' . $row['handled_by'] . ')">' . ($row['job_name'] ? $row['job_name'] : '(none)') . '</td>';

      // column 7 - for the consignment column, check to see if this is a non-revenue waybill
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        // if so, display "Non-Revenue"
        print '<td>Non-Revenue</td>';
      }
      else
      {
        // otherwise display the normal consignment
        print '<td>' . $row['consignment'] . '</td>';
      }

      // column 8 - for the loading location column, check to see if this is a non-revenue waybill
      // if it is non-revenue, display N/A
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
        print '<td>N/A</td>';
      }
      else
      {
        // if this is regular waybill, display the normal contents for the column
        if ($row['status'] == 'Ordered')
        {
          // display the car's next destination in bold text
          print '<td><b><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '<b></td>';
        }
        else if (($row['status'] == "Empty") || ($row['status'] == "Unavailable"))
        {
          print '<td></td>';
        }
        else
        {
          print '<td><u>' . $row['loading_station'] . '</u><br />' . $row['loading_location'] . '</td>';
        }
	    }

      // column 9 - for the final destination (unloading location) column, check to see if this is a non-revenue waybill
      // if it is non-revenue, display it's destination which is located in it's shipment column
      if (substr($row['waybill_number'], 4, 1) == 'E')
      {
	    	// run two quick queries to find the location code for this car's destination
	    	$sql2 = 'select code from locations where id = "' . $row['shipment'] . '"';
	    	$rs2 = mysqli_query($dbc, $sql2);
    		$row2 = mysqli_fetch_array($rs2);

        $sql3 = 'select routing.station from routing, locations where routing.id = locations.station and locations.id = "' . $row['shipment'] . '"';
        $rs3 = mysqli_query($dbc, $sql3);
        $row3 = mysqli_fetch_array($rs3);

        print '<td><b><u>' . $row3['station'] . '</u><br />' . $row2['code'] . '<b></td>';
      }
      else
      {
        // if this is regular waybill, display the normal contents for the column
        if ($row['status'] == 'Loaded')
        {
          // display the car's next destination in bold text
          print '<td><b><u>' . $row['unloading_station'] .  '</u><br />' . $row['unloading_location'] . '<b></td>';
        }
        else if (($row['status'] == "Empty") || ($row['status'] == "Unavailable"))
        {
          print '<td></td>';
        }
        else
        {
          print '<td><u>' . $row['unloading_station'] . '</u><br />' . $row['unloading_location'] . '</td>';
        }
      }

	    // column 10 - remarks
      print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'remarks\')">' . $row['remarks'] . '</td>';

      // column 11 - home location
      print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'home_location\', \'location\', ' . $row['home_location_id'] . ')"><u>' . $row['home_station'] . '</u><br />' . $row['home_location'] . '</td>';

	    // column 12 - load count
      print '<td style="text-align: center">' . $row['load_count'] . '</td>';

      // column 13 - shipment pool
      print '<td style="text-align: center"><a href="db_edit.php?tbl_name=pool&obj_name=' . $row['id'] . '&obj_id=' . $row['reporting_marks'] . '">Add/Edit</a></td>';

      // column 14 - RFID code
      print '<td class="editable-cell" onclick="makeEditable(this, ' . $row['id'] . ', \'RFID_code\')">' . $row['RFID_code'] . '</td>';

      // column 15 - session number this car was last spotted
      print '<td style="text-align: center">' . $row['last_spotted'] . '</td>';

      // column 16 - link to this car's history
      print '<td><a href="car_history.php?car_id=' . $row['id'] . '">Rpt</a></td>';

      print '</tr>';
      $row_count++;
    }
  }
  print '</table>';

  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("rptgmarks").focus();</script>';

  // add some extra lines to the instructions div and initally disable the data entry boxes which
  // can be enabled by clicking on the add car radio button
  print '<script>
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML +
                                                               \'Filters can be used to hide rows. \';
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML +
                                                               \'Click on column titles shown in <i>italics</i> to sort the table<br /><br />\';
           document.getElementById("instructions").innerHTML = document.getElementById("instructions").innerHTML +
                                                               \'Cars in a car/shipment pooling arrangement are <span style="background-color:#ffff80;">highlighted.</span><br /><br />\';

           document.getElementById("rptgmarks").disabled = true;
           document.getElementById("car_code").disabled = true;
           document.getElementById("current_location").disabled = true;
           document.getElementById("position").disabled = true;
           document.getElementById("remarks").disabled = true;
           document.getElementById("home_location").disabled = true;
           document.getElementById("RFID_code").disabled = true;
         </script>';

  // add a "None" option to the top of the home location drop down box
  print '<script>
           var select = document.getElementById("home_location");
           var option = document.createElement("option");
           option.text = "None";
           option.value = "0";
           select.add(option, select[1]);
         </script>';
?>
