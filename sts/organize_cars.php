<html>
  <head>
    <title>STS - View/Organize Cars</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top;}
      th {border: 1px solid black; padding: 10px;}
      td {border: 1px solid black; padding: 10px;}
      td.checkbox {text-align: center;}
    </style>
    <script>
      function enable_org_loc_btn()
      {
        // enable the org_loc button
        document.getElementById("org_loc").disabled = false;        
      }
      function enable_org_job_btn()
      {
        // enable the org_loc button
        document.getElementById("org_job").disabled = false;        
      }
    </script>
    <script>
    // drag-n-drop function
    var row;

    function start(){  
      row = event.target; 
    }
    function dragover(){
      var e = event;
      e.preventDefault(); 
      
      let children= Array.from(e.target.parentNode.parentNode.children);
      
      if(children.indexOf(e.target.parentNode)>children.indexOf(row))
        e.target.parentNode.after(row);
      else
        e.target.parentNode.before(row);
    }

    /* --- Not used, at least not yet ---
    // insert blank row
    function insert_blank_row()
    {
      var table = document.getElementById("car_table");
      var blank_row = table.insertRow(1);
      var blank_td00 = blank_row.insertCell(0);
      blank_td00.innerHTML = "<img src='./ImageStore/DB_Images/graphics/up_arrow.png' onclick='move_row(this.parentElement, -1);' alt='UP'/>" +
      " <br /><br />" + " <img src='./ImageStore/DB_Images/graphics/dn_arrow.png' onclick='move_row(this.parentElement, 1);' alt='DN'/>";
      blank_td00.style = "text-align: center; vertical-align: middle;";
      var blank_td01 = blank_row.insertCell(1);
      blank_td01.colSpan = 8;
      blank_td01.innerHTML = "&nbsp;";
    }
    --- Not used, at least not yet ---*/
    </script>
    <?php
      // bring in the javascript function that shows rollingstock photos
      require 'show_image.php';
    ?>
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
    <h3>View and/or Organize Cars</h3>
    <!-- <form method="get" action="organize_cars.php"> -->
    <?php
      // this program displays a list of locations and a list of jobs, either of which can be used
      // to display a list of cars at that location or in that job so the user can adjust the positions of the cars

      // generate some javascript to enable moving cars forward or backward in the consist
      print '<script>
               function move_row(cell, move)
               {
                 // incoming cell is the one containing the up or down arrow image
                 var row_num = cell.parentElement.rowIndex;

                 // get the collection of rows in the table
                 var rows = document.getElementById("car_table").rows;

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
             </script>';

      // bring in the function files
      require 'open_db.php';
      require 'drop_down_list_functions.php';

      // open a database connection
      $dbc = open_db();
      
      // set up a big table with two cells on the top row, one for the locations and the other for the jobs, and the bottom row for the list of cars
      print '<table>';
      print '  <tr>';
      print '    <td colspan="2">';
      print '      Use these two functions to view cars in selected trains or on selected tracks and also to organize them in the<br />
                   database so it knows their physical arrangement.';
      print '    </td>';
      print '  </tr>';
      print '  <tr>';
      // set up the drop-down list of jobs
      print '    <td>';
      print '      Select a job/train and click the <b>VIEW/ORGANIZE</b> button<br /><br />';
      print        drop_down_jobs('job_list', '3', 'enable_org_job_btn()') . '&nbsp;';
      print '      <button id="org_job" name="org_job" onclick="get_cars_in_job();" disabled style="background-color: #80ff00; font-size: 24px;">
                   VIEW/ORGANIZE
                   </button><br /><br />';
      print '      After arranging the cars, click <a href="display_switchlist.php"><b>here</b></a> to generate an<br/> updated switch list if desired.';
      print '    </td>';
      // set up the drop-down list of locations
      print '    <td>';
      print '      Select a location and click the <b>VIEW/ORGANIZE</b> button<br /><br />';
      print        drop_down_locations('location_list', '0', 'enable_org_loc_btn()') . '&nbsp;';
      print '      <button id="org_loc" name="org_loc" onclick="get_cars_at_location()" disabled style="background-color: #80ff00; font-size: 24px;">
                   VIEW/ORGANIZE
                   </button><br /><br />';
      print '      After arranging the cars, click <a href="display_station_report.php"><b>here</b></a> to generate an<br /> updated station car report if desired.';
      print '    </td>';
      print '  </tr>';
      print '  <tr>';
      print '    <td colspan="2">
                   After adjusting the positions of the cars, click the <b>UPDATE</b> button to refresh the list with the new positions.<br />
                   <h3 id="location_or_job_name"></h3>
                   <button id="update_pos" name="update_pos" disabled onclick="update_car_positions();" style="background-color: #80ff00; font-size: 24px;">
                   <b>UPDATE</b>
                   </button>';
                   /*--- Not used, at least not yet ---
                   <button id="insert_blank_button" name="insert_blank_button" disabled onclick="insert_blank_row();" style="back: #80ff00; font-size: 24px;">
                   INSERT BLANK ROW</button>
                   --- Not used, at least not yet ---*/
      print '      <br />
                 </td>';
      print '  </tr>';
      print '  <tr>';
      print '    <td id="car_section" colspan="2">';
      print '    </td>';
      print '  </tr>';
      print '</table>';

    ?>
    <!-- </form> -->
  </body>
  <script>
    //////////////////////////////////// call back function for the HttpRequest functions //////////////////////////////
    
    // this is the call back function for the list of cars at the selected station or in the selected job
    function populate_car_table(xmlhttp)
    {
      if (xmlhttp.responseText.substring(0, 10) == "No cars at")
      {
        // tell the user that there aren't any cars at this location
        document.getElementById("car_section").innerHTML = "<tr>" + 
          "<td>No cars found at this location.</td></tr>";
        // disable the update and insert blank row buttons
        document.getElementById("update_pos").disabled = true;
        // document.getElementById("insert_blank_button").disabled = true;
      }
      else if (xmlhttp.responseText.substring(0, 10) == "No cars in")
      {
        // tell the user that there aren't any cars in this train
        document.getElementById("car_section").innerHTML = "<tr>" + 
          "<td>This job is not handling any cars.</td></tr>";
        // disable the update and insert blank row buttons
        document.getElementById("update_pos").disabled = true;
        // document.getElementById("insert_blank_button").disabled = true;
      }
      else
      {
        // display the table being returned from the server
        document.getElementById("car_section").innerHTML = xmlhttp.responseText;
        // enable the update and insert blank row buttons
        document.getElementById("update_pos").disabled = false;
        // document.getElementById("insert_blank_button").disabled = false;
      }
    }

    ///////////////////////////////////////// javascript for organizing cars at a location /////////////////////////////
    
    // build some javascript functions that will bring in a list of cars from the specified location
    function get_cars_at_location()
    {
      // check to see if the selection from the station list is non-blank
      if (document.getElementById('location_list').value.length > 0)
      {
        // display the name of the location in the instructions block
        var location_index = document.getElementById('location_list').selectedIndex;
        document.getElementById('location_or_job_name').innerHTML = "Location: " + document.getElementById('location_list').options[location_index].text;
      
        // submit the request for the cars at the selected location
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function()
        {
          if (this.readyState == 4 && this.status == 200)
          {
             populate_car_table(this);
          }
        }
        var url = 'get_location_cars.php?location=' + encodeURIComponent(document.getElementById('location_list').value);
        xmlhttp.open('GET', url, true);
        xmlhttp.send();
      }
    }
    
    //////////////////////////////////////// javascript for organizing cars in a job /////////////////////////////////////
    
    // build some javascript functions that will bring in a list of cars from the specified job
    function get_cars_in_job()
    {
      // check to see if the selection from the station list is non-blank
      if (document.getElementById('job_list').value.length > 0)
      {
        // display the name of the location in the instructions block
        var job_index = document.getElementById('job_list').selectedIndex;
        document.getElementById('location_or_job_name').innerHTML = "Job: " + document.getElementById('job_list').options[job_index].text;
      
        // submit the request for the cars at the selected location
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function()
        {
          if (this.readyState == 4 && this.status == 200)
          {
             populate_car_table(this);
          }
        }
        var url = 'get_job_cars.php?job=' + encodeURIComponent(document.getElementById('job_list').value);
        xmlhttp.open('GET', url, true);
        xmlhttp.send();
      }
    }
    
    //////////////////////////////////// javascript function to update car positions in the database ////////////////////////////
    
    // this function takes each car's position in the car_table table and uses it to update the car's position in the cars table
    function update_car_positions()
    {
      // build the parameters to the url
      var parms = "";
      var url, car_id, car_pos;
      var first_parm = true;
      var car_table = document.getElementById("car_table");
      for (var i=1; i<car_table.rows.length; i++)
      {
        car_id = car_table.rows[i].cells[2].innerHTML;
        if (!first_parm)
        {
          parms = parms + "&";
        }
        parms = parms + "car" + i + "=" + car_id + "&pos" + i + "=" + i;
        first_parm=false;
      }
      // add the car count to the start of the parameters
      parms = "car_count=" + (car_table.rows.length - 1) + "&" + parms;
console.log(parms);      
      // submit the request for the cars at the selected location
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function()
      {
        if (this.readyState == 4 && this.status == 200)
        {
           populate_car_table(this);
        }
      }
      //var url = 'update_car_positions.php?' + encodeURIComponent(parms);
      var url = 'update_car_positions.php?' + parms;
console.log(url);
      xmlhttp.open('GET', url, true);
      xmlhttp.send();
    }
  </script>
</html>
