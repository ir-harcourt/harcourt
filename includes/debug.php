<?php
// Catch all errors & exceptions
$debug_fh=fopen("debug.txt","w");
function exception_error_handler($severity, $message, $file, $line) {
global $debug_fh;
	$text=array();
	$text['severity']=$severity;
	$text['message']=$message;
	$text['file']=$file;
	$text['line']=$line;
	fputcsv($debug_fh, $text, "\t", "\"");
}
set_error_handler("exception_error_handler");
?>