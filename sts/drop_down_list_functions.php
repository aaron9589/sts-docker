<?php
  // this file contains functions that return the contents of various
  // tables to be used in building drop down lists

  ///////////////////////////////////////////////////////////////////////

  // car_codes
  function drop_down_car_codes($list_name, $tab_index, $wild_cards)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the car codes
    $sql = "select id, code from car_codes order by code";

    // retrieve the rows and put them into an html <select> block
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        // check the wild card setting - only add a car code to the list if
        // - wild cards are OK or
        // - wild cards aren't OK and the car code doesn't contain one anyway
        if (($wild_cards == "wild_ok") || (($wild_cards == "no_wild") && (!strpos($row['code'], "*"))))
        {
          $select_string .= '<option value="' . $row['id'] . '">' . $row['code'] . '</option>';
        }
      }
    }

    $select_string .= "</select>";
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // locations
  function drop_down_locations($list_name, $tab_index, $on_click)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the locations
    $sql = "select locations.id, locations.code, routing.station, routing.sort_seq 
              from locations, routing
             where locations.station = routing.id
          order by routing.sort_seq, routing.station, locations.code";

    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" onclick="' . $on_click . '" style="width: 100px;">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['station'] . ' - ' . $row['code'] . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // locations at station
  function drop_down_locations_at_station($station_id, $list_name, $tab_index, $on_click)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the locations
    $sql = 'select locations.id, locations.code
              from locations
             where locations.station = "' . $station_id . '"
          order by locations.code';
