<html>
  <head>
    <title>STS - Back-up DB</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      td {border: 1px solid black; padding: 10px}
    </style>
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
  <h3>Back Up Database</h3>
  <form action="backup_db.php" method="get">
    <div align="left">Enter a name for the backup file and then click the <b>Back 
    Up</b> button. This file can be used to restore the database at a later date.<br />
    <br />
    (Leave the file name box empty and click the Back Up button to refresh the 
    list of existing files.)<br />
    <br />
    <input id="backup_name" name="backup_name" type="text">
    &nbsp; 
    <input id="backup_btn" name="backup_btn" value="BACK UP" type="submit" onclick="display_msg1();">
    <br />
    <br />
    <?php
      // set up a message area
      print '<div id="msg1"><br /></div><br />';

      // bring in the utility files
      require 'open_db.php';

      // bring in the function that backs up all of the tables into one file
      require 'backup_tables.php';

      // get a database connection
      $dbc = open_db();

      // has the Back Up button been clicked?
      if (isset($_GET['backup_btn']))
      {
        // get the name of the backup from the form
        $backup_name = strtolower($_GET['backup_name']);

        // get a list of files in the backup directory
        $backup_names = array_slice(scandir('./backups'), 2);

        // display a list of existing files in the backup directory
        if (count($backup_names) > 0)
        {
          print 'Existing backup files: <ul><li>' . implode('<li>', $backup_names) . '</ul>';
        }

        // check to see if this name has already been used
        if (in_array($backup_name, $backup_names))
        {
          print 'That name has already been used. Try again.';
        }
        else
        {
          if (strlen($backup_name) > 0)
          {
            // dump the current database to the backups directory and use the desired name for the file
            backup_tables($dbc, $backup_name);
            print 'Database backup file created...<br /><br />';
            
            // get a list of files in the rollingstock photo directory
            $photo_dir = './ImageStore/DB_Images/RollingStock';
            $files = glob($photo_dir . '/*.*');
            
            // if there are files in there, make a backup copy of them
            if (sizeof($files) > 0)
            {
              // create a subdirectory of the backups directory with the same name as the backup sql file
              $backup_dir = './backups/' . $backup_name . '_photos';
              print 'Creating rollingstock photo backup directory ' . $backup_dir . '...<br /><br />';
              mkdir($backup_dir);
              
              // copy the photos from the rollingstock photo directory into the new photo backup directory
              print 'Copying rollingstock photos to backup directory...<br /><br />';
              foreach($files as $file)
              {
                $file_to_go = str_replace($photo_dir,$backup_dir,$file);
                copy($file, $file_to_go);
              }
            }
            print 'Backup complete...';
          }
        }
      }
    ?>
    </div>
  </form>
  <script>
    function display_msg1()
    {
      document.getElementById("msg1").innerHTML = "Processing...";
    }
  </script>
</html>
