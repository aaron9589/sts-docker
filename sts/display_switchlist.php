<html>
  <head>
    <title>STS - Switchlist</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      function activate_button()
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
<h3>Switchlist</h3>
    <!-- this form generates another page that is modified for better printing  -->
    <!-- the user needs to use the browser's back button to return to this page -->
    <!-- or user the link displayed at the bottom of the last page              -->
    <form action="printable_switchlist.php" method="get"> 
    Select job and display/print format and then click the <b>DISPLAY</b> button.<br />
    A new page will be displayed that is formatted for printing.<br />
    Use the browser's <b>BACK</b> button to return to this page<br />
    or click on the link that is displayed at the bottom of the last page.<br /><br />

    <?php
      // bring in the utility files
      require 'drop_down_list_functions.php';
      require 'open_db.php';

      // generate a drop-down list of jobs and the submit button
      print drop_down_jobs('job_name', '', 'activate_button()');
      print '<br /><br />';
      print '<input type="radio" name="format" id="mobile" value="mobile" checked> Mobile<br />';
      print '<input type="radio" name="format" id="half"  value="half"> Half Sheet<br />';
      print '<input type="radio" name="format" id="full"  value="full"> Full Sheet<br />';
      print '<input type="radio" name="format" id="dmp"  value="dmp"> Dot Matrix<br />';
      print '<input type="radio" name="format" id="wo"  value="wo"> Work Order<br /><br />';
      print '&nbsp;<input id="display_btn" name="display_btn" value="DISPLAY" type="submit" disabled><br /><br >';
    ?>
    </form>
  
</body>
</html>