// print '<br />SQL: ' . $sql . '<br />';
    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" onclick="' . $on_click . '" style="width: 100px;">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['code'] . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // shipments
  function drop_down_shipments($list_name, $tab_index)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the locations
    $sql = 'select id, code from shipments order by code';

    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['code'] . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // shipments
  function drop_down_ship_desc($list_name, $tab_index)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the locations
    $sql = 'select id, code, description from shipments order by code';

    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['code'] . ' - ' . substr($row['description'], 0, 20) . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // status
  function drop_down_status($list_name, $tab_index)
  {
    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">
                        <option value=""></option>
                        <option value="Empty">Empty</option>
                        <option value="Ordered">Ordered</option>
                        <option value="Loading">Loading</option>
                        <option value="Loaded">Loaded</option>
                        <option value="Unloading">Unloading</option>
                        <option value="Unavailable">Unavailable</option>
                      </select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // stations
  function drop_down_stations($list_name, $tab_index, $on_click)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the stations
    $sql = "select id, station from routing order by sort_seq, station";

    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    if ((isset($on_click) && strlen($on_click) > 0))
    {
      // "onclick" won't work with touch screen devices
      // $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" onclick="' . $on_click . '">';
      $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" onchange="' . $on_click . '">';
    }
    else
    {
      $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    }

    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['station'] . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // empty cars at one of the locations prioritized for a specified shipment
  function drop_down_specified_cars($list_name, $shipment)
  {
    // get a database connection
    $dbc = open_db();

    // use the shipment ID to pull in the required car code
    $sql = 'select car_code from shipments where code = "' . $shipment . '"';
    $rs = mysqli_query($dbc, $sql);
    $row = mysqli_fetch_row($rs);
    $car_code = $row[0];

    // substitute % (SQL wild card) for any * in the car code
    $new_car_code = "";
    for ($i=0; $i<strlen($car_code); $i++)
    {
      if (substr($car_code, $i, 1) == '*')
      {
        $new_car_code = $new_car_code . '%';
      }
      else
      {
        $new_car_code = $new_car_code . substr($car_code, $i, 1);
      }
    }
    $car_code = $new_car_code;

    // find out if this shipment has prioritized empty car locations
    $sql = 'select count(0) from empty_locations where shipment = "' . $shipment . '"';
    $rs = mysqli_query($dbc, $sql);
    $row = mysqli_fetch_row($rs);

    if ($row[0] > 0)
    {
/*      // if so, build a query to pull in eligible cars at the specified locations
      $sql = 'select distinct cars.reporting_marks
              from cars, empty_locations
              where cars.status = "Empty"
              and cars.car_code like "' . $car_code . '"
              and cars.current_location like replace(empty_locations.location, "*", "%")
              and empty_locations.shipment = "' . $shipment . '"
              order by empty_locations.priority asc,  cars.load_count desc';
*/
      $sql = 'select cars.reporting_marks, 0 as pr, cars.load_count as lc
              from cars, empty_locations, shipments
              where cars.status = "Empty"
              and cars.car_code like "' . $car_code . '"
              and cars.current_location in
                  (select code from locations where locations.station in
                         (select station from locations, shipments
                          where locations.code = shipments.loading_location and shipments.code = "' . $shipment . '"))
              union
              select distinct cars.reporting_marks, empty_locations.priority as pr, cars.load_count as lc
              from cars, empty_locations
              where cars.status = "Empty"
              and cars.car_code like "' . $car_code . '"
              and cars.current_location like replace(empty_locations.location, "*", "%")
              and empty_locations.shipment = "' . $shipment . '"
              order by pr asc, lc asc';
    }
    else
    {
      // if not, build a query to pull in eligible cars from the entire system
      $sql = 'select cars.reporting_marks, 0 as pr, cars.load_count as lc
              from cars, empty_locations, shipments
              where cars.status = "Empty"
              and cars.car_code like "' . $car_code . '"
              and cars.current_location in
                  (select code from locations where locations.station in
                         (select station from locations, shipments
                          where locations.code = shipments.loading_location and shipments.code = "' . $shipment . '"))
              union
              select cars.reporting_marks, 9999 as pr, cars.load_count as lc
              from cars
              where cars.status = "Empty"
              and cars.car_code like "' . $car_code . '"
              order by pr asc, loc asc';
    }

    $rs = mysqli_query($dbc, $sql);

    if (mysqli_num_rows($rs) > 0)
    {
      $select_string = '<select id="' . $list_name . '" name="' . $list_name . '">';
      $select_string .= '<option value=""></option>';
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row[0] . '">' . $row[0] . '</option>';
      }
      $select_string .= '</select>';
      return $select_string;
    }
    else
    {
      return '<select id="' . $list_name . '" name="' . $list_name . '"><option value="">None Avail</option></select>';
    }
  }

  ///////////////////////////////////////////////////////////////////////

  // jobs
  function drop_down_jobs($list_name, $tab_index, $on_click)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in all of the jobs
    $sql = 'select id, name from jobs order by name';

    // retrieve the rows and put them into an array
    $rs = mysqli_query($dbc, $sql);

    if ((isset($on_click) && strlen($on_click) > 0))
    {
      // $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" onclick="' . $on_click . '">';
      $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" onchange="' . $on_click . '">';
    }
    else
    {
      $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    }
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
      }
    }

    $select_string .= '</select>';
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // car_orders
  function drop_down_car_orders($list_name, $tab_index)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in the filled car orders
    $sql = 'select car_orders.waybill_number as waybill_number
              from car_orders, cars
             where car_orders.car = cars.id
               and cars.current_location_id > 0
             order by waybill_number';

    // retrieve the rows and put them into an html <select> block
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row[0] . '">' . $row[0] . '</option>';
      }
    }
    else
    {
      $select_string = "None";
      return $select_string;
    }

    $select_string .= "</select>";
    return $select_string;
  }

  ///////////////////////////////////////////////////////////////////////

  // commodities
  function drop_down_commodities($list_name, $tab_index)
  {
    // get a database connection
    $dbc = open_db();

    // build the query to pull in the commodity information
    $sql = 'select id, code from commodities order by code';

    // retrieve the rows and put them into an html <select> block
    $rs = mysqli_query($dbc, $sql);

    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '" style="width: 100px;">';
    $select_string .= '<option value=""></option>';

    if (mysqli_num_rows($rs) > 0)
    {
      while ($row = mysqli_fetch_array($rs))
      {
        $select_string .= '<option value="' . $row['id'] . '">' . $row['code'] . '</option>';
      }
    }

    $select_string .= "</select>";
    return $select_string;
  }
  ///////////////////////////////////////////////////////////////////////

  // colors
  function drop_down_colors($list_name, $tab_index)
  {
    $select_string = '<select id="' . $list_name . '" name="' . $list_name . '" tabindex="' . $tab_index . '">
                        <option>None</option>
                        <option value="pink" style="color: black; background-color: pink;">Pink</option>
                        <option value="red" style="color: white; background-color: red;">Red</option>
                        <option value="orange" style="color: white; background-color: orange;">Orange</option>
                        <option value="yellow" style="color: black; background-color: yellow;">Yellow</option>
                        <option value="green" style="color: white; background-color: green;">Green</option>
                        <option value="lightblue" style="color: black; background-color: lightblue">Light Blue</option>
                        <option value="mediumblue" style="color: white; background-color: mediumblue;">Medium Blue</option>
                        <option value="purple" style="color: white; background-color: purple;">Purple</option>
                        <option value="lightgrey" style="color: black; background-color: lightgrey;">Grey</option>
                        <option value="black" style="color: white; background-color: black;">Black</option>
                      </select>';
    return $select_string;
  }
  
?>
