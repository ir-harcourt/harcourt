<?php
/*
COPYRIGHT NOTICE:
Copyright 1981 - 2021 Suburban Computer Services, Inc All Rights Reserved.

This program is furnished under a license restricing its use solely for the operation
of a designated computer for a particular  purpose, and may  not be copied, reproduced
disclosed,  or otherwise  used without the prior written, consent of Suburban Computer
Services, Inc.  Title to and ownership of the program shall at all times remain the
property of Suburban Computer Services.

development	http://localhost:8085/query_test.php
staging		https://harcourt.scscpq.com/query_test.php
production	https://harcourt.co/query_test.php
*/
if (file_exists("scs_inc.php")) require_once "scs_inc.php";

// standard library
require_once "database_mysqli.php";
require_once "functions_rev2.php";
require_once "forms_rev2.php";

// standard tables
require_once "classes/registry.php";

switch (TRUE) {
  case (fn_development_server()):
	$database->connect("localhost","demo","suburban","harcourt");
    break;
  case ($_SERVER['SERVER_NAME'] == "harcourt.scscpq.com"):
	$database->connect("localhost","suburb29_thayes","suburban","suburb29_harcourt");
    break;
  default:
	$database->connect("localhost","harcourt","npE3dglcJYckOtpgNtGW","wp_harcourt");
}
$database->registry=new registry_class();
$forms->report['action']=trim($_POST['action']);
$forms->report['term']=trim($_POST['term']);
switch (TRUE) {
  case ($forms->report['action']):
	if (!strlen($forms->report['term'])) {
    	$forms->error('term');
        break;
    }
	$stopwatch=new stopwatch_class("Query Benchmark",4);
    $stopwatch->split("SKU Search");

	$query=array("select");
    $query[]="inventory.sku as 'code',concat(category.name,': ',subcategory.name,' (',inventory.sku,')') as 'ajax_label',inventory.*";
    $query[]="from inventory";
    $query[]="left join inventory_xref on inventory_xref.sku=inventory.sku";
    $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
    $query[]="left join category on category.id=subcategory.category_id";
    $query[]="where inventory.active = '1'";
    $query[]=" and category.active = '1'";
    $query[]=" and subcategory.status in ('Active', 'Pending')";
    $query[]=" and subcategory.id in ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '12', '16', '17', '18', '19', '20', '21', '22', '24', '25', '26', '27', '28', '29', '30', '31', '37', '38', '40', '43', '44', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '100', '101', '105', '106', '107', '108', '109', '110', '112', '113', '114', '115', '116', '117', '118', '119', '120', '121', '122', '123', '124', '129', '131', '132', '133', '134', '135', '136', '137', '140', '141', '142', '143', '144', '147', '148', '151', '152', '153', '154', '155', '156', '157', '158', '159', '160', '161', '164', '165', '166', '167', '168', '170', '171', '172', '173', '174', '175', '176', '177', '178', '179', '180', '181', '182', '183', '184', '185', '186', '187', '188', '189', '190', '191', '192', '193', '194', '195', '196', '197', '198', '199', '208', '209', '210', '211', '212', '213', '214', '215', '216', '217', '218', '219', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', '234', '235', '236', '237', '238', '243', '273', '274', '275', '276', '279', '280', '283', '284', '285', '286', '287', '288', '289', '290', '291', '292', '293', '294', '295', '296', '297', '298', '300', '301', '302', '303', '306', '308', '310', '311', '312', '314', '315', '316', '318', '321', '322', '325', '326', '327', '330', '331', '332', '333', '336', '337', '338', '339', '340', '341', '342', '343', '344', '345', '358', '359', '360', '361', '362', '366', '367', '368', '369', '372', '373', '374', '375', '376', '382', '384', '387', '388', '389', '390', '391', '392', '393', '394', '395', '396', '397', '398', '399', '400', '401', '402', '403', '404', '405', '406', '410', '411', '412', '413', '414', '415', '416', '417', '418', '419', '425', '429', '430', '431', '432', '433', '434', '435', '437', '438', '440', '441', '442', '443', '444', '445', '446', '447', '448', '449', '450', '451', '452', '453', '454', '455', '456', '457', '458', '459', '460', '461', '463', '464', '465', '467', '468', '469', '470', '471', '472', '473', '474', '475', '476', '479', '481', '482', '483', '484', '485', '486', '487', '488', '493', '494', '495', '496', '497', '500', '501', '502', '503', '504', '505', '506', '507', '508', '512', '513', '514', '515', '518', '519', '520', '521', '522', '523', '524', '525', '526', '527', '528', '529', '530', '531', '532', '533', '534', '535', '536', '537', '539', '540', '541', '542', '543', '544', '545', '546', '547', '549', '550', '552', '553', '554', '556', '557', '558', '560', '561', '562', '563', '564', '565', '567', '568', '569', '570', '571', '572', '573', '576', '577', '578', '579', '580', '581', '583', '584', '585', '586', '587', '588', '589', '592', '593', '594', '596', '597', '598', '602', '603', '604', '605', '606', '607', '608', '609', '610', '611', '612', '613', '617', '618', '619', '620', '621', '622', '623', '624', '625', '626', '627', '628', '629', '630', '631')";
    $query[]=" and (";
    $query[]="  inventory.sku = " . fn_escape($forms->report['term']);
    $query[]="  or inventory_xref.competitor_sku = " . fn_escape($forms->report['term']);
    $query[]=")";
    $query[]="group by inventory.sku";
    $query[]="order by category.sort_order,category.name,subcategory.name";
    $query[]="limit 1";

//    $stopwatch->split_comment($query);
    $database->temp->query($query);
    if ($database->temp->meta->rows) $database->temp->fetch();
    $stopwatch->split_comment( ( ($database->temp->meta->rows) ? "SKU: " . $database->temp->fetch['code'] : "Not found") );
	if ($database->temp->meta->rows) break;
    $stopwatch->split("Subcategory Search");
    $search_terms=array_filter(explode(" ",$forms->report['term']));
    $query=array();
    $query[]="select";
    $query[]="subcategory.id as 'code',";
    $query[]="concat(category.name,': ',subcategory.name) as 'ajax_label',";
    $query[]="inventory.*";
    $query[]="from inventory";
    $query[]="left join subcategory on subcategory.id=inventory.subcategory_id";
    $query[]="left join category on category.id=subcategory.category_id";
    $query[]="where inventory.active = '1'";
    $query[]=" and category.active = '1'";
    $query[]=" and not isnull(category.id)";
    $query[]=" and subcategory.status in ('Active', 'Pending')";
    $query[]=" and subcategory.id in ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '12', '16', '17', '18', '19', '20', '21', '22', '24', '25', '26', '27', '28', '29', '30', '31', '37', '38', '40', '43', '44', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '100', '101', '105', '106', '107', '108', '109', '110', '112', '113', '114', '115', '116', '117', '118', '119', '120', '121', '122', '123', '124', '129', '131', '132', '133', '134', '135', '136', '137', '140', '141', '142', '143', '144', '147', '148', '151', '152', '153', '154', '155', '156', '157', '158', '159', '160', '161', '164', '165', '166', '167', '168', '170', '171', '172', '173', '174', '175', '176', '177', '178', '179', '180', '181', '182', '183', '184', '185', '186', '187', '188', '189', '190', '191', '192', '193', '194', '195', '196', '197', '198', '199', '208', '209', '210', '211', '212', '213', '214', '215', '216', '217', '218', '219', '222', '223', '224', '225', '226', '227', '228', '229', '230', '231', '232', '233', '234', '235', '236', '237', '238', '243', '273', '274', '275', '276', '279', '280', '283', '284', '285', '286', '287', '288', '289', '290', '291', '292', '293', '294', '295', '296', '297', '298', '300', '301', '302', '303', '306', '308', '310', '311', '312', '314', '315', '316', '318', '321', '322', '325', '326', '327', '330', '331', '332', '333', '336', '337', '338', '339', '340', '341', '342', '343', '344', '345', '358', '359', '360', '361', '362', '366', '367', '368', '369', '372', '373', '374', '375', '376', '382', '384', '387', '388', '389', '390', '391', '392', '393', '394', '395', '396', '397', '398', '399', '400', '401', '402', '403', '404', '405', '406', '410', '411', '412', '413', '414', '415', '416', '417', '418', '419', '425', '429', '430', '431', '432', '433', '434', '435', '437', '438', '440', '441', '442', '443', '444', '445', '446', '447', '448', '449', '450', '451', '452', '453', '454', '455', '456', '457', '458', '459', '460', '461', '463', '464', '465', '467', '468', '469', '470', '471', '472', '473', '474', '475', '476', '479', '481', '482', '483', '484', '485', '486', '487', '488', '493', '494', '495', '496', '497', '500', '501', '502', '503', '504', '505', '506', '507', '508', '512', '513', '514', '515', '518', '519', '520', '521', '522', '523', '524', '525', '526', '527', '528', '529', '530', '531', '532', '533', '534', '535', '536', '537', '539', '540', '541', '542', '543', '544', '545', '546', '547', '549', '550', '552', '553', '554', '556', '557', '558', '560', '561', '562', '563', '564', '565', '567', '568', '569', '570', '571', '572', '573', '576', '577', '578', '579', '580', '581', '583', '584', '585', '586', '587', '588', '589', '592', '593', '594', '596', '597', '598', '602', '603', '604', '605', '606', '607', '608', '609', '610', '611', '612', '613', '617', '618', '619', '620', '621', '622', '623', '624', '625', '626', '627', '628', '629', '630', '631')";
    $query[]=" and not isnull(subcategory.id)";
    $query[]=" and (";
    $query_where=array();
    foreach ($search_terms as $search_term) {
    	$query_where[]="concat(category.name,' ',subcategory.name,' ',coalesce(subcategory.search_words,'')) like " . fn_escape("%{$search_term}%");
    }
    $query[]=implode("\n and ",$query_where);
    $query[]=")";
    $query[]="group by ajax_label, inventory.sku";
    $query[]="order by category.sort_order, category.name, subcategory.sort_order, subcategory.name, inventory.sort_order, inventory.sku";
//    $stopwatch->split_comment($query);
    $database->temp->query($query);
    $stopwatch->split_comment("SKU's found: " . number_format($database->temp->meta->rows));
	break;
}


$forms->title("Harcourt Query Benchmark");
$forms->html_load();
$forms->html_open();
print $forms->message($_POST);
print $forms->open();
print $forms->hidden("action","query");
print "<p>Search term: " . $forms->text("term",$forms->report['term'],30) . "</p>";
print "<p>" . $forms->submit("Go!") . "</p>";
if (isset($stopwatch)) print implode("\n",$stopwatch->results(TRUE));
print $forms->close();
$forms->html_close();
?>