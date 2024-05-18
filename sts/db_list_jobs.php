<?php
  // list_jobs.php

  // adds a new job to the jobs table if the Update button was
  // clicked and if there is a code in the job name text box.
  // also creates a table using the name of the job that will contain the job's steps

  // generate some javascript to display the table name, identify this table to the form, and set the update button's tab index
  print '<script>
           document.getElementById("table_name").innerHTML = "Jobs";
           document.getElementById("tbl_name").value = "jobs";
           document.getElementById("update_btn").tabIndex = "3";
         </script>';

  // get a database connection
  $dbc = open_db();

  // initialize an sql message
  $sql_msg = '<br />Transaction Completed';

  // has the submit button been clicked?
  if (isset($_POST['update_btn']))
  {
    // yes, so check to see if there is anything in the input text boxes
    if (strlen($_POST['name']) > 0)
    {
      // add the new job to the car code table
      $sql = 'insert into jobs (name, description) values ("' . $_POST['name'] . '", "' . $_POST['description'] . '")';
      $rs = mysqli_query($dbc, $sql);

      // create the new job step table
      $sql = 'create table `' . $_POST['name'] . '` ';
      $sql .='(step_number int primary key, station int, pickup char(1), setout char(1), remarks varchar(256))';

      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = 'SQL error: ' . mysqli_error($dbc) . ' [' . $sql . ']';
      }
    }
  }

  // query the database for all of the jobs and display them in a table
  $sql = 'select id, name, description from jobs order by name';
  $rs = mysqli_query($dbc, $sql);

  print '<table>
           <caption style="font: bold 15px Verdana, Arial, sans-serif; text-align:left;">Add New Job/Train</caption>
           <thead>
             <tr>
                <th>Job Name</th><th>Description</th>
             </tr>           
             <tr>
               <td><input id="name" name="name" type="text" tabindex="1"required></td>
               <td><textarea name="description" rows="8" cols="128" tabindex="2"></textarea></td>
             </tr>
             <tr>
               <td colspan="2" style="border:0px;">
             </tr>
             <tr style="position: sticky; top: 0; background-color: #F5F5F5">
                <th>Job Name</th><th>Description</th>
             </tr>
           </thead>';

  if (mysqli_num_rows($rs) > 0)
  {
    while ($row = mysqli_fetch_array($rs))
    {
      print '<tr>
               <td><a href="db_edit.php?tbl_name=jobs&obj_id=' . $row['id'] . '&obj_name=' . urlencode($row['name']) . '">' . $row['name'] . '</td>
               <td>' . nl2br($row['description']) . '</td>
             </tr>';
    }
  }
  print "</table>";

  // display a database status message
  print $sql_msg;

  // generate a javascript line to set focus on the first text box
  print '<script>document.getElementById("name").focus();</script>';

?>
