<?php
$fh=fopen("beta.txt", "w");
fwrite($fh, print_r($_SERVER, TRUE));
fclose($fh);
?>