<html>
  <head>
    <title>STS - Remove Back-up</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      function enable_remove_btn()
      {
        document.getElementById("remove_btn").disabled = false;
      }
    </script>
  </head>
  <body>
  <p><img src="ImageStore/GUI/Menu/manage.jpg" width="718" height="146" border="0" usemap="#Map5">
    <map name="Map5">
      <area shape="rect" coords="569,4,708,48" href="index.html">
      <area shape="rect" coords="569,97,711,140" href="index-t.html">
      <area shape="rect" coords="569,52,709,91" href="database.html">
    </map>
  </p>
  <h2>Database Management</h2>
  <h3>Remove Backup Database</h3>
  <form action="remove_backup.php" method="get">
    Click on the radio button for the backup copy that you want to remove and then click the <b>REMOVE</b> button.<br /><br />
    <img src="ImageStore/GUI/warning.gif" width="110" height="30" align="absmiddle"> 
    After you have clicked on the <b>REMOVE</b> button there is no way to recover 
    the removed backup database!<br />
    <br />
    <div id="msg1"></div>
    <?php
      // has the Remove button been clicked?
      $msg2 = "";
      if (isset($_GET['remove_btn']))
      {
        // check to see if the user selected a backup file to be removed
        if (isset($_GET['remove_name']))
        {
          // check to see if a subdirectory of photos exists for this backup file
          $remove_name = $_GET['remove_name'];
          $remove_dir = './backups/' . $remove_name . '_photos';
          if (file_exists($remove_dir))
          {
            // get a list of file names in the directory to be removed
            $files = glob($remove_dir . '/*.*');
            
            // if there are files in there, delete them
            if (sizeof($files) > 0)
            {
              // remove the rollingstock photos
              print 'Removing rollingstock photos from the backup directory...<br /><br />';
              foreach($files as $file)
              {
                unlink($file);
              }
            }
            // delete the photo backup directory
            rmdir($remove_dir);
            print 'Photo backup directory ' . $remove_dir . ' removed...<br /><br />';
          }
          
          // delete the backup file if it exists
          if (file_exists('./backups/' . $remove_name))
          {
            unlink('./backups/' . $remove_name);
            print $remove_name . ' backup file removed.<br /><br />';
          }
        }
      }

      // get the list of names from the backup directory
      $backup_names = array_slice(scandir('./backups'), 2);

      if (count($backup_names) > 0)
      {
        // since there are backup copies, we can now display the remove button
        print '<input id="remove_btn" name="remove_btn" value="REMOVE" type="submit" onclick="display_msg1();" disabled><br /><br />';

        // build a table of backup names with radio buttons
        print '<table>';
        foreach($backup_names as $name)
        {
          // if the item in the directory list is a file, add it to the radio button list
          if (is_file('./backups/' . $name))
          {
            print '<tr>
                     <td><input name="remove_name" value="' . $name . '" type="radio" onclick="enable_remove_btn();"></td>
                     <td>' . $name . '</td>
                   </tr>';
          }
        }
        print '</table>';
      }
      else
      {
        print 'No backup copies available.';
      }
    ?>
    <br />
  </form>
  <script>
    function display_msg1()
    {
      document.getElementById("msg1").innerHTML = "Processing...";
    }
  </script>
</html>
