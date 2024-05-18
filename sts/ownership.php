<html>
  <head>
    <title>STS Club Ops - Ownership Management</title>
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
  </head>
  <body>
<p><img src="ImageStore/GUI/Menu/club_operations.jpg" width="716" height="147" border="0" usemap="#Map4">
  <map name="Map4">
    <area shape="rect" coords="570,3,709,49" href="index.html">
    <area shape="rect" coords="567,94,709,141" href="index-t.html">
    <area shape="rect" coords="569,53,710,93" href="club_index.html">
  </map>
</p>
<h2>Fleet Management</h2>
<h3>Rolling Stock Ownership</h3>
<?php
      // connect to the database using the credentials from sts04
      require 'open_db.php';
      $dbc = open_db();
      
      // if we got here via a click on the Submit button, update the ownership table based on what's in the owner dropdown list for each car
      if (isset($_POST['submit']))
      {
        // create a temporary table to hold the updated info
        $sql = 'create table if not exists temp_ownership (car_id int, owner_id int, on_off_rr varchar(256))';
        if (!$rtncd = mysqli_query($dbc, $sql))
        {
          print "Error creating temporary ownership table";
        }
        
        // dump the list of car id's, owner id's, and stored status values into the temporary table
        for ($i = 0; $i < $_POST['row_count']; $i++)
        {
          // check for empty values in the drop-down lists
          if (empty($_POST['select' . $i]))
          {
            $selected_owner = 'NULL';
          }
          else
          {
            $selected_owner = $_POST['select' . $i];
          }
          
          // check for empty values in the on_off_rr column
          if (empty($_POST['on_off_rr' . $i]))
          {
            $on_off_rr = 'on';
          }
          else
          {
            $on_off_rr = $_POST['on_off_rr' . $i];
          }
          
          $sql = 'insert into temp_ownership (car_id, owner_id, on_off_rr) values (' . $_POST['car_id' . $i] . ', ' . $selected_owner . ', "' . $on_off_rr . '")';
          if (!$rtncd = mysqli_query($dbc, $sql))
          {
            print 'Unable to insert record into temporary table';
            print $sql; die();
          }
        }

        // rename the original ownership table
        $sql = 'rename table ownership to orig_ownership';
        if (!$rtncd = mysqli_query($dbc, $sql))
        {
          print 'Unable to rename original ownership table';
        }
        
        // rename the temporary ownership table
        $sql = 'rename table temp_ownership to ownership';
        if (!$rtncd = mysqli_query($dbc, $sql))
        {
          print 'Unable to rename temporary ownership table';
        }
        
        // remove the original ownership table
        $sql = 'drop table orig_ownership';
        if (!$rtncd = mysqli_query($dbc, $sql))
        {
          print 'Unable to drop original ownership table';
        }
      }
      
      // pull in all owners for use later
      $owners = array();
      $owner_count = 0;
      $sql = 'select id, name from owners order by name';
      $rs = mysqli_query($dbc, $sql);
      while ($row = mysqli_fetch_array($rs))
      {
        $owners[$owner_count][0] = $row[0];
        $owners[$owner_count][1] = $row[1];
        $owner_count++;
      }
      
      // start a form for updating ownership information
      print '<form name=get_owner method="post" action="ownership.php">';
      
      // put a submit button on both the top and bottom of the page
      print '<input type="submit" id="submit" name="submit" value="UPDATE">';
      print '<br /><br />';
      
      // set up the table for the list of cars and owners
      print '<table>
               <tr>
                 <th>Car Reporting Marks</th>
                 <th>Owner</th>
               </tr>';
      
      // display all of the cars
      $sql = 'select cars.reporting_marks as reporting_marks,
                     cars.id as car_id,
                     owners.name as owners_name,
                     owners.id as owners_id,
                     ownership.on_off_rr as on_off_rr
                from cars
                left join ownership on ownership.car_id = cars.id
                left join owners on owners.id = ownership.owner_id
                order by cars.reporting_marks';


      if (!$rs = mysqli_query($dbc, $sql))
      {
        print 'Unable to query cars, owners, and ownership tables';
      }
      
      // display the cars and their owners in a table
      $row_count = 0;
      while ($row = mysqli_fetch_array($rs))
      {
        if (file_exists('./ImageStore/DB_Images/RollingStock/' . $row['car_id'] . '.jpg'))
        {
          $parm_string = '\'' . $row['car_id'] . '\', \'' . $row['reporting_marks'] . '\'';
        }
        else
        {
          $parm_string = '\'\',\'' . $row['reporting_marks'] . '\'';
        }
        print '<tr>';
        print '  <td onclick="show_image(' . $parm_string . ');">' . $row['reporting_marks'] . '<input type="hidden" id="car_id' . $row_count . '" name="car_id' . $row_count . '" value="' . $row['car_id'] . '"></td>';
        print '  <td>';
        print '    <select id="select' . $row_count . '" name="select' . $row_count . '">';
        print '      <option value=""></option>';
        for ($i = 0; $i < $owner_count; $i++)
        {
          if ($owners[$i][0] == $row['owners_id'])
          {
            print '  <option value="' . $owners[$i][0] . '" selected>' . $owners[$i][1] . '</option>';
          }
          else
          {
            print '  <option value="' . $owners[$i][0] . '">' . $owners[$i][1] . '</option>';
          }
        }
        print '    </select>';
        print '    <input type="hidden" id ="on_off_rr' . $row_count . '" name="on_off_rr' . $row_count . '" value="' . $row['on_off_rr'] . '">';
        print '  </td>';
        print '</tr>';
        $row_count++;
      }
      print '</table>';
      
      // save the row count in a hidden field for the next time through
      print '<input type="hidden" id = "row_count" name="row_count" value="' . $row_count . '">';

      // put a submit button on both the top and bottom of the page
      print '<br />';
      print '<input type="submit" id="submit" name="submit" value="UPDATE">';
      print '</form>';
      
    ?>
<!-- <a href="index.html"><img src="../sts/ImageStore/Menu/Club-OPS-operations-sm.jpg" width="109" height="30" border="0"></a> -->
</body>
</html>