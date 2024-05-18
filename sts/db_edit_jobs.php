<?php
  // edit_jobs.php

  // edits the selected row in the jobs table if the Update button was clicked
  // it replaces only those items where a new value was submitted

  // it also edits the rows in the table associated with this job
  // rows can be added and updated; to remove a row it's index value is set to zerio

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Jobs";</script>';
  
  // generate a javascript line to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Job Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=jobs";</script>';

  // get a database connection
  $dbc = open_db();

  // initiate a database response message
  $sql_msg = '<br />Transaction completed';

  // if a previous update was successful and the job's name was changed, rename it's table using the new name
  if (isset($_GET['prev_obj_name']) && ($_GET['prev_obj_name'] != $_GET['obj_name']))
  {
	  $sql = 'rename table `' . $_GET['prev_obj_name'] . '` to `' . $_GET['obj_name']. '`';
	  if (!mysqli_query($dbc, $sql))
	  {
      $sql_msg =  '<br />Rename Error: ' . mysqli_error($dbc);
	  }
  }

  // has the submit button been clicked?
  if (isset($_GET['update_btn']))
  {
    // is this a remove operation?
    if ($_GET['update_remove_btn'] == 'remove')
    {
      // build a query to remove the selected job
      $sql = 'delete from jobs where name = "' . $_GET['obj_name'] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg =  '<br />Delete Error: ' . mysqli_error($dbc);
        print $sql_msg;
      }
      else
      {
        // if the delete was successful, delete any pickup criteria and then remove this job's step table
        $sql = 'delete from pu_criteria where job_id = "' . $_GET['obj_name'] . '"';
        mysqli_query($dbc, $sql); // don't need to check the result of this query since there might not be any records to remove
        
        $sql = 'drop table `' . $_GET['obj_name'] . '`';
        if (!mysqli_query($dbc, $sql))
        {
          $sql_msg = '<br />Drop Error: ' . mysqli_error($dbc);
          print $sql_msg;
        }
        else
        {
        // if the drop was successful, return to the list_jobs page
          header('Location: db_list.php?tbl_name=jobs');
          exit();
        }
      }
    }
    else
    {
      // this must be an update operation
      // build the update query based on the contents of the input text boxes
      // first check the job name and description
      $sql = 'update jobs set ';
      $first_field = true;

      // check each incoming input text box and add it to the update if there's something there
      if (strlen($_GET['obj_name']) > 0)
      {
        $sql .= 'name = "' . $_GET['obj_name'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['description']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'description = "' . $_GET['description'] . '" ';
        $first_field = false;
      }

      // run the update query if at least one field is to be updated
      if (!$first_field)
      {
        $sql .= 'where name = "' . $_GET['prev_obj_name'] . '"';
        if (mysqli_query($dbc, $sql))
        {
          $sql_msg =  '<br />Transaction completed';
        }
        else
        {
          $sql_msg =  '<br />Update Error: ' . mysqli_error($dbc);
        }
      }
	  
      // next, check the step table
      if ($_GET["row_count"] > 0)
      {
        // first, delete all of this job's step from it's step table
        // then go through each of the existing steps coming in from the web page
        // insert any step that doesn't have a sequence number of zero
        // including anything in the input text boxes

        // delete the existing steps
        $sql = 'delete from `' . $_GET['obj_name'] . '`';
        if (!mysqli_query($dbc, $sql))
        {
          $sql_msg = 'Delete error: ' . mysqli_error($dbc);
        }
        else
        {
          // loop through the existing steps on the web page and put them back into this job's step table
          for ($i=0; $i<$_GET["row_count"]; $i++)
          {
            // construct the names of each of the input fields in this table row
            $step_nbr = 'step' . $i;
            $station_nbr = 'station' . $i;
            $pickup_nbr = 'pickup' . $i;
            $setout_nbr = 'setout' . $i;
            $step_rmks_nbr = 'step_remarks' . $i;

            // if the step sequence number is 0, skip it (this deletes it from the list of steps)
           if ($_GET[$step_nbr] > 0)
           {
              // build the sql insert command
              $sql = 'insert into `' . $_GET['obj_name'] . '` ';
              $sql .= 'values (' . $_GET[$step_nbr] . ', ';
              $sql .= '"' . $_GET[$station_nbr] . '", ';
              if (isset($_GET[$pickup_nbr]))
              {
                $sql .= '"T", ';
              }
              else
              {
                $sql .= '"F", ';
              }
              if (isset($_GET[$setout_nbr]))
              {
                $sql .= '"T", ';
              }
              else
              {
                $sql .= '"F", ';
              }
              $sql .= '"' . $_GET[$step_rmks_nbr] . '")';
  
              if (!mysqli_query($dbc, $sql))
              {
                $sql_msg = 'Step Insert Error: ' . mysqli_error($dbc);
              }
            }
          }
        }
      }
      // finally check the input text boxes
      if (strlen($_GET['seq_nbr']) > 0)
      {
        $sql = 'insert into `' . $_GET['obj_name'] . '` ';
        $sql .= 'values ("' . $_GET['seq_nbr'] . '", ';
        $sql .= '"' . $_GET['station'] . '", ';

        if (isset($_GET['pickup']))
        {
          $sql .= '"T", ';
        }
        else
        {
          $sql .= '"F", ';
        }

        if (isset($_GET['setout']))
        {
          $sql .= '"T", ';
        }
        else
        {
          $sql .= '"F", ';
        }

        $sql .= '"' . $_GET['step_remarks'] . '")';

        if (!mysqli_query($dbc, $sql))
        {
          $sql_msg = 'Insert error: ' . mysqli_error($dbc);
        }
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="jobs" type="hidden">';

  // generate a hidden field to send this form's previous object name to itself when it's refreshed
  print '<input id="prev_obj_name" name="prev_obj_name" value="' . $_GET['obj_name'] . '" type="hidden">';

  // query the database for the properties of the selected job  and display them in a table
  $sql = 'select id, name, description from jobs where id = "' . $_GET['obj_id'] . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);

  // generate a hidden field so send this form's previous object ID to itself when it's refreshed
  print '<input id="obj_id" name="obj_id" value="' . $row['id'] . '" type="hidden">';
  
  print
    '<table>
      <tr>
        <th>Property</th>
        <th>Value</th>
      </tr>
      <tr>
        <td>Job Name</td>
        <td><input id="obj_name" name="obj_name" type="text" value="' . $row['name'] . '" tabindex="1" autofocus></td>
      </tr>
      <tr>
        <td>Description</td>
        <td><textarea name="description" rows="8" cols="128" tabindex="2">' . $row['description'] . '</textarea></td>
      </tr>
    </table>';

  // query the database for the steps in this job's step table
  
  $sql = 'select `' . $_GET['obj_name'] . '`.step_number as step_number,
                 `' . $_GET['obj_name'] . '`.station as station_id,
				 `' . $_GET['obj_name'] . '`.pickup as pickup,
				 `' . $_GET['obj_name'] . '`.setout as setout,
				 `' . $_GET['obj_name'] . '`.remarks as remarks,
				 routing.station as station
			from `' . $_GET['obj_name'] . '`, routing
            where `' . $_GET['obj_name'] . '`.station = routing.id
			order by step_number';

  $rs = mysqli_query($dbc, $sql);

  // save the row count in a hidden field for use later on
  print '<input id="row_count" name="row_count" value="' . mysqli_num_rows($rs) . '" type="hidden">';

  print '<br /><b>Job Steps</b><br /><br />';
  print 'To add a step, enter the appropriate information and click the UPDATE button.<br />';
  print 'To remove a step, set it\'s sequence number to 0 [Zero] and click the UPDATE button.<br /><br />';
  print 'The <b>+</b> symbol to the right of a station name indicates that the step has auto-assign pickup criteria<br />';
  print 'To add, modify or remove a step\'s auto-assign pickup criteria, click on it\'s station link<br /><br />';

  // generate another submit button
  print '<input id="update_btn" name="update_btn" value="UPDATE" type="submit"><br /><br />';

  // generate a table of this job's steps
  print '<table>
           <tr>
             <th>Sequence<br />Number</th>
             <th>Station</th>
             <th>Set Out</th>
             <th>Pick Up</th>
             <th>Remarks</th>
           </tr>
           <tr>
           </tr>
             <td><input id="seq_nbr" name="seq_nbr" type="text" size="5" tabindex="3"></td>
             <td>' . drop_down_stations("station", "4", "") . '</td>
             <td style="text-align: center"><input id="setout" name="setout" value="T" type="checkbox" checked tabindex="5"></td>
             <td style="text-align: center"><input id="pickup" name="pickup" value="T" type="checkbox" checked tabindex="6"></td>
             <td><input id="step_remarks" name="step_remarks" type="text" size="64" tabindex="7"></td>
           </tr>';

  // keep track of how many rows are on in the step table on the web page
  $row_count=0;
  $tab_count=8;

  // build a new table of step
  while ($row = mysqli_fetch_array($rs))
  {
    // set up the tab index for each field in each row
    $tabindex1 = $tab_count;
    $tabindex2 = $tab_count + 1;
    $tabindex3 = $tab_count + 2;
    $tabindex4 = $tab_count + 3;
    
    // check to see if this step has auto-assign pick up criteria
    $sql2 = 'select id from pu_criteria where job_id = "' . $_GET['obj_name'] . '" and step_nbr = "' . $row['step_number'] . '"';
//print 'SQL: ' . $sql2 . '<br /><br >';
    $result = mysqli_query($dbc, $sql2);
//print 'Num Rows: ' . mysqli_num_rows($result) . '<br /><br />';
    if (mysqli_num_rows($result) > 0)
    {
      $pickup_flag = '&nbsp;&nbsp;<b>+</b>';
    }
    else
    {
      $pickup_flag = '';
    }
    
    print '<tr>';
    print '<td><input name="step' . $row_count . '" type="text" value="' . $row['step_number'] . '" size="5" tabindex="' . $tabindex1 . '"></td>';

    print '<td>
             <a href="pu_criteria.php?job_id=' . $_GET['obj_id'] . '&job_name=' . $_GET['obj_name'] . '&step_nbr=' . $row['step_number'] .
             '&setout=' . $row['setout'] . '&pickup=' . $row['pickup'] . '&station_id=' . $row['station'] . '">
             ' . $row['station'] . $pickup_flag . '</a>
             <input name="station' . $row_count . '" type="hidden" value="' . $row['station_id'] . '">
           </td>';
          
    print '<td style="text-align: center">';
    if ($row['setout'] == "T")
    {
      print '<input name="setout' . $row_count . '" value="T" type="checkbox" checked tabindex="' . $tabindex2 . '">';
    }
    else
    {
      print '<input name="setout' . $row_count . '" value="F" type="checkbox" tabindex="' . $tabindex2 . '">';
    }
    print '</td>';
    print '<td style="text-align: center">';
    if ($row['pickup'] == "T")
    {
      print '<input name="pickup' . $row_count . '" value="T" type="checkbox" checked tabindex="' . $tabindex3 . '">';
    }
    else
    {
      print '<input name="pickup' . $row_count . '" value="F" type="checkbox" tabindex="' . $tabindex3 . '">';
    }
    print '</td>';
    print '<td><input name="step_remarks' . $row_count . '" type="text" value="' . $row['remarks'] . '" size="80" tabindex="' . $tabindex4 . '"></td>';
    print '</tr>';
    $row_count++;
    $tab_count = $tab_count + 4;
  }
  print '</table>';

  // generate a javascript line to set focus on the first input text box
  print '<script>document.getElementById("obj_name").focus();</script>';

  // display a status message
  print $sql_msg;

?>
