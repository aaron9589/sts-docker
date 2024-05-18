<?php
  // edit_cars.php

  // edits the selected row in the car table if the Update button was clicked
  // it replaces only those items where a new value was submitted

  // generate a javascript line to display the table name
  print '<script>document.getElementById("table_name").innerHTML = "Cars";</script>';

  // generate a javascript line to set the proper return link and text
  print '<script>document.getElementById("return_link").innerHTML = "Return to Car Management page";</script>';
  print '<script>document.getElementById("return_link").href = "db_list.php?tbl_name=cars";</script>';
//print 'CWD: ' . getcwd() . '<br /><br />';
  // generate a QR code for this car
  require '../phpqrcode/qrlib.php';
  QRcode::png($_GET['obj_id'], './ImageStore/DB_Images/qrcodes/' . str_replace(" ", "", $_GET['obj_id']) . '.png', 'M', 1.5, 1);

  // pull in the bar code library
  require '../php-barcode/php-barcode.php';

  // set the parameters for the bar code
  $image_height = 30;
  $image_width = 300;
  $x = $image_width/2;  // barcode center
  $y = $image_height/2; // barcode center
  $bar_width = 2;       // barcode height in 1D ; not use in 2D
  $bar_height = 20;     // barcode height in 1D ; module size in 2D
  $angle = 0;           // rotation in degrees
  $type = 'code39';     // type of bar code

  // create the basic image and fill it with white
  $im = imagecreatetruecolor($image_width, $image_height);
  $black = ImageColorAllocate($im,0x00,0x00,0x00);
  $white = ImageColorAllocate($im,0xff,0xff,0xff);
  imagefilledrectangle($im, 0, 0, $image_width, $image_height, $white);

  // get a database connection
  $dbc = open_db();

  // get the internal ID for this car
  $sql = 'select id from cars where reporting_marks = "' . $_GET['obj_id'] . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);
  
  // add the bar code to the basic image
  $bar_code = '-' . $row['id'] . '-';
  $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array("code"=>$bar_code), $bar_width, $bar_height);
  $bar_file_name = './ImageStore/DB_Images/barcodes/' . str_replace(" ", "", $_GET['obj_id']) . '.png';
  imagepng($im, $bar_file_name);
  imagedestroy($im);

  // initiate a database response message
  $sql_msg = '<br />Transaction completed';

  // has the submit button been clicked?
  if (isset($_GET['update_btn']))
  {
    // is this a remove operation?
    if ($_GET['update_remove_btn'] == 'remove')
    {
      // build a query to remove the selected car
      $sql = 'delete from cars where id = "' . $_GET['obj_name'] . '"';
      if (!mysqli_query($dbc, $sql))
      {
        $sql_msg = '<br />Delete Error: ' . mysqli_error($dbc);
      }
      else
      {
        // if the delete was successful, return to the list_cars page
        header('Location: db_list.php?tbl_name=cars');
        exit();
      }
    }
    else
    {
      // this must be an update operation
      // initialize the unavailable car and the first field flags
      $unavailable_car = false;
      $first_field = true;

      // build the update query based on the contents of the input text boxes
      $sql = 'update cars set ';

      // check each incoming input text box and add it to the update if there's something there
      if (strlen($_GET['reporting_marks']) > 0)
      {
        $sql .= 'reporting_marks = "' . $_GET['reporting_marks'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['car_code']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'car_code_id = "' . $_GET['car_code'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['current_location']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'current_location_id = "' . $_GET['current_location'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['position']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'position = "' . $_GET['position'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['status']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'status = "' . $_GET['status'] . '" ';
        $first_field = false;
        
        // check to see if this car is now either empty or unavailable (these are the only two possibilities)
        if (($_GET['status'] == 'Unavailable') || ($_GET['status'] == 'Empty'))
        {
          $unavailable_car = true;
        }
        else
        {
          $unavailable_car = false;
        }
      }

      if (strlen($_GET['handled_by']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'handled_by_job_id = "' . $_GET['handled_by'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['remarks']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'remarks = "' . $_GET['remarks'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['home_location']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'home_location = "' . $_GET['home_location'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['load_count']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'load_count = "' . $_GET['load_count'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['RFID_code']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'RFID_code = "' . $_GET['RFID_code'] . '" ';
        $first_field = false;
      }

      if (strlen($_GET['last_spotted']) > 0)
      {
        if (!$first_field)
        {
          $sql .= ', ';
        }
        $sql .= 'last_spotted = "' . $_GET['last_spotted'] . '" ';
        $first_field = false;
      }
      // run the update query if at least one field is to be updated
      if (!$first_field)
      {
        $sql .= 'where id = "' . urldecode($_GET['prev_obj_name']) . '"';
// print 'SQL: ' . $sql . '<br /><br />';
        if (mysqli_query($dbc, $sql))
        {
          $sql_msg =  '<br />Transaction completed<br /><br />';
          
          // if the update was successful and if this car is now unavailable or newly emptied, take it out of the train
          // and delete the car order to which it is connected
          if ($unavailable_car)
          {
            // take the car out of any train that it might have been in
            $sql = 'update cars set handled_by_job_id = 0 where id = "' . urldecode($_GET['prev_obj_name']) . '"';
// print 'SQL: ' . $sql . '<br /><br />';
            if (!mysqli_query($dbc, $sql))
            {
              $sql_msg = '<br />Update Error: ' . mysqli_error($dbc);
            }
            
            // delete any car order that is linked to this car
            $sql = 'delete from car_orders where car in (select id from cars where id = "' . urldecode($_GET['prev_obj_name']) . '")';
// print 'SQL: ' . $sql . '<br /><br />';
            if (!mysqli_query($dbc, $sql))
            {
              $sql_msg = '<br />Update Error: ' . mysqli_error($dbc);
            }
            
          }
        }
        else
        {
          $sql_msg =  '<br />Update Error: ' . mysqli_error($dbc);
        }
      }
    }
  }

  // generate a hidden field to send this form's table name to itself when it's refreshed
  print '<input id="tbl_name" name="tbl_name" value="cars" type="hidden">';

  // generate a hidden field to send this form's previous object name to itself when it's refreshed
  print '<input id="prev_obj_name" name="prev_obj_name" value="' . urlencode($_GET['obj_name']) . '" type="hidden">';

  // query the database for the properties of the selected  car code and display them in a table
  $sql = 'select cars.id as car_id,
                 cars.reporting_marks as reporting_marks,
                 cars.car_code_id as car_code_id,
				         cars.current_location_id as current_location_id,
				         cars.position as position,
                 cars.status as status,
				         cars.handled_by_job_id as handled_by_job_id,
				         cars.remarks as remarks,
				         cars.load_count as load_count,
                 cars.RFID_code as RFID_code,
                 cars.last_spotted as last_spotted,
				         car_codes.code as car_code,
				         loc01.code as current_location,
                 loc02.code as home_location,
				         jobs.name as job_name
          from cars
		      left join car_codes on cars.car_code_id = car_codes.Id
			    left join locations loc01 on cars.current_location_id = loc01.Id
          left join locations loc02 on cars.home_location = loc02.id
			    left join jobs on cars.handled_by_job_id = jobs.Id
          where cars.id = "' . $_GET['obj_name'] . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_array($rs);

  // if this car's current location is 0 (zero) that means it's in the assigned train
  if ($row['current_location_id'] > 0)
  {
    $current_location = $row['current_location'];
  }
  else
  {
    $current_location = "In Train";
  }
  
  // add big table so any graphical image will be on the right side of the page
  print '<table>
           <tr>
             <td>';
  print
    '<table>
      <tr>
        <th>Property</th>
        <th>Current Value</th>
        <th>New Value</th>
      </tr>
      <tr>
        <td>Reporting marks</td>
        <td>' . $row['reporting_marks'] . '</td>
        <td>
        <input id="reporting_marks" name="reporting_marks" type="text" tabindex="1" autofocus>
          <input id="obj_name" name="obj_name" type="hidden" value="' . $_GET['obj_name'] . '">
          <input id="obj_id" name="obj_id" type="hidden" value="' . $_GET['obj_id'] . '">
        </td>
      </tr>
      <tr>
        <td>Car Code</td>
        <td>' . $row['car_code'] . '</td>
        <td>' . drop_down_car_codes('car_code', 2, 'no_wild') . '</td>
      </tr>
      <tr>
        <td>Current Location</td>
        <td>' . $current_location . '</td>
        <td>' . drop_down_locations('current_location', 3, '') . '</td>
      </tr>
      <tr>
        <td>Position</td>
        <td>' . $row['position'] . '</td>
        <td><input name="position" type="text" tabindex="4"></td>
      </tr>
      <tr>
        <td>Status</td>
        <td>' . $row['status'] . '</td>
        <td><select id="status" name="status"><option value=""></option><option value="Empty">Empty</option><option value="Unavailable">Unavailable</option></select></td>
      </tr>
      <tr>
        <td>Handled By</td>
        <td>' . $row['job_name'] . '</td>
        <td>' . drop_down_jobs('handled_by', '6', '') . '</td>
      </tr>
      <tr>
        <td>Remarks</td>
        <td>' . $row['remarks'] . '</td>
        <td><input name="remarks" type="text" tabindex="7"></td>
      </tr>
      <tr>
        <td>Home Location</td>
        <td>' . $row['home_location'] . '</td>
        <td>' . drop_down_locations('home_location','8', '') . '</td>
      </tr>
      <tr>
        <td>Load Count</td>
        <td>' . $row['load_count'] . '</td>
        <td><input name="load_count" type="text" tabindex="9"></td>
      </tr>
      <tr>
        <td>RFID code</td>
        <td>' . $row['RFID_code'] . '</td>
        <td><input name="RFID_code" type="text" tabindex="10"></td>
      </tr>
      <tr>
        <td>QR Code</td>
        <td style="font: normal 10px Verdana, Arial, sans-serif; text-align: center;">
          <img src="./ImageStore/DB_Images/qrcodes/' . $_GET['obj_id'] . '.png" style="vertical-align: middle">&nbsp;&nbsp;' . $_GET['obj_id'] . '</td>
        <td></td>
      </tr>
      <tr>
        <td>Bar Code</td>
        <td style="font: normal 10px Verdana, Arial, sans-serif; text-align: center">
          <img src="' . $bar_file_name . '" style="vertical-align: middle"><br />' . $_GET['obj_id'] . '</td>
        <td></td>
      </tr>
      <tr>
        <td>Last Spotted</td>
        <td>' . $row['last_spotted'] . '</td>
        <td><input name="last_spotted" type="text" tabindex="11"></td>
      </tr>
    </table>';
 
  // close the left hand box and start the right hand box 
  print '</td>
         <td>';

  // image 1
  print '<div id="image1" name="image1">';
  
  // handle image 1 of this car
  $image_name1 = './ImageStore/DB_Images/RollingStock/' . $_GET['obj_name'] . '.jpg';

  print '<br />Rolling stock image or photo No. 1&nbsp;';
  if (file_exists($image_name1))
  {
    print '<input type="button" value="IMPORT" disabled>&nbsp;
           <a href="remove_img.php?obj_id=' . $_GET['obj_id'] . '&obj_name=' . $_GET['obj_name'] . '&image=1" target="_blank"><input type="button" value="REMOVE"></a><br /><br />';

    // display the image
    $filemtime = filemtime($image_name1);
    print '<img src="' . $image_name1 . '?' . $filemtime. '" style="width:640px;"><br /><br />';
  }
  else
  {
    print '<a href="import_img.php?obj_id=' . $_GET['obj_id'] . '&obj_name=' . $_GET['obj_name'] . '&image=1" target="_blank"><input type="button" value="IMPORT"></a>&nbsp;
           <input type="button" value="REMOVE" disabled><br /><br />';
           
    print 'No image available';
  }
  // end of top photo
  print '</div>';
  print '<br /><hr />';
  
  // image 2
  print '<div id="image2" name="image2">';
  
  // handle image 2 of this car
  $image_name2 = './ImageStore/DB_Images/RollingStock/' . $_GET['obj_name'] . 'b.jpg';

  print '<br />Rolling stock image or photo No. 2&nbsp;';
  if (file_exists($image_name2))
  {
    print '<input type="button" value="IMPORT" disabled>&nbsp;
           <a href="remove_img.php?obj_id=' . $_GET['obj_id'] . '&obj_name=' . $_GET['obj_name'] . '&image=2" target="_blank"><input type="button" value="REMOVE"></a><br /><br />';

    // display the image
    $filemtime = filemtime($image_name2);
    print '<img src="' . $image_name2 . '?' . $filemtime. '" style="width:640px;"><br /><br />';
  }
  else
  {
    print '<a href="import_img.php?obj_id=' . $_GET['obj_id'] . '&obj_name=' . $_GET['obj_name'] . '&image=2" target="_blank"><input type="button" value="IMPORT"></a>&nbsp;
           <input type="button" value="REMOVE" disabled><br /><br />';
           
    print 'No image available';
  }
  // end of top photo
  print '</div>';
  // close the right hand column
  print '</td>';
  
  // close the big box
  print '</tr>
       </table>';

  // display a status message
  print $sql_msg;

  // add a "None" option to the top of the home location drop down box
  print '<script>
           var select = document.getElementById("home_location");
           var option = document.createElement("option");
           option.text = "None";
           option.value = "0";
           select.add(option, select[1]);
         </script>';
?>
