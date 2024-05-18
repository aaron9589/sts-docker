<html>
  <head>
    <title>Remove Rolling Stock Image</title>
    <style>
      body {font: normal 20px Verdana, Arial, sans-serif;}
      table {border-collapse: collapse;}
      tr {vertical-align: top}
      th {border: 1px solid black; padding: 10px}
      th.vert_bottom {vertical-align: bottom}
      td {border: 1px solid black; padding: 10px}
      td.numbers {text-align: center}
    </style>
    <script>
      window.onunload = refreshParent;
      function refreshParent() {
          window.opener.location.reload();
      }
  </script>
  </head>
  <body>
    <h3>Edit Cars</h3>
    <?php
      print '<h3>Upload a Rollingstock Image</h3>';

      // check to see if the upload button was clicked
      if (isset($_POST['remove_btn']))
      {
        // set the image name based on the image number (1 or 2)
        if ($_POST['image'] == 1)
        {
          $img_file_name = $_POST['obj_name'];
        }
        else
        {
          $img_file_name = $_POST['obj_name'] . 'b';
        }
        
        // remove the file
        if (unlink('./ImageStore/DB_Images/RollingStock/' . $img_file_name . '.jpg'))
        {
          print 'The image file for ' . $_POST['obj_name'] . ' has been successfully removed.<br /><br />';
        }
        else
        {
          print 'Unable to remove the file for ' . $_POST['obj_id'] . '. Contact the development team.<br /><br />';
        }
        //print 'Click on the CLOSE button to return to the Edit Car screen. <input type="button" value="CLOSE" onclick="window.close();">';
        print '<br /><br />Close this window to return to the Edit Car Screen<br /><br />';
      }
      else
      {
        // this must be the first time in, so get the name of the file to be uploaded and display an UPLOAD button
        print '<form action="remove_img.php" method="post">';

        // set the image name based on the image number (1 or 2)
        if ($_GET['image'] == 1)
        {
          $img_file_name = $_GET['obj_name'];
        }
        else
        {
          $img_file_name = $_GET['obj_name'] . 'b';
        }
        
        print '<p>To remove this image for ' . $_GET['obj_id'] . ' click the REMOVE button.</p>';
               
        print '<input type="submit" name="remove_btn" id="remove_btn" value="REMOVE">&nbsp;';
               //<input type="submit" name="close_win" id="close_win" value="CANCEL" onclick="window.close();"><br /><br />';
        print '<br /><br />Close this window to return to the Edit Car Screen<br /><br />';

        print '<img src="./ImageStore/DB_Images/RollingStock/' . $img_file_name . '.jpg" style="width:640px;"><br />';

        // pass the incoming car index, car reporting marks, and image number_format through to this form when it's called a second time
        print '<input type="hidden" id="obj_id" name="obj_id" value="' . $_GET['obj_id'] . '">
               <input type="hidden" id="obj_name" name="obj_name" value="' .$_GET['obj_name'] . '">
               <input type="hidden" id="image" name="image" value="' . $_GET['image'] . '">';
        print '</form>';
      }
    ?>
  </body>
</html>