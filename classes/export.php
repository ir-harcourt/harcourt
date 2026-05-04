<?php
$database->export = new export_class();
class export_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['12/04/2024']="Initial Release";
        return $results;
    }
    function __construct() {
        $this->data=new export_data_class();
        $this->meta=new database_meta_class();
        $this->fetch=array();
        $this->constant=new export_constant_class();
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
    	$this->data=new export_data_class();
        switch ($this->report) {
          case "email":
            $this->fetch_email();
            break;
        }
    }
    function fetch_email() {
        foreach ($this->fetch as $key => $value) {
            $this->data->$key=$value;
        }
    }
    function map($options) {
        global $database;
        $this->map=new table_map_class($options);
    }
    function initialize($report) {
        $this->report=$report;
        switch ($this->report) {
          case "email":
            return $this->initialize_email();
            break;
          default:
            die("Unknown report: {$report}");
        }
    }
    function initialize_email() {
        global $database;
        $this->map->item("email",array("name"=>"Email", "width"=>40));
        $this->map->item("company_name",array("name"=>"Company Name", "width"=>40));
	    $this->map->item("address",array("name"=>"Address", "width"=>40));
	    $this->map->item("city",array("name"=>"City", "width"=>40));
	    $this->map->item("state",array("name"=>"State/Province"));
	    $this->map->item("zip",array("name"=>"ZIP/Postal Cost","type"=>"uppercase"));
	    $this->map->item("country_code",array("type"=>"uppercase","name"=>"Country Code"));

        $query= <<< EOT
CREATE TEMPORARY TABLE `export` (
  `email` varchar(256) DEFAULT NULL,
  `user_id` bigint(10) NOT NULL,
  `first_date` date DEFAULT NULL,
  `last_date` date DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `city` varchar(40) DEFAULT NULL,
  `state` varchar(40) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1
EOT;
        $database->temp->query($query);

        $query= <<< EOT
insert into export (user_id, email, first_date, last_date)
select
user_id,
email,
date_format(from_unixtime(min(date_time)),"%Y/%m/%d"),
date_format(from_unixtime(max(date_time)),"%Y/%m/%d")
from log
where email <> ''
and user_id <> 0
group by email
EOT;
        $database->temp->query($query);

        $query= <<< EOT
update export
inner join user on user.id=export.user_id
set
export.company_name=user.company_name,
export.address=user.address,
export.city=user.city,
export.state=user.state,
export.zip=user.zip,
export.country_code=user.country_code
EOT;
        $database->temp->query($query);

        $query_where=array();
        $query_where[]=new query_where("company_name", "<>", "");
        switch ($this->map->options['source']) {
          case "first":
            if ($this->map->options['from_date']) $query_where[]=new query_where("first_date", ">=", $this->map->options['from_date']);
            if ($this->map->options['thru_date']) $query_where[]=new query_where("first_date", "<=", $this->map->options['thru_date']);
            break;
          case "last":
            if ($this->map->options['from_date']) $query_where[]=new query_where("last_date", ">=", $this->map->options['from_date']);
            if ($this->map->options['thru_date']) $query_where[]=new query_where("last_date", "<=", $this->map->options['thru_date']);
            break;
        }
        $query=array("select * from export");
        $query[]=$database->where($query_where);
        $query[]="order by email";
        return $query;
    }
}
class export_data_class {

}
class export_constant_class {

}
?>