<?php
/*
https://devharcourt.wpengine.com/dashboard/
*/
?>
<html>
<head>
<title>Word Press Test</title>
<meta name="description" content="Word Press Test">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
<meta name="robots" content="noarchive, noimageindex">
<meta charset="utf-8">
<link rel="icon" href="/scs_images/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Rajdhani:400,600,700|Open+Sans:400italic,600italic,100,200,300,400,600,700|Oswald:400,700">
<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/scs.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/products.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/campaign.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/tablesorter.css">
<link rel="stylesheet" type="text/css" href="/scs_scripts/menu.css">
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<!-- <script type="text/javascript" src="https://devharcourt.wpengine.com/scs_scripts/scscpq_wp.js"></script> -->
</head>
<body>

<script>
jQuery(document).ready(function() {
	scscpq_event('initial');
});

function scscpq_event(action) {
	$.ajax({
//		url: "https://devharcourt.wpengine.com/scscpq_wp.php",
		url: 'scscpq_wp.php',
		data: { action: action, page: jQuery("#scscpq_page").text(), token: sessionStorage['scscpq_token'] },
        type: 'post',
	    dataType: 'json',
	    success: function(response) {
			for (const [key, value] of Object.entries(response)) {
				switch (true) {
                  case (key == "token"):
                  	sessionStorage['scscpq_token']=value;
                  	break;
                  case (key == "scscpq_page"):
                 	jQuery("#" + key).html(value);
                  	break;
                  case (key.substring(0,6) == "scscpq"):
                 	jQuery("." + key).html(value);
                  	break;
                }
			}
        },
        error: function(xhr, response) {
			jQuery("#scscpq_status").html(xhr.responseText);
        }
	});
}
</script>


<div id="scs_content">
<div>Hi Tom</div>

<div>Menu</div>
<div class=scscpq_menu>scscpq_menu</div>

<div>Shopping Cart</div>
<div class=scscpq_cart>scscpq_cart</div>

<div>Search</div>
<div class=scscpq_search>scscpq_search</div>

<div>Dashboard</div>
<div id=scscpq_page>dashboard</div>

<div>Other Classes</div>
<div class=other1>other1</div>
<div class=other2>other2</div>
<div class=other3>other3</div>
<div class=other4>other4</div>

<div>Status</div>
<div id=scscpq_status></div>

<div>Solutions</div>
<div class=scscpq_solutions>scscpq_solutions</div>

</div>

</body>
<html>