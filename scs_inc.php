<?php
ini_set('default_charset', 'UTF-8');
date_default_timezone_set("America/Detroit");
error_reporting( E_ALL ^(E_NOTICE | E_DEPRECATED  | E_STRICT) );
set_include_path(
	get_include_path() .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/includes" .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/includes_legacy" .     
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/composer" .
    PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . "/scsps");
?>