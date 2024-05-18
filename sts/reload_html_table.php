<?php
  // this javascript routine sends an sql query through to the database and returns the results
  // as the guts of an HTML table
  //
  // include or require this php file
  //
  // incoming parameters is a valid sql query
  //
  print '<script>
         function get_table_guts(sql_query)
         {
           var xmlhttp;
           if (sql_query == "")
           {
             document.getElementById(table_name).innerHTML = "";
             return;
           }  
           xmlhttp = new XMLHttpRequest();
           xmlhttp.onreadystatechange = function()
             {
               if (this.readyState == 4 && this.status == 200)
               {
                 document.getElementById(table_name).innerHTML = this.responseText;
               }
             }; 
           xmlhttp.open("GET", "get_table_guts.php?sql_query="+sql_query, true);
           xmlhttp.send();
         }
       </script>';
?>
