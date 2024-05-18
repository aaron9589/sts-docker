<html>
  <head>
    <title>STS - Station Car Report</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      function enable_display_btn()
      {
        document.getElementById("display_btn").disabled = false;
      }
    </script>
  </head>
  <body>
<p><img src="ImageStore/GUI/Menu/report.jpg" width="715" height="144" border="0" usemap="#MapMap">
  <map name="MapMap">
    <area shape="rect" coords="566,7,704,47" href="index.html">
    <area shape="rect" coords="566,96,706,136" href="index-t.html">
    <area shape="rect" coords="563,51,707,91" href="reports.html">
  </map>
</p>
<h2>Reports</h2>
<h3>Station Report of Cars On-Hand</h3>
    <!-- this form generates another page that is modified for better printing  -->
    <!-- the user needs to use the browser's back button to return to this page -->
    <!-- or user the link displayed at the bottom of the last page              -->
    <form action="printable_station_report.php" method="get"> 
    Select a station and then click the Display button.<br />
    <br />
    A list of cars at the selected station will be displayed on a new page<br />
    that is formatted for printing.<br />
    <br />
    Use the browser's <b>BACK</b> button to return to this page or click<br />
    on the link that is displayed at the bottom of the last page.<br /><br />

    <?php
      // bring in the utility files
      require"drop_down_list_functions.php";
      require "open_db.php";

      // generate a drop-down list of stations and the submit button
      print drop_down_stations("station_name", "", "enable_display_btn()");
      print '&nbsp;<input id="display_btn" name="display_btn" value="DISPLAY" type="submit" disabled>&nbsp;&nbsp;';
      print 'Hide Unvailable Cars: <input type="checkbox" id="hide_unavail" name="hide_unavail" checked>';

      // generate some javascript that adds "All" to the top of the station drop-down list
      print '<script>
               var drop_down_list = document.getElementById("station_name");
               var option = document.createElement("option");
               option.value = "0";
               option.text = "All";
               drop_down_list.add(option, drop_down_list[1]);
             </script>';
    ?>
    </form>
  </body>
</html>
