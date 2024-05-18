<html>
  <head>
    <title>STS - Restore DB</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
    <script>
      function enable_restore_btn()
      {
        document.getElementById("restore_btn").disabled = false;
        document.getElementById("msg1").innerHTML = "";
      }
    </script>
    <script>
      function display_msg1()
      {
        document.getElementById("msg1").innerHTML = "Restore operation started...<br /><br />";
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
    <h3>Restore Database</h3>
    <form action="restore_db.php" method="get">
    Click on the radio button for the backup copy that you want to restore and then click the <b>RESTORE</b> button.<br /><br />
    The current contents of the database for this railroad will be erased and replaced with the data stored in the backup copy.<br /><br />
    If you are restoring a backup file for a different railroad, run the <b>WIPE</b> function first.<br /><br />
    <?php
      // bring in the utility files
      require 'open_db.php';
      require 'credentials.php';

      // get a database connection
      $dbc = open_db();

      // get the list of name from the backup directory
      $backup_files = array_slice(scandir('./backups'), 2);
      if (count($backup_files) > 0)
      {
        // since there are backup copies, we can now display the restore button
        print '<input id="restore_btn" name="restore_btn" value="RESTORE" type="submit" onmouseup="display_msg1();" disabled><br /><br />';

        // if the Restore button has been clicked, display a "running" status message, otherwise nothing
        if (isset($_GET['restore_btn']))
        {
          print '<div id="msg1" style="color: red;">Restore operation running...<br /><br /></div>';
        }
        else
        {
          print '<div id="msg1" style="color: red;"></div>';
        }

        // build a table of backup names with radio buttons
        print '<table>';
        foreach($backup_files as $file_name)
        {
          // only generate radio buttons for files, not for directories
          if (is_file('./backups/' . $file_name))
          {
            print '<tr>
                     <td><input id="restore_name" name="restore_name" value="' . $file_name . '" type="radio" onclick="enable_restore_btn();"></td>
                     <td>' . $file_name . '</td>
                   </tr>';
          }
        }
        print "</table>";
      }
      else
      {
        print 'No backup copies available.';
      }
    ?>
    <br />
    <?php
      // has the Restore button been clicked?
      if (isset($_GET["restore_btn"]))
      {
// print 'Restore button clicked<br />';
        print '<script>
                 document.getElementById("msg1").innerHTML = "Restore operation running...<br /><br />";
               </script>';

        // get the name of the backup to be restored from the form
        $restore_name = $_GET['restore_name'];
// print 'Backup File Name: ' . $restore_name;
        // read the sql file into an array
        $sql_string = file_get_contents('./backups/' . $restore_name);

        // parse the string using the comment lines as delimiters
        $sql = explode('#', $sql_string);
        foreach($sql as $sql_cmd)
        {
// print 'Backup File Line: ' . $sql_cmd . '<br />';
          // ignore any empty lines
          if (!empty(trim($sql_cmd)))
          {
// print 'SQL: ' . $sql_cmd . '<br />';
            if(!mysqli_query($dbc, $sql_cmd))
            {
              if (strpos($sql_cmd, 'drop') === false)
              {
                print 'SQL: [' . $sql_cmd . '] Error ' . mysqli_error($dbc) . '<br />';
              }
            }
          }
        }
        // get rid of any existing QR, bar code, upload and rollingstock photo files.
        // the QR and barcode files will be replaced when the reports are run
        
        //first, get a list of all of the file names in the barcodes folder.
        $files = glob('./ImageStore/DB_Images/barcodes' . '/*.*');
         
        //Loop through the file list.
        foreach($files as $file)
        {
          //Make sure that this is a file and not a directory.
          if(is_file($file)){
              //Use the unlink function to delete the file.
                unlink($file);
//print 'Deleting barcode file ' . $file . '<br />';
          }
        }        
        
        //next, get a list of all of the file names in the qrcodes folder.
        $files = glob('./ImageStore/DB_Images/qrcodes' . '/*.*');
         
        //Loop through the file list.
        foreach($files as $file)
        {
          //Make sure that this is a file and not a directory.
          if(is_file($file)){
              //Use the unlink function to delete the file.
                unlink($file);
//print 'Deleting QR code file ' . $file . '<br />';
          }
        }        

        //get a list of all of the file names in the uploads folder.
        $files = glob('./ImageStore/DB_Images/uploads' . '/*.*');
         
        //Loop through the file list.
        foreach($files as $file)
        {
          //Make sure that this is a file and not a directory.
          if(is_file($file)){
              //Use the unlink function to delete the file.
                unlink($file);
//print 'Deleting uploaded file ' . $file . '<br />';
          }
        }
        
        // get a list of all the file names in the rollingstock photo directory
        $files = glob('./ImageStore/DB_Images/RollingStock' . '/*.*');
         
        //Loop through the file list.
        foreach($files as $file)
        {
          //Make sure that this is a file and not a directory.
          if(is_file($file)){
              //Use the unlink function to delete the file.
                unlink($file);
//print 'Deleting rollingstock photo ' . $file . '<br />';
          }
        }
        
        // if a subdirectory exists in the backup directory with the same name as the backup file,
        // copy it's contents to the rollingstock photos directory
        $restore_dir = './backups/' . $restore_name . '_photos';
        if (file_exists($restore_dir))
        {
          $files = glob($restore_dir . '/*.*');
          
          // if there are files in there, make a backup copy of them
          if (sizeof($files) > 0)
          {
            // copy the photos from the backup directory to the rollingstock photos directory
            print 'Copying backup photos to rollingstock photo directory...<br /><br />';
            $photo_dir = './ImageStore/DB_Images/RollingStock';
            foreach($files as $file)
            {
              $file_to_go = str_replace($restore_dir,$photo_dir,$file);
              copy($file, $file_to_go);
            }
          }
        }
        
        print '<script>
                 document.getElementById("msg1").innerHTML = "' . $restore_name . ' restored...<br /><br />";
               </script>';
      }
    ?>
    </form>
</html>
