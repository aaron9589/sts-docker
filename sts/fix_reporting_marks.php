<html>
  <head>
    <title>STS - Fix Reporting Marks</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
  </head>
  <body>
    <p> <img src="ImageStore/GUI/Menu/maint.jpg" width="715" height="147" border="0" usemap="#Map3">
      <map name="Map3">
        <area shape="rect" coords="567,5,710,47" href="index.html">
        <area shape="rect" coords="568,98,708,142" href="index-t.html">
        <area shape="rect" coords="567,54,711,92" href="db-maint.html">
      </map>
    </p>
    <h2><a href="validate_db.php"><img src="ImageStore/GUI/Menu/validate.png" width="166" height="40" border="0"></a></h2>
    <h2>Database Maintenance</h2>
    <h3 >Fix Reporting Marks</h3>
    <div id="instructions">
    The Association of American Railroads (AAR) controls the issuance of<br />
    reporting marks which are allowed to consist of any alphabetic letter.<br />
    The AAR does not allow ampersands. ( & ) Likewise, STS does not allow<br />
    ampersands in reporting marks either as that character can cause<br />
    unpredicatable results.<br /><br />
    Click on the FIX REPORTING MARKS button to remove all ampersands.<br />
  <br />
    </div>

    <?php
      // pull in the utility files
      require 'open_db.php';

      // get a database connection
      $dbc = open_db();

      // was the fix it button clicked?
      if (isset($_GET['fix_it_btn']))
      {
        // remove all ampersands
        $sql = 'update cars set reporting_marks = replace(reporting_marks, "&", "")';
        mysqli_query($dbc, $sql);
        $rows_updated = mysqli_affected_rows($dbc);
        if ($rows_updated > 0)
        {
          print $rows_updated . ' reporting marks updated.<br />';
        }
      }

      // display the list of reporting marks that contain ampersands
      $sql = 'select reporting_marks from cars where locate("&", reporting_marks) order by reporting_marks';

      $rs = mysqli_query($dbc, $sql);
      
      // if we found some ampersands, list them
      if (mysqli_num_rows($rs) > 0)
      {
        print '<form action="fix_reporting_marks.php" method="get">';
        print '<input type="submit" id="fix_it_btn" name="fix_it_btn" value="FIX REPORTING MARKS"><br /><br />';
    
        print '<table id="car_table" name="car_table">';
        print '<thead>';
        print '<tr>';
        print '<th>Reporting Marks</th>';
        print '</tr>';
        print '</thead>';
        while ($row = mysqli_fetch_array($rs))
        {
          print '<tr><td style="text-align: center;">'. $row['reporting_marks'] . '</td></tr>';
        }
        print '</table>';
      
        print '</form>';
      }
      else
      {
        print '<br />No illegal characters found in reporting marks.';
      }
    ?>

</body>
</html>