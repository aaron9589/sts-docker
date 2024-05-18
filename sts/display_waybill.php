<html>
  <head>
    <title>STS - Waybills</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
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
    <h3>Waybill</h3>
    <!-- this form generates another page that is modified for better printing  -->
    <!-- the user needs to use the browser's back button to return to this page -->
    <!-- or use the link displayed at the bottom of the last page              -->
    <form action="printable_waybill.php" method="post"> 
    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';

      // generate a drop-down list of filled car orders and the submit button
      $select_string = drop_down_car_orders('waybill_number', '');
      if ($select_string != 'None')
      {
        print 'Select a waybill and then click the Display button.<br /><br />';

        print 'A new page will be displayed that is formatted for printing. If the car assigned to the car order is not at
               the same station as the loading location, an empty car waybill will be also be generated.
               <br /><br />
               Use the browser "Back" button to return to this page or click on the link that is displayed at the bottom
               of the last page.<br /><br />';

        print drop_down_car_orders('waybill_number', '');
        print '<input name="display_btn" value="Display" type="submit"><br /><br >';
      }
      else
      {
        print 'All billed cars are enroute. Waybills cannot be displayed or printed while a car is moving.<br />';
        print 'For information about enroute cars, check the <a href="db_list.php?tbl_name=car_orders">car orders</a>
               or the <a href="db_list.php?tbl_name=cars">cars</a> tables.';
      }

    ?>
    </form>
  </body>
</html>
