<html>
  <head>
    <title>STS - Club Ops - Owner Management</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top;}
      th {border: 1px solid black; padding: 10px;}
      td {border: 1px solid black; padding: 10px;}
      td.checkbox {text-align: center;}
    </style>
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
<h3>Rolling Stock Owners</h3>
<?php
      // connect to the database using the credentials from sts04
      require 'open_db.php';
      $dbc = open_db();
   
      // if we came into this form because the Submit button was pressed, update the database
      // otherwise just list the contents of the table
      
      if (isset($_POST['submit']))
      {
        // if there is something in the owner's name text box, insert it into the owners table
        if (isset($_POST['new_name']) and (strlen(trim($_POST['new_name'])) > 0))
        {
          $sql = 'insert into owners (name, remarks) values ("' . $_POST['new_name'] . '", "' . $_POST['new_rmks'] . '")';
          if (!$rtncd = mysqli_query($dbc, $sql))
          {
            print 'Unable to insert new record into owners table';
            print '<br />SQL: ' . $sql;
          }
        }
        
        // run through all of the table rows and either delete the row from the database if it's checkbox is checked
        // or update the row with the contents of the two text boxes
        $row_count = $_POST['row_count'];
        for ($i = 0; $i < $row_count; $i++)
        {
          // look to see if the row's delete checkbox is checked
          if (isset($_POST['delete' . $i]))
          {
            // if so, delete the row from the owners table
            $sql = 'delete from owners where id = ' . $_POST['owner' . $i];
            if (!$rtncd = mysqli_query($dbc, $sql))
            {
              print 'Unable to delete row ' . $i . ' from owners table';
            }
          }
          else
          {
            // otherwise update the name and remarks fields
            $sql = 'update owners set name = "' . $_POST['name' . $i] . '", remarks = "' . $_POST['rmks' . $i] . '" where id = ' . $_POST['owner' . $i];
            if (!$rtncd = mysqli_query($dbc, $sql))
            {
              print 'Unable to update row ' . $i . ' in owners table';
            }
          }
        }
      }

      // pull the contents of the owners table in alphabetical order
      $sql = 'select id, name, remarks from owners order by name';
      $rs = mysqli_query($dbc, $sql);
        
      // set up a form
      print '<form name="owners" method="post" action="owners.php">';
        
      // put a submit button on both the top and bottom of the list
      print '<input type="submit" id="submit01" name="submit" value="UPDATE">';
      print '<br /><br />';
        
      // use the list of owners to built a table with each name and remarks string already in a text box
      print '<table>
               <tr>
                 <th>Owner Name</th>
                 <th>Remarks (Contact info, etc...)</th>
                 <th></th>
               </tr>';

      // the first row will be for adding a new owners
      print '<tr>
               <td>
                 <input type="text" id="new_name" name="new_name">
               </td>
               <td>
                 <input type="text" id="new_rmks" name="new_rmks" size="80">
               </td>
               <td>
                 NEW
               </td>
             </tr>';
      
      // get ready to count how many rows are on the screen
      $row_count = 0;
      
      // if the table is empty, say so
      if (mysqli_num_rows($rs) == 0)
      {
        print '<tr><td colspan="2"><br />There are no owners in the database<br /><br /></td></tr>';
      }
      else
      {
      // list the existing owners
        while ($row = mysqli_fetch_array($rs))
        {
          print '<tr>
                   <td>
                     <input type="text" id="name' . $row_count . '" name="name' . $row_count . '" value="' . $row[1] . '">
                   </td>
                   <td>
                     <input type="text" id="rmks' . $row_count . '" name="rmks' . $row_count . '" value="' . $row[2] . '" size="80">
                   </td>
                   <td>
                     <input type="checkbox" id="delete' . $row_count . '"name="delete' . $row_count . '"> Delete
                     <input type="hidden" id="owner' . $row_count . '"name="owner' . $row_count . '" value="' . $row[0] . '">
                   </td>
                 </tr>';
          $row_count++;
        }
      }
      print '</table>';
      // store the row count in a hidden variable for the next time this form is called
      print '<input type="hidden" id="row_count" name="row_count" value="' . $row_count . '">';
        
      // put a submit button on both the top and bottom of the list
      print '<br />';
      print '<input type="submit" id="submit02" name="submit" value="UPDATE">';
      
      print '</form>';
        
    ?>
<!-- <a href="index.html"><img src="../sts/ImageStore/Menu/Club-OPS-operations-sm.jpg" width="109" height="30" border="0"></a> -->
</body>
</html>