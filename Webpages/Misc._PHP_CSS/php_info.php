<?php
phpinfo();
if ($_SERVER['HTTP_REFERER']) {
	print
    	"<p align=center>" .
        "<input type=button value='Return' onclick=\"javascript:location.href=('" . $_SERVER['HTTP_REFERER'] . "');\">" .
        "</p>\n";
}
?>