<?php
$php_version=explode(".",phpversion());
if (floatval($php_version[0] . "." . $php_version[1]) > 5.3) {
	require_once "scs_pdf_rev0.php";
  } else {
	require_once "scs_pdf_legacy.php";
}
?>