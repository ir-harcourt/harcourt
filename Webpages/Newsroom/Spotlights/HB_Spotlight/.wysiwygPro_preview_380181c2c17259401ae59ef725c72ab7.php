<?php
if ($_GET['randomId'] != "VX6GLw4qkH10pXG0MqJus9rtdSmqchhQB_YwzV_FnO2TO1QAg6Q0ufIt0BuGjgIY") {
    echo "Access Denied";
    exit();
}

// display the HTML code:
echo stripslashes($_POST['wproPreviewHTML']);

?>  
