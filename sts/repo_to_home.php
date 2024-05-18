<?php
  // this program creates car orders for all empty non-billed cars that are not at their home locations
  // in order to move them to their home locations
  
  // bring in the function files
  require 'open_db.php';
  require 'drop_down_list_functions.php';

  // open a database connection
  $dbc = open_db();

  // get the current operating session number, default to zero if the query returns nothing
  $sql = 'select setting_value from settings where setting_name = "session_nbr"';
  $rs = mysqli_query($dbc, $sql);
  if (mysqli_num_rows($rs) > 0)
  {
    $row = mysqli_fetch_row($rs);
    $session_number = $row[0];
  }
  else
  {
    $session_number = 0;
  }

  // get the last reposition waybill number generated, default to 1 if the query returns nothing
  $sql = 'select waybill_number from car_orders where waybill_number like "' . str_pad($session_number, 3, '0', STR_PAD_LEFT) .  '-E__" order by waybill_number desc limit 1';
  $rs = mysqli_query($dbc, $sql);
  if (mysqli_num_rows($rs) > 0)
  {
    $row = mysqli_fetch_row($rs);
    $waybill_counter = substr($row[0], -2, 2) + 1;
  }
  else
  {
    $waybill_counter = 1;
  }

  // build the sql query to pull in cars that have a status of "Empty-Available" and aren't billed
  $sql = 'select cars.id as id, 
                 cars.reporting_marks as reporting_marks,
                 cars.current_location_id as current_location_id,
                 cars.home_location as home_location_id
            from cars
           where status = "Empty"
             and not exists (select car_orders.car from car_orders where cars.id = car_orders.car)
             and cars.current_location_id != cars.home_location';

  $rs = mysqli_query($dbc, $sql);

  // initialize a car counter
  $row_count = 0;
  
  // go through each of the cars from the previous query and insert an empty car waybill into the waybills table
  while($row = mysqli_fetch_array($rs))
  {
    // construct the waybill number
    $wb_nbr = str_pad($session_number, 3, '0', STR_PAD_LEFT) . '-E' . str_pad($waybill_counter, 2, '0', STR_PAD_LEFT);

    // build an sql query to create the empty car waybill
    $sql = 'insert into car_orders values ("' . $wb_nbr . '", "' . $row['home_location_id'] . '", "' . $row['id'] . '")';

    if (!mysqli_query($dbc, $sql))
    {
      print 'Insert Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
    }

    // build an sql query to update the car's status to "Ordered"
    $sql = 'update cars set status = "Ordered" where id = "' . $row['id'] . '"';

    if (!mysqli_query($dbc, $sql))
    {
      print 'Update Error: ' . mysqli_error($dbc) . ' SQL: ' . $sql;
    }
    $row_count++;
    $waybill_counter++;
  }
  // return the number of cars billed to their home locations
  print $row_count;

?>