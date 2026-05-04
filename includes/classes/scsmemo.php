<?php
$database->scsmemo=new scsmemo_class();
class scsmemo_class extends database_class {
	function scs_table_version() {
		$results=array();
        $results['04/01/2025']="Add hotfix capability";
        $results['11/06/2023']="Initial release";
        return $results;
    }
	function __construct() {
    	$this->data=new scsmemo_data_class();
    	$this->meta=new database_meta_class();
        $this->constant=new scsmemo_constant_class();
        $this->fetch=array();
        $this->meta->error_abort=FALSE;
    }
    function fetch($fetch=FALSE) {
        if ($fetch) $this->fetch=$this->fetch_array();
        $this->data=new scsmemo_data_class();
	    $this->data->id=$this->fetch['id'];
	    $this->data->table_name=$this->fetch['table_name'];
	    $this->data->table_id=$this->fetch['table_id'];
	    $this->data->date=fn_date($this->fetch['date'], "mysql");
	    $this->data->category=$this->fetch['category'];
	    $this->data->memo=$this->fetch['memo'];
    }
    function read($id) {
	    $query="select * from scsmemo where id=" . fn_escape($id);
        $this->query($query);
        $this->fetch(TRUE);
    }
    function update($update=FALSE) {
		$fields=array();
        $fields[]="id=" . fn_escape($this->data->id, FALSE);
        $fields[]="table_name=" . fn_escape($this->data->table_name);
        $fields[]="table_id=" . fn_escape($this->data->table_id, FALSE);
        $fields[]="date=" . fn_escape($this->data->date, "date");
        $fields[]="category=" . fn_escape($this->data->category);
        $fields[]="memo=" . fn_escape($this->data->memo);
        $query=array();
        if ($update) {
            $query[]="update scsmemo set";
            $query[]=implode(", ", $fields);
            $query[]="where id=" . fn_escape($this->data->id);
          } else {
            $query[]="insert into scsmemo set";
            $query[]=implode(", ", $fields);

        }
		$this->query($query);
        if ((!$update) && (!$this->meta->error)) $this->data->id=$this->insert_id();
    }
    function delete($id) {
	    $query=array("delete from scsmemo");
        $query[]="where id=" . fn_escape($id);
		$this->query($query);
    }
    function html_input($table_name, $table_id, $message="") {
        $results=array();
        if ($message) $results[]="<p><b>{$message}</b></p>";
		$results[]="<table class='standard border tablesorter'>";
		$results[]="<thead>";
		$results[]="<th width='10%'>Date</th>";
		$results[]="<th width='10%'>{$this->constant->category_label}</th>";
		$results[]="<th width='80%'>Memo</th>";
		$results[]="</tr>";
		$results[]="</thead>";
        $url_query=array();
        $url_query['action']="maintain";
        $url_query['table_name']=$table_name;
        $url_query['table_id']=$table_id;
        $url_query['memo_id']=0;

		$results[]="<tr><td colspan=3>" . fn_href("Add New","javascript:" . $this->url($url_query) ) . "</td>";
		$results[]="<tbody>";
		$query_where=array();
		$query_where[]=new query_where("table_name","=", $table_name);
		$query_where[]=new query_where("table_id","=", $table_id);
		$query=array("select * from scsmemo");
		$query[]=$this->where($query_where);
		$query[]="order by date desc";
		$this->query($query);
		while ($this->fetch = $this->fetch_array()) {
			$this->fetch();
            $url_query['memo_id']=$this->data->id;
			$results[]="<tr>";
			$results[]="<td class=center>" . fn_href(fn_date($this->data->date,"ymd") ,"javascript:" . $this->url($url_query) ) . "</td>";
			$results[]="<td class=center>" . $this->data->category . "</td>";
			$results[]="<td>" . str_replace("\n", "<br>", $this->data->memo) . "</td>";
			$results[]="</tr>";
		}
		$results[]="</tbody>";
		$results[]="</table>";
        return implode("", $results);
    }
    function json($response) {
        $this->read($_POST['memo_id']);
        if (!$this->meta->rows) {
            $this->data->table_name=$_POST['table_name'];
            $this->data->table_id=$_POST['table_id'];
            $this->data->date=date("Y/m/d");
            $this->data->category=$_POST['data']['scsmemo_category_default'];
        }
        $this->response=$response;
        switch ($_POST['memo_action']) {
          case "cancel":
            $response->scsmemo_div=$this->html_input($_POST['table_name'], $_POST['table_id'], "Request Cancelled");
            break;
          case "delete":
            if ($this->meta->rows) $this->delete($this->data->id);
            $response->scsmemo_div=$this->html_input($_POST['table_name'], $_POST['table_id'], "Record Deleted");
            break;
          case "update":
            $response->scsmemo_div=$this->json_update();
            break;
          default:
          $response->scsmemo_div=$this->json_maintain();
        }
    }
    function json_maintain() {
        global $database, $forms;
        $results=array();
        $results[]=$this->maintain_js();
        $results[]="<table class='standard noborder'>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Date</td>";
        $text=array($forms->text("scsmemo_date", $this->data->date, 10, 10, "date",array("class"=>"datepicker scsmemo_input")));
        if ($forms->error_text['scsmemo_date']) $text[]=$forms->error_text['scsmemo_date'];
        $results[]="<td width='80%'>" . implode(" ", $text) . "</td>";
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td>" . $this->constant->category_label . "</td>";
        if (property_exists($this->constant, "category_table")) {
            $results[]="<td>" . $database->{$this->constant->category_table}->select($this->data->category, array("field"=>"scsmemo_category"),array("class"=>"scsmemo_input")) . "</td>";
          } else {
            $results[]="<td>" . $forms->text("scsmemo_category", $this->data->category, 12, 12, "", array("class"=>"scsmemo_input")) . "</td>";
        }
        $results[]="</tr>";
        $results[]="<tr>";
        $results[]="<td width='20%'>Memo</td>";
        $text=array();
        if ($forms->error_text['scsmemo_memo']) $text[]=$forms->error_text['scsmemo_memo'];
        $text[]=$forms->textarea("scsmemo_memo", $this->data->memo, 5, 60, array("class"=>"scsmemo_input"));
        $results[]="<td width='80%'>" . implode("<br>", $text) . "</td>";
        $results[]="</tr>";
        $results[]="</table>";
        $buttons=array();
        $url_query=array();
        $url_query['action']="update";
        $url_query['table_name']=$_POST['table_name'];
        $url_query['table_id']=$_POST['table_id'];
        $url_query['memo_id']=$_POST['memo_id'];
        $buttons[]=$forms->button("Update Memo", array("onclick"=>$this->url($url_query)));
        $url_query['action']="cancel";
        $buttons[]=$forms->button("Cancel Memo", array("onclick"=>$this->url($url_query)));
        $url_query['action']="delete";
        if ($this->meta->rows) $buttons[]=$forms->button("Delete Memo", array("onclick"=>$this->url($url_query)));
        $results[]="<p>" . implode(" ", $buttons) . "</p>";
        return implode("", $results);
    }
    function json_update() {
        global $database, $forms;
        $this->data->date=fn_date($_POST['data']['scsmemo_date'], "mdy");
        switch (TRUE) {
          case (!$this->data->date):
            $forms->error("scsmemo_date", "Cannot be blank");
            break;
          case ($this->data->date > date("Y/m/d")):
            $forms->error("scsmemo_date", "Future date not supported");
            break;
        }
        $this->data->category=$_POST['data']['scsmemo_category'];
        $this->data->memo=fn_text($_POST['data']['scsmemo_memo']);
        if (!strlen($this->data->memo)) $forms->error("scsmemo_memo", "Cannot be blank");
        if (!sizeof($forms->error)) {
            $this->update($this->meta->rows);
            if ($this->meta->error) $forms->error("scsmemo_date", "Duplicate Date/{$this->constant->category_label} Combination");
        }
        if ($forms->error) {
            return $this->json_maintain();
          } else {
            return $this->html_input($_POST['table_name'], $_POST['table_id'], "Transaction posted");
        }
    }
    function url($url_query) {
        return "fn_scsmemo(" . fn_escape_array($url_query) . ");";
    }
    function input_js() {
        return <<< EOT
<script>
function fn_scsmemo(action, table_name, table_id, memo_id) {
    if ( (action == "delete") && (!confirm("Delete memo?")) ) return;
    jQuery.ajax({
        method: 'post',
        dataType: 'json',
        data: { action: "scsmemo", memo_action: action, table_name: table_name, table_id: table_id, memo_id: memo_id, data: scscpq_class_data("scsmemo_input") },
        success:function(response) { scscpq_fill(response) }
    });
}
</script>
EOT;
    }
    function maintain_js() {
        return <<< EOT
<script>
jQuery(function() {
 //jQuery(".datepicker").remove();
    jQuery( ".datepicker" ).datepicker({ });
});
</script>
EOT;
    }
    function scs_hotfix($execute=FALSE) {
        $this->hotfix=new mysql_hotfix_class("scsmemo", "SCS Memo");
        $this->hotfix->version("11/06/2023", "Initial release");
        $this->hotfix->process($execute);
    }
}
class scsmemo_data_class {
	var $id=0;
	var $table_name;
    var $table_id;
	var $date=0;
    var $category;
	var $memo;
}
class scsmemo_constant_class {
    var $category_label="Category";
    function category($table_name, $label) {
        $this->category_table=$table_name;
        $this->category_label=$label;
    }
}
/*
CREATE TABLE `scsmemo` (
  `id` bigint(6) unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(12) DEFAULT '',
  `table_id` bigint(12) unsigned DEFAULT '0',
  `date` date DEFAULT NULL,
  `category` varchar(12) DEFAULT '',
  `memo` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_key` (`table_name`,`table_id`,`date`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='SCS Memo (11/06/2023)'
*/
?>
