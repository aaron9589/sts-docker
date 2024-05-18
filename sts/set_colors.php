<?php
// return foreground and background colors based on the location name
function set_colors($dbc, $location_name)
{
  // build a query to get the color for this location
  $sql = 'select color from locations where code = "' . $location_name . '"';
  $rs = mysqli_query($dbc, $sql);
  $row = mysqli_fetch_row($rs);
// print 'SQL: ' . $sql . '<br /><br />';
  if ($row[0] == 'None')
  {
    $background_color = 'white';
  }
  else
  {
    $background_color = $row[0];
  }
  
  // construct the style string
  $color_string = 'background-color: ' . $background_color . '; ';
  
  // determine the best foreground color
  switch ($background_color)
  {
    case 'None': $foreground_color = 'white'; break;
    case 'pink': $foreground_color = 'black'; break;
    case 'red': $foreground_color = 'white'; break;
    case 'orange': $foreground_color = 'black'; break;
    case 'yellow': $foreground_color = 'black'; break;
    case 'green': $foreground_color = 'white'; break;
    case 'lightblue': $foreground_color = 'black'; break;
    case 'mediumblue': $foreground_color = 'white'; break;
    case 'purple': $foreground_color = 'white'; break;
    case 'lightgrey': $foreground_color = 'black'; break;
    case 'black': $foreground_color = 'white'; break;
    default: $foreground_color = 'black';
  }
  
  $color_string .= 'color: ' . $foreground_color . ';';
// print 'color string: ' . $color_string . '<br /><br />';
  return $color_string;
}
?>
